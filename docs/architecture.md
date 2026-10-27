# Architecture, concepts et patterns

Ce document fixe les concepts et les patterns de la librairie. Toute contribution les suit ; un écart se discute
et se consigne ici avant d'être codé.

**Idée directrice :** le modèle **décrit** (déclaratif), la sérialisation **décide** (version, conversions,
rejets). Un champ = une propriété annotée ; une différence entre versions = une classe. Rien d'autre.

## 1. Couches et règle de dépendance

```
Support  ←  Enums  ←  Metadata  ←  Objects / Contracts  ←  Serialization  ←  Validation
   (Exceptions : utilisables par toutes les couches)
```

| Couche | Dossier | Contenu |
| --- | --- | --- |
| Support | `src/Support/` | `Undefined`, `JsonPointer` : briques sans dépendance |
| Enums | `src/Enums/` | `Version`, `Strictness`, `ParameterLocation`… |
| Metadata | `src/Metadata/` | Attribut `#[Field]`, `ClassMetadata`, `MetadataFactory` |
| Objects | `src/Objects/`, `src/Contracts/` | Les 29 objets de la spec, les unions « X ou Reference » |
| Serialization | `src/Serialization/` | `Serializer`, `Normalizer`, `Rewriters/`, `Diagnostics`, `Encoders/` |
| Validation | `src/Validation/` | `Validator`, `Rules/`, `Violations` |
| Exceptions | `src/Exceptions/` | Hiérarchie unique d'exceptions |

- Une couche ne dépend que des couches à sa gauche. `Objects` ne connaît ni `Serialization` ni `Validation`.
- **Seule exception :** la classe de base `SpecObject` expose `toArray()` / `toJson()` / `toYaml()` en façade,
  qui délègue à `Serializer`. Aucun autre objet n'importe `Serialization`.
- `Exceptions` ne dépend que de `Support`, `Enums` et des classes de diagnostic de `Serialization`
  (`Diagnostics`, `Diagnostic`, `DiagnosticLevel`), que porte `UnsupportedFeatureException`.
- Ces règles sont vérifiées par des tests d'architecture (`phpat`, extension PHPStan) dans la CI :
  `tests/Architecture/ArchitectureTest.php`, exécuté par `composer stan`.

## 2. Le modèle

### 2.1 `SpecObject` : objet de valeur immuable

Toutes les classes de `src/Objects/` héritent de `SpecObject` (ou de `ExtensibleSpecObject`). Patterns :
**Value Object immuable**, **Fluent Interface**, **Named Constructor**.

```php
final class Info extends ExtensibleSpecObject
{
    #[Field] protected string|Undefined $title = Undefined::Value;
    #[Field(since: Version::V3_1_0)] protected string|Undefined $summary = Undefined::Value;
    #[Field] protected string|Undefined $description = Undefined::Value;
    #[Field] protected string|Undefined $termsOfService = Undefined::Value;
    #[Field] protected Contact|Undefined $contact = Undefined::Value;
    #[Field] protected License|Undefined $license = Undefined::Value;
    #[Field] protected string|Undefined $version = Undefined::Value;

    public function title(?string $title): static
    {
        return $this->with('title', $title);
    }

    public function summary(?string $summary): static
    {
        return $this->with('summary', $summary);
    }

    // … une méthode par champ, même nom que dans la spec
}
```

Règles :

1. **Classes `final`.** On étend par composition et par extensions `x-*`, jamais par héritage.
   Seules `SpecObject` et `ExtensibleSpecObject` sont abstraites.
2. **Construction** : constructeur `final protected`, création par `create(?string $objectId = null)` et par les
   raccourcis sémantiques (`Schema::string()`, `Operation::get()`…), qui appellent tous `create()`.
3. **Modification** : chaque setter appelle `with()` de la base, qui clone et affecte. L'objet courant ne change
   jamais. Aucune autre façon d'écrire une propriété.
4. **Ordre des propriétés = ordre des champs dans le texte de la spec.** C'est cet ordre qui donne l'ordre des
   clés en sortie ; on ne trie jamais.
5. **Lecture** : propriétés `protected` exposées en lecture par `__get` (comme GoldSpec), déclarées en
   `@property-read` pour l'analyse statique. Un champ non défini se lit `null` ; `has('field')` distingue
   « non défini » de `null`.

### 2.2 `Undefined` : la sentinelle

`enum Undefined { case Value; }` dans `src/Support/`. C'est la valeur par défaut de **tous** les champs.

- Un champ `Undefined` n'apparaît pas en sortie ; un champ `null` s'écrit `null`.
- Setters des champs typés (`?string`, `?Contact`…) : `null` remet le champ à `Undefined`.
- Champs à valeur libre (`default`, `example`, `const`, valeurs d'extensions) : `null` est une vraie valeur.
  Pour retirer un champ, `without('default')`.
- `Undefined` n'apparaît jamais dans une signature publique.

### 2.3 Métadonnées déclaratives : `#[Field]`

Pattern **Metadata Mapping**. L'attribut porte tout ce que la sérialisation doit savoir d'un champ :

| Argument | Rôle | Exemple |
| --- | --- | --- |
| `name` | Nom en sortie si différent de la propriété | `#[Field(name: '$ref')] protected … $ref` |
| `since` | Première version qui connaît le champ | `#[Field(since: Version::V3_1_0)]` |
| `until` | Dernière version qui le connaît | `#[Field(until: Version::V3_0_4)]` |
| `map` | Tableau indexé par `objectId` (écrit `{}` même vide) | `#[Field(map: true)]` |
| `emit` | `false` pour une intention que seul un rewriter traduit | `#[Field(emit: false)] protected bool $nullable` |
| `patterned` | Map dont les entrées s'écrivent directement dans l'objet (champs « patterned » de la spec) | `Responses::$responses`, `Callback::$pathItems`, `SecurityRequirement::$schemes` |
| `keyedByObjectId` | `false` pour une map de valeurs (setters en `array<string, T>`), sans `objectId` à lire | `#[Field(map: true, keyedByObjectId: false)] protected array $scopes` |

- `MetadataFactory` lit les attributs par réflexion une seule fois par classe et met le résultat en cache
  (**Flyweight**). C'est le seul cache autorisé, car il est pur et déterministe.
- La disponibilité d'un champ selon la version est **toujours** déclarée par `since` / `until`, jamais codée
  dans un `if`.

### 2.4 Enums versionnées

Les cas d'enum apparus dans une version ultérieure le déclarent (`ParameterLocation::Querystring`,
`HttpMethod::Query`, `SecuritySchemeType::MutualTLS`). Ces enums implémentent `VersionedEnum::since(): Version` ;
la sérialisation les traite exactement comme un champ `since`. Le `since()` d'un cas est une déclaration, jamais
un corps neutre. Les raccourcis s'appuient sur ces enums (`Operation::get()` utilise `HttpMethod::Get->value`).
`VersionedEnum` vit dans `src/Enums/` : une enum ne peut pas dépendre de `Contracts`, qui est à sa droite.

### 2.5 Unions « X ou Reference » : interfaces de contrat

Partout où la spec accepte « X ou Reference Object », le type est une interface de `src/Contracts/`
(`SchemaContract`, `ResponseContract`…) implémentée par l'objet et par `Reference`. Pas de `Schema|Reference`
dans les signatures.

- Les raccourcis `Response::ref()`, `Parameter::ref()`… renvoient un `Reference` et construisent eux-mêmes le chemin
  `#/components/<section>/<nom>` : un `Reference` ne connaît pas sa section, `Reference::to()` écrit tel quel.
- **Exception :** `Schema::ref()` renvoie un `Schema` qui porte `$ref`. En JSON Schema, `$ref` est un mot-clé
  comme un autre : `Schema::ref('User')->nullable()` et les champs voisins restent possibles, et les rewriters les
  convertissent pour 3.0. Ce champ n'a donc pas de setter d'instance (il partagerait le nom du raccourci statique).
- Les champs dont le nom commence par `$` ont un setter sans le `$` (`$id` → `id()`, `$defs` → `defs()`) et
  `#[Field(name: '$id')]`.
- Sous-schémas booléens (JSON Schema 2020-12) : `items`, `contains`, `not`, `propertyNames`, `if` / `then` / `else`,
  `unevaluated*` et `additionalProperties` acceptent `SchemaContract|bool`. `BooleanSchemaRewriter` les convertit
  pour 3.0 (`{}` / `{not: {}}`), sauf `additionalProperties`, booléen dans toutes les versions.

### 2.6 `objectId`, collections et maps

- `objectId` est la clé de l'objet dans son parent ; il ne fait pas partie des champs `#[Field]`.
- Chaque champ liste ou map a **deux** méthodes, qui renvoient toutes deux un clone :
    - le setter au nom de la spec, variadique, qui **remplace** tout le contenu : `->servers(Server ...$servers)` ;
    - `add` + le nom exact du champ, variadique, qui **ajoute** à la fin : `->addServers(Server ...$servers)`.
- Nom des méthodes `add*` : `add` suivi du nom du champ, sans mise au singulier. On évite ainsi les pluriels
  irréguliers (`requestBodies`) et les collisions (`example()` existe déjà à côté de `examples()`).
  Exemples : `addProperties()`, `addPaths()`, `addTags()`, `addRequired()`, `addAllOf()`.
- Listes (`servers`, `tags`, `required`, `allOf`…) : `add*` ajoute sans dédoublonner ; l'unicité éventuelle
  (`required`, `enum`) relève du validateur. **Exception :** `Schema.type`, dont un doublon est interdit dans toutes
  les versions : `type()` et `addType()` le refusent (`SpecObjectTestCase::listsOfUniqueValues()`).
- Maps (`properties`, `paths`, `responses`, sections de `components`) : indexées par `objectId`, dans les deux
  méthodes. `objectId` manquant → `MissingObjectIdException`. Un `objectId` déjà présent → `DuplicateObjectIdException`,
  avec `add*` comme avec le setter : on ne remplace jamais une entrée en silence.
- Les deux méthodes passent par la base : `with('servers', $items)` et `withAdded('servers', $items)`.
- `Responses` : une réponse d'`objectId` `default` va dans le champ `default`. `responses()` (qui remplace) la
  substitue à la précédente et garde celle-ci quand le nouvel ensemble n'en contient pas ; `addResponses()` (qui
  ajoute) lève `DuplicateObjectIdException` si le champ est déjà défini.
- `PathItem::operations()` reconnaît une méthode fixe quelle que soit sa casse (`GET` → `get`) ;
  `additionalOperations()` et `addAdditionalOperations()` la refusent, quelle que soit sa casse (`QUERY` compris).
- Champs exclusifs, refusés à la construction dans toutes les versions : `License.url` / `identifier`,
  `Link.operationRef` / `operationId`, `example` / `examples` (`Parameter`, `Header`, `MediaType`),
  `Example.value` / `externalValue` / `dataValue` / `serializedValue` (`value` exclut les trois autres,
  `serializedValue` exclut `externalValue`), `encoding` / `prefixEncoding` + `itemEncoding` (`MediaType`,
  `Encoding`), `Xml.nodeType` / `attribute` + `wrapped`. Une collection vide ne compte pas comme définie.
  `exclusiveOf($field, ...$others)` porte la règle, dans la base `SpecObject`.
- `Xml.nodeType` est une enum (`XmlNodeType`), comme les autres valeurs fermées.
- Dans un champ à valeur libre (`example`, `value`, `dataValue`), un tableau vide est une valeur : il compte comme
  défini pour les champs exclusifs.
- `Schema::type()` sans argument retire le type.
- `Operation::responses()` accepte un `Responses` ou des réponses qu'il enveloppe : c'est le seul setter qui ne se
  réduit pas à un appel de `with()`.
- **Exception : maps de valeurs scalaires** (`Link.parameters`, `Discriminator.mapping`, `OAuthFlow.scopes`,
  `Schema.dependentRequired`, `SecurityRequirement`) : leurs entrées n'ont pas d'`objectId`, les deux méthodes
  prennent donc un `array<string, T>` au lieu d'arguments variadiques, et le champ le déclare
  (`keyedByObjectId: false`) pour que `with()` n'ait rien à deviner. Une clé déjà présente lève aussi
  `DuplicateObjectIdException`.
- `SpecObjectTestCase` vérifie que chaque champ liste ou map possède bien ses deux méthodes.

### 2.7 Extensions

`ExtensibleSpecObject` ajoute `x(string $name, mixed $value)` et `withoutX(string $name)`. Seuls les objets que la
spec déclare extensibles en héritent : `Reference` et `SecurityRequirement` ne le sont pas.

## 3. La sérialisation

```
OpenApi ──► Normalizer (parcours) ──► Rewriters (une différence de version chacun) ──► array ──► Encoder (JSON | YAML)
                 │                              │
                 └────── Context : Version, Strictness, JsonPointer, Diagnostics ──────┘
```

| Pattern | Classe | Rôle |
| --- | --- | --- |
| **Visitor** (parcours d'arbre) | `Normalizer` | Parcourt le graphe d'objets avec `ClassMetadata`, construit le tableau, suit le JSON Pointer |
| **Chain of Responsibility / Pipeline** | `Rewriter` + `RewriterChain` | Chaque rewriter traite **une** différence entre versions |
| **Notification** | `Diagnostics` | Collecte toutes les conversions et tous les rejets, au lieu de s'arrêter au premier |
| **Ports & Adapters** | `Encoder` (`JsonEncoder`, `SymfonyYamlEncoder`) | Isole les dépendances optionnelles |
| **Facade** | `Serializer`, `SpecObject::toJson()` | Point d'entrée unique |

### 3.1 `Normalizer`

Pour chaque objet, le `Normalizer` :

1. lit ses `ClassMetadata` ;
2. pour chaque champ défini et émis : vérifie `since` / `until` contre la version cible. Si le champ est indisponible,
   il signale `Unsupported` et l'ignore ; sinon il le normalise (récursion sur les objets, listes, maps) ;
3. écrit un objet ou une map vide en `\stdClass` (donc `{}` en JSON, jamais `[]`), y compris dans `toArray()` ;
   les rewriters respectent la même convention ;
4. passe le nœud obtenu à la `RewriterChain` ;
5. remet les clés du nœud réécrit dans l'ordre des champs (tri stable, rang précalculé par `ClassMetadata::rank()`) :
   les entrées d'un champ « patterned » à la place de ce champ, les clés inconnues ensuite, les extensions en
   dernier. Un rewriter n'a donc pas à placer les clés qu'il ajoute (`anyOf` à la place d'un `$ref`…).

Une map non vide dont les clés forment une liste (`"0"`, `"1"`…, que PHP convertit en entiers) est écrite en
`\stdClass`, comme une map vide : JSON en fait un objet, jamais un tableau.

Une valeur libre (`default`, `example`, extensions…) est écrite telle quelle, après vérification récursive (tableaux
et `\stdClass`) : un `JsonSerializable` est remplacé par ses données, pour que JSON et YAML écrivent la même chose ;
`NAN`, `INF`, une ressource ou un autre objet lèvent une `InvalidArgumentException` qui porte le JSON Pointer de la
valeur.

Le `Normalizer` ne contient **aucune** règle propre à une version.

### 3.2 `Rewriter` : une différence de version = une classe

```php
interface Rewriter
{
    public function supports(SpecObject $object): bool;

    /**
     * @param array<string, mixed> $node   le nœud normalisé de $object
     * @return array<string, mixed>        le nœud réécrit pour $context->version
     */
    public function rewrite(SpecObject $object, array $node, Context $context): array;
}
```

- Un rewriter couvre **une ligne** des tableaux de la spec (section 6), pour **toutes** les versions :
  `NullableRewriter`, `ExclusiveBoundsRewriter`, `ConstRewriter`, `SchemaExamplesRewriter`, `MultiTypeRewriter`,
  `RefSiblingsRewriter`, `ContentEncodingRewriter`, `ResponseDescriptionRewriter`, `EmptyPathsRewriter`…
- Il lit l'intention dans le modèle (`$schema->nullable`) et l'écrit selon `$context->version`.
- Il signale ce qu'il fait : `Converted` pour une conversion, `Unsupported` pour ce qui ne s'exprime pas.
- L'ordre de la chaîne est explicite, dans `RewriterChain::default()`, et testé sur les combinaisons
  (`nullable` + `$ref` + plusieurs types).
- Quatre rewriters ne correspondent pas à une ligne de conversion : `OpenApiVersionRewriter` écrit la version cible
  dans `openapi` ; `NonOAuthScopesRewriter` (scopes hors OAuth2, rejet en 3.0) travaille sur le nœud `OpenApi`,
  qui contient tout le document ; `ReservedExtensionRewriter` refuse les préfixes `x-oai-` / `x-oas-` à partir de
  3.1 ; `MediaTypeReferenceRewriter` refuse un `Reference` dans un `content` avant 3.2.
- Il n'y a donc pas de classe `V30Adapter` : « supporter 3.0 », c'est l'ensemble des `since` / `until` et des
  rewriters.

### 3.3 Diagnostics et mode strict

`Diagnostic` = niveau (`Converted`, `Dropped`, `Unsupported`), `JsonPointer`, message. À la fin du parcours :

- **Strict** : s'il y a au moins un `Unsupported`, une seule `UnsupportedFeatureException` qui les contient tous ;
- **Permissif** : chaque `Unsupported` devient `Dropped`, et le résultat porte la liste complète.

Règles fixées par la suite de tests (M2) :

- `Converted` signale toute sortie qui diffère de la forme du modèle (celle de la dernière ligne) : surtout en
  3.0, mais aussi la `description` vide d'une réponse en 3.1, et `nullable` sur une `$ref` dans toutes les
  versions. Le mode (strict ou permissif) n'a pas d'effet sur une conversion.
- Le pointeur d'un diagnostic est celui du nœud réécrit ; celui d'un champ `Unsupported` est celui du champ.
- Un cas d'enum indisponible (`mutualTLS`, `querystring`, style `cookie`) retire l'objet qui le porte, au
  pointeur de cet objet.
- Scopes d'un schéma non OAuth en 3.0 (permissif) : la liste devient `[]`, pointeur `/security/0/ApiKey` ; ils
  sont cherchés dans `security` du document, des opérations, des opérations de leurs callbacks et de
  `components.callbacks`.
- Une conversion est signalée une fois, même quand plusieurs rewriters y participent (plusieurs types + `nullable`,
  `contentMediaType` + `contentEncoding`) ; deux conversions distinctes d'un même nœud donnent deux `Converted`.
- En 3.0, le schéma « null seul » s'écrit `{enum: [null], type: object, nullable: true}`, dans l'ordre des champs,
  y compris dans une branche `anyOf`.
- `NullableWithoutTypeRewriter` est le dernier rewriter de `Schema` : la branche qu'il construit contient des
  mots-clés déjà convertis, et `RefSiblingsRewriter` lui laisse un `$ref` nullable sans type.
- Les extensions réservées `x-oai-` / `x-oas-` sont écrites en 3.0, retirées ou rejetées à partir de 3.1.
- `Validator::validate()` ne lève pas `UnsupportedFeatureException` : chaque fonctionnalité non exprimable devient
  une violation dont la règle est `Serializer::class` ; les erreurs du schéma officiel ont pour règle
  `OpisJsonSchemaValidator::class`.
- YAML : `Yaml::DUMP_OBJECT_AS_MAP | DUMP_EMPTY_ARRAY_AS_SEQUENCE | DUMP_MULTI_LINE_LITERAL_BLOCK |
  DUMP_NUMERIC_KEY_AS_STRING`, profondeur inline 10, indentation 2 ; les codes de statut sont donc entre guillemets.

## 4. La validation

| Pattern | Classe | Rôle |
| --- | --- | --- |
| **Rule / Specification** | `Rule` | Une règle sémantique par classe |
| **Composite** | `Validator` | Applique une liste de règles, activables et désactivables |
| **Notification** | `Violations` | Collecte toutes les violations |
| **Ports & Adapters** | `JsonSchemaValidator` (`OpisJsonSchemaValidator`) | Validation officielle (niveau 1), dépendance optionnelle |

Les règles travaillent sur **le document sérialisé** (le tableau effectivement écrit, avec ses JSON Pointers),
pas sur le modèle : on valide ce qui sort, pour une version donnée.

```php
interface Rule
{
    /** @param array<string, mixed> $document */
    public function check(array $document, Version $version, Violations $violations): void;
}
```

- `Validator::validate()` écrit le document en mode permissif. S'il a fallu retirer une fonctionnalité (violation
  `Serializer::class`), ni le schéma officiel ni les règles ne s'exécutent : le document écrit n'est plus celui décrit,
  et on signalerait les conséquences du retrait (document vide, référence orpheline).
- Une règle peut recevoir un document que le schéma officiel rejette : chaque accès vérifie la forme du nœud.
- Le parcours est partagé par `Rules\Document` (interne) : path items et opérations écrits sur place, dans l'ordre du
  document (`paths`, `webhooks`, puis `components`), chaque opération suivie de celles de ses callbacks ; un path item
  `$ref` n'est pas suivi par ce parcours. `Document::resolve()` suit les `$ref` locaux (paramètres, path items,
  schémas de sécurité). Une violation est signalée à l'endroit où l'on corrige, la référence plutôt que sa cible.
- `Rules\Values` repère les valeurs libres (`example`, `default`, `const`, `enum`, `value`, `dataValue`, extensions,
  liste `examples` d'un schéma) : un `$ref` ou un `schema` qui s'y trouve est une donnée. Dans une map de noms
  (`properties`, sections de `components`…), ces clés sont des noms et sont parcourues.
- `OpisJsonSchemaValidator` charge les schémas à la première validation, une fois par instance (pas d'état
  statique) : on réutilise une instance pour valider plusieurs documents.

## 5. Les erreurs

- Toutes les exceptions implémentent l'interface `OpenApiWriterException` et étendent l'exception SPL qui
  correspond (`InvalidArgumentException extends \InvalidArgumentException`…).
- Elles se créent par des constructeurs nommés : `InvalidArgumentException::forField(Info::class, 'license', '…')`,
  `UnsupportedFeatureException::fromDiagnostics($diagnostics)`.
- Le message commence par la cible : `Info::license: url and identifier are mutually exclusive`.
- Exception immédiate seulement pour une valeur interdite **dans toutes les versions**. Ce qui dépend de la version
  relève de la sérialisation ; ce qui dépend du document entier relève du validateur.
- Valeurs interdites retenues (`InvalidArgumentException`) : `url` + `identifier` d'une licence, `example` +
  `examples` (Parameter, Header, MediaType), `value` + `externalValue` (Example), `operationRef` + `operationId`
  (Link), code de statut mal formé, compteur `min*` / `max*` négatif, `multipleOf` ≤ 0, type inconnu ou en double,
  `x('')` et `x('x-')`, `Reference::for()` sur un objet hors composants.
- Messages de référence : `Parameter::in: "body" is not one of query, header, path, cookie, querystring`,
  `Components::schemas: an entry with the objectId "User" already exists`, `Components::schemas: an entry has no
  objectId`, `Reference::for: the Schema has no objectId`, `symfony/yaml is required to write YAML: composer
  require symfony/yaml`, et `"2 features cannot be written for this version:\n- /webhooks: …"`.

## 6. Conventions de code

| Sujet | Règle |
| --- | --- |
| Classes | `final` par défaut ; abstraites seulement pour les classes de base du modèle |
| Traits | Interdits dans `src/` : base abstraite ou composition (cause principale du désordre du générateur) |
| État global | Aucun singleton, aucune propriété statique mutable ; seul le cache de `MetadataFactory` est permis |
| Types | `declare(strict_types=1)`, toutes les signatures typées ; `mixed` réservé aux valeurs libres et documenté |
| PHPDoc | Génériques pour les collections (`list<Server>`, `array<string, SchemaContract>`), pas de PHPDoc redondante |
| Enums | Les méthodes acceptent l'enum ou sa valeur ; conversion par `::from()`, valeur invalide = `InvalidArgumentException` |
| Nommage | Objets = nom de la spec au singulier ; unions = `*Contract` ; capacités = adjectif (`Extensible`) ; services = nom (`Rewriter`, `Rule`, `Encoder`) ; pas de suffixe `Interface` |
| Rewriters / règles | Nommés d'après la fonctionnalité : `NullableRewriter`, `UniqueOperationIdRule` |
| Fichiers | Une classe par fichier, PSR-4, dossier = couche |

## 7. Patterns de test

- **Template Method** : `SpecObjectTestCase` (abstraite) teste pour chaque objet l'immuabilité, `objectId`,
  les extensions et chaque champ à partir d'un fournisseur `fields()`. Chaque classe de test d'objet l'étend et
  ajoute ses cas propres.
- **Object Mother** : `tests/Support/Documents.php` fournit les documents de référence (petstore, webhooks…),
  partagés par les suites Conformance et Validation.
- **Un test par rewriter et par version**, et un test par règle (un cas valide, un cas invalide).
- Assertions partagées dans une classe de base `TestCase` (`assertSerializesTo`, `assertValidOpenApi`), pas dans
  des traits.
- Structure Arrange / Act / Assert, nom du test en phrase.
- **Fixtures** : `tests/fixtures/<ligne>/` (`3.0`, `3.1`, `3.2`), écrites pour le dernier patch de la ligne ; pour
  un autre patch, le helper remplace le champ `openapi`. Format `JSON_PRETTY_PRINT` de PHP plus un saut de ligne,
  comparé octet par octet. Un fragment est enveloppé dans un document minimal (`Envelope`) avant d'être validé.
- La fixture attendue est validée contre le schéma officiel **avant** la comparaison (`OfficialSchema`, outil de
  test indépendant du code testé, configuré comme `OpisJsonSchemaValidator`).
- `composer fixtures:update` réécrit les fixtures depuis la sortie réelle, seulement quand elle est du JSON valide :
  chaque modification se relit dans le diff.
- Les doublures de test (rewriters, règles) sont des classes nommées de `tests/Support/` : les règles `phpat` de
  nommage s'appliquent aussi aux classes anonymes.

## 8. Recettes

### Ajouter un champ

1. Ajouter la propriété `#[Field(since: …)]` à sa place dans l'ordre de la spec.
2. Ajouter le setter du même nom, qui appelle `with()`.
3. Ajouter le cas dans `fields()` du test de l'objet, et la fixture de sortie de chaque version concernée.
4. Si le champ ne s'exprime pas dans une version antérieure, rien d'autre à faire : `since` suffit. S'il se
   **convertit**, écrire un rewriter (recette suivante).

### Ajouter une conversion entre versions

1. Créer `src/Serialization/Rewriters/<Feature>Rewriter.php` et l'insérer à sa place dans
   `RewriterChain::default()`.
2. Écrire le test du rewriter pour chaque version, et la ligne correspondante de la matrice `tests/Compatibility/`.
3. Cocher la ligne dans le document du jalon (M4 ou M5).

## 9. Garde-fous automatiques

| Garde-fou | Ce qu'il vérifie |
| --- | --- |
| PHPStan niveau max + règles strictes | Types, code mort, comparaisons |
| `phpat` (tests d'architecture, `composer stan`) | Règle de dépendance entre couches, classes `final`, abstraites réservées à la base du modèle, aucun trait dans `src/`, nommage (`*Contract`, `*Rewriter`, `*Rule`, pas de suffixe `Interface`) |
| PHP-CS-Fixer (PER-CS) | Style |
| `SpecObjectTestCase` | Contrat commun des 29 objets |
| Infection | Qualité des tests (MSI ≥ 85 %) |
