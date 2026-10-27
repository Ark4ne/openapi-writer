# M6 — Validation

**Objectif.** `Validator::validate($openApi, Version)` renvoie la liste des `Violation` (JSON Pointer, règle,
message), sans jamais lever d'exception sur un document invalide.

**Dépend de :** M3, M4, M5.
**Spécification :** section 9.

## Niveau 1 — schéma officiel

- [x] Validation contre le schéma officiel 3.0 (draft 4)
- [x] Validation contre les schémas officiels 3.1 et 3.2 (draft 2020-12)
- [x] Librairie JSON Schema en dépendance suggérée ; exception explicite si elle est absente
- [x] `schema-base` 3.1 et 3.2 appliqué quand `jsonSchemaDialect` est le dialecte OpenAPI
- [x] Mêmes verdicts que `tests/Support/OfficialSchema.php` sur toutes les fixtures (les adaptations opis existent
      en double)

## Niveau 2 — règles sémantiques (une classe par règle)

- [x] `operationId` unique dans le document
- [x] Chaque variable `{x}` d'un chemin a un paramètre `in: path` obligatoire correspondant
- [x] Pas de doublon `name` + `in` dans les paramètres d'une opération
- [x] Toute référence locale `#/...` pointe sur un élément existant
- [x] Toute exigence de sécurité nomme un `securityScheme` déclaré ; les scopes OAuth2 existent dans le flux
- [x] Tags utilisés déclarés (avertissement) ; en 3.2, `Tag.parent` existe et sans cycle
- [x] Chemins équivalents à un nom de variable près signalés
- [x] Au moins une opération dans `paths` ou `webhooks` (avertissement) ; l'exigence « `paths`, `components` ou
      `webhooks` » de 3.1 est déjà vérifiée par le schéma officiel
- [x] `default` et `example` conformes au type du schéma (avertissement)
- [x] Règles activables et désactivables, ajout de règles personnalisées

## Critères de sortie

- [x] Suite Validation verte
- [x] Couverture de lignes ≥ 95 % sur l'ensemble du projet
- [ ] MSI (Infection) ≥ 85 %

## Notes de réalisation

- `Validator::create()` contient les dix règles intégrées. `withRules()` et `withoutRules()` renvoient une copie.
- Quand la sérialisation retire une fonctionnalité, le schéma officiel et les règles ne s'exécutent pas
  (architecture §4).
- Parcours commun aux règles dans `Rules\Document` et `Rules\Values` (internes) : opérations des callbacks,
  `additionalOperations`, `components.pathItems` et `components.callbacks` comprises ; extensions de `paths` et
  valeurs libres ignorées ; `$ref` locaux suivis pour les paramètres, les path items et les schémas de sécurité.
- Comportements ajoutés, avec un cas de test chacun :
  - `LocalReferencesRule` vérifie aussi `operationRef` et les valeurs de `discriminator.mapping` ; un `$ref` dans un
    schéma qui a un `$id` se résout dans ce schéma.
  - `SecurityRequirementsRule` accepte en 3.2 un nom de schéma donné par URI : une URI locale doit se résoudre, une
    URI externe n'est pas vérifiée. Les scopes ne sont vérifiés que pour OAuth2, pas pour OpenID Connect.
  - `PathParametersRule` signale un path item `$ref` à l'endroit de la référence.
  - `ValueTypeRule` : `nullable: true` accepte `null` en 3.0, et `1.0` est un entier. Les schémas imbriqués et ceux
    des paramètres sont vérifiés.
- `OpisJsonSchemaValidator` reprend les adaptations de `tests/Support/OfficialSchema.php`, sans état statique. Un
  test compare les deux sur les 337 fixtures de documents : mêmes pointeurs d'erreur.
- Couverture de lignes du projet : 97,65 %.
- MSI non mesuré en local (Infection n'est pas installé) : à confirmer par la CI.
- L'exclusion PHPStan `throws.unusedType` sur `src/Validation/` est retirée.
- Revue de code appliquée :
  - erreurs de racine d'opis placées à `""` au lieu de `/` ;
  - document impossible à encoder en JSON signalé en violation au lieu d'une exception ;
  - nature d'un nœud (objet ou map de noms) tirée de son parent (`Rules\Scope` remplace `Rules\Values`) : un
    composant nommé `content` n'est plus parcouru comme une map ;
  - extensions de Responses et `requestBody` de Link traités comme valeurs libres ;
  - opérations et paramètres écrits à côté d'un `$ref` de path item vérifiés ;
  - path item externe compté par `NonEmptyDocumentRule` ;
  - `TagParentsRule` inactive avant 3.2 ;
  - version par défaut du `Validator` lue dans le champ `openapi` du document, sans recopier `declaredVersion()`.
