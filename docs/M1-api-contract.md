# M1 — Contrat d'API

**Objectif.** Toute l'API publique existe sous forme de classes, interfaces et enums complètes en signatures et en
PHPDoc, sans logique. Elle est relue et validée par les contributeurs avant l'écriture des tests.

**Dépend de :** M0.
**Spécification :** sections 4 (principes), 5 (catalogue), 7 (références), 8 (sérialisation) et 9 (validation).
**Architecture :** [architecture.md](architecture.md) — tous les livrables suivent ses concepts et conventions.

## Règle des corps vides

Les méthodes renvoient une valeur neutre (`clone $this`, `[]`, `''`, `null`) au lieu de lever une exception.
Les tests du M2 échouent ainsi sur leurs assertions, et non sur une erreur fatale.

Les objets sont, eux, écrits dans leur forme définitive : propriétés `#[Field]` et setters `return $this->with(…)`.
Seul le socle (`SpecObject::with()`, `MetadataFactory`, `Normalizer`, rewriters, règles) reste à corps vide.

## Livrables

### Socle (`src/Support/`, `src/Metadata/`, classes de base)

- [x] `Support\Undefined` (sentinelle) et `Support\JsonPointer`
- [x] `Metadata\Field` (attribut : `name`, `since`, `until`, `map`, `emit`), `ClassMetadata`, `MetadataFactory`
- [x] `Objects\SpecObject` (`create()`, `with()`, `withAdded()`, `without()`, `has()`, `__get`, façades `toArray()` / `toJson()` / `toYaml()`)
      et `Objects\ExtensibleSpecObject` (`x()`, `withoutX()`)
- [x] `Enums\VersionedEnum` (`since(): Version`) — dans `Enums/` et non `Contracts/`, voir [architecture.md §2.4](architecture.md#24-enums-versionnées)
- [x] Tests d'architecture `phpat` : dépendances entre couches, classes `final`, pas de traits dans `src/`, nommage

### Enums (`src/Enums/`)

- [x] `Version` : un cas par version patch (3.0.0 → 3.0.4, 3.1.0 → 3.1.2, 3.2.0), `isAtLeast()`, `line()`, `default()` = 3.1.2
- [x] `Strictness` : `Strict` (défaut), `Lenient`
- [x] `HttpMethod` (dont `QUERY` pour 3.2)
- [x] `ParameterLocation` : `query`, `header`, `path`, `cookie`, `querystring`
- [x] `ParameterStyle` : `matrix`, `label`, `simple`, `form`, `spaceDelimited`, `pipeDelimited`, `deepObject`, `cookie`
- [x] `SchemaType` : `string`, `number`, `integer`, `boolean`, `array`, `object`, `null`
- [x] `SecuritySchemeType` : `apiKey`, `http`, `oauth2`, `openIdConnect`, `mutualTLS`
- [x] `ApiKeyLocation` : `query`, `header`, `cookie`

### Contrats (`src/Contracts/`)

- [x] `Extensible` (méthode `x()`)
- [x] Unions « X ou Reference » : `SchemaContract`, `ResponseContract`, `ParameterContract`, `RequestBodyContract`,
      `HeaderContract`, `ExampleContract`, `LinkContract`, `CallbackContract`, `SecuritySchemeContract`,
      `PathItemContract`, `MediaTypeContract`

### Objets (`src/Objects/`) — signatures de tous les champs, méthodes `add*` des listes et maps, constructeurs sémantiques

- [x] `OpenApi`, `Info`, `Contact`, `License`
- [x] `Server`, `ServerVariable`
- [x] `Components`
- [x] `PathItem`, `Operation`, `ExternalDocumentation`
- [x] `Parameter`, `RequestBody`, `MediaType`, `Encoding`
- [x] `Responses`, `Response`, `Callback`, `Example`, `Link`, `Header`
- [x] `Tag`, `Reference`
- [x] `Schema`, `Discriminator`, `Xml`
- [x] `SecurityScheme`, `OAuthFlows`, `OAuthFlow`, `SecurityRequirement`

### Sérialisation, validation, erreurs

- [x] `Serialization\Serializer`, `SerializationResult`, `Context`, `Normalizer`
- [x] `Serialization\Diagnostics`, `Diagnostic`, enum `DiagnosticLevel` (`Converted`, `Dropped`, `Unsupported`)
- [x] `Serialization\Rewriters\Rewriter` (interface), `RewriterChain` (`default()`), un rewriter vide par ligne du
      tableau des conversions (spécification, section 6)
- [x] `Serialization\Encoders\Encoder` (interface), `JsonEncoder`, `SymfonyYamlEncoder`
- [x] `Validation\Validator`, `Violations`, `Violation`, `Rule` (interface), `JsonSchemaValidator` (interface)
- [x] `Exceptions\OpenApiWriterException` (interface), `InvalidArgumentException`, `UnsupportedFeatureException`,
      `DuplicateObjectIdException`, `MissingObjectIdException`, `MissingDependencyException`

## Critères de sortie

- [x] Chaque champ du catalogue (spécification, section 5) a sa méthode, nommée exactement comme dans la spec
- [x] PHPStan niveau max vert
- [ ] API relue et validée par les contributeurs (PR approuvée)

## Notes de réalisation

- `SpecObject::with()`, `withAdded()` et les corps des services restent neutres ; les `@throws` documentés ne sont
  donc pas encore levés. PHPStan ignore `throws.unusedType` dans `src/` jusqu'au M3 (voir `phpstan.neon.dist`).
- Écarts consignés dans [architecture.md](architecture.md) : `VersionedEnum` dans `Enums/`, argument `patterned` de
  `#[Field]`, `Schema::ref()` qui renvoie un `Schema`, dépendance de `Exceptions` aux diagnostics, trois rewriters
  hors tableau de conversion (dont `MediaTypeReferenceRewriter`), maps de scalaires en `array`.
- Deuxième revue : `Version::default()` et les `since()` des enums versionnées sont des déclarations (3.1.2,
  `Query` / `Querystring` / style `cookie` en 3.2, `mutualTLS` en 3.1) ; `#[Field(keyedByObjectId: false)]` pour
  les maps de valeurs ; sous-schémas booléens + `BooleanSchemaRewriter` ; objets vides en `\stdClass` ;
  `default` d'un `Responses` routé depuis `responses()` ; les `::ref()` construisent le chemin de composant.
- `PathItem.$ref` existe dès 3.0 (texte officiel 3.0.4), contrairement au tableau de la spécification (section 6) :
  aucun `since` sur ce champ.
- Ajouts à l'API, hors liste initiale : `Validation\Severity` (erreur / avertissement), `Operation::responses()` qui
  accepte un `Responses` ou des réponses, `PathItem::operations()`, raccourcis `SecurityScheme::apiKey()`…,
  `Response::ok()`… qui posent la phrase de statut en `description` (comme GoldSpec).

## Questions à clore pendant M1

- Portée des exceptions immédiates à la construction (spécification, section 4, règle 10).
- Mesure du coût de l'immuabilité sur un document de 500 opérations.
