# M2 — Suite de tests complète

**Objectif.** Toute la suite de tests est écrite et revue avant l'implémentation. À la fin du jalon, elle compile,
passe PHPStan, et échoue entièrement sur des assertions.

**Dépend de :** M1.
**Spécification :** section 10 (stratégie de tests).

## Préparation

- [x] Catalogue recoupé champ par champ avec le texte officiel d'OpenAPI 3.0.4, 3.1.2 et 3.2.0
- [x] Schémas JSON officiels embarqués dans `src/Validation/schemas/` (3.0, 3.1, 3.2)
- [x] Librairie JSON Schema choisie (candidat : `opis/json-schema`), compatible draft 4 (3.0) et 2020-12 (3.1, 3.2)
- [x] Helper `assertSerializesTo($object, Version, string $fixture)`
- [x] Helper `assertValidOpenApi(string $json, Version)` contre le schéma officiel
- [x] Script de régénération des fixtures `tests/fixtures/<version>/`

## Suites

- [x] **Objets** (`tests/Unit/Objects/`) : une classe par objet (29), avec construction, chaque champ, raccourcis
      sémantiques, immuabilité, `objectId`, extensions, valeurs interdites
- [x] **Sérialisation** (`tests/Unit/Serialization/`) : sortie exacte de chaque objet en 3.0, 3.1 et 3.2 ; ordre des
      clés ; `{}` contre `[]` ; flags JSON ; YAML
- [x] **Références** (`tests/Unit/References/`) : `::ref()`, `Reference::for()`, composants en double, surcharges 3.1
- [x] **Matrice de versions** (`tests/Compatibility/`) : chaque ligne des tableaux de conversion et de rejet × chaque
      version, en mode strict et permissif
- [x] **Conformité** (`tests/Conformance/`) : exemples officiels de l'OpenAPI Initiative reconstruits avec l'API
      (petstore, callbacks, links, webhooks, non-oauth-scopes, tictactoe…)
- [x] **Validation** (`tests/Validation/`) : niveau 1 sur documents valides et invalides connus ; niveau 2 avec au
      moins un cas valide et un cas invalide par règle

## Critères de sortie

- [x] Chaque champ du catalogue a au moins un test
- [x] Chaque ligne des tableaux de la spécification (section 6) a au moins un test par version
- [x] Aucun test ne s'arrête sur une erreur fatale : tous échouent sur une assertion
- [x] PHPStan niveau max vert sur `tests/`
- [x] Suite relue et validée par les contributeurs

## Notes

- Pendant ce jalon, les jobs `tests` et `coverage` de la CI sont rouges par construction.
- Les sorties attendues sont des fichiers JSON, jamais des chaînes dans le code des tests.

## Notes de réalisation

- Recoupement du catalogue : propriétés de chaque objet des schémas officiels 3.0, 3.1 et 3.2 comparées aux champs
  `#[Field]`. Un oubli corrigé : `MediaType.description` (3.2), ajouté au contrat et à M5. `Header.allowEmptyValue`
  et `allowReserved` ne sont pas modélisés : ils n'existent que dans le schéma 3.0, ne s'appliquent qu'aux
  paramètres de requête et sont refusés par les schémas 3.1 et 3.2.
- Schémas officiels embarqués : 3.0 (2024-10-18), 3.1 (2026-08-03), 3.2 (2026-08-30), leurs `schema-base`,
  dialectes et méta-schémas, et les méta-schémas JSON Schema 2020-12. Un document dont `jsonSchemaDialect` est le
  dialecte OpenAPI est vérifié contre le `schema-base` de sa ligne.
- Librairie : `opis/json-schema` ^2.4, en `require-dev` et `suggest` (`symfony/yaml` aussi en `require-dev`). Elle
  demande des adaptations au chargement, documentées dans `OpisJsonSchemaValidator` : schéma 3.0 converti de
  draft 4 en draft 6 (`id`, `exclusiveMinimum` / `exclusiveMaximum` booléens), `$dynamicRef: "#meta"` remplacé par
  une `$ref` vers le dialecte (réécriture sur l'arbre décodé, pas sur le texte), `minContains: 0` réécrit en `not contains`, extensions opis désactivées (formats en
  annotations, aucun `default` inséré dans les données).
- Script de régénération : `composer fixtures:update` (variable `OPENAPI_WRITER_UPDATE_FIXTURES=1`). Une sortie
  invalide contre le schéma officiel n'écrase jamais une fixture.
- `NonEmptyDocumentRule` redéfinie : l'exigence 3.1 « `paths`, `components` ou `webhooks` » est déjà vérifiée par
  le schéma officiel ; la règle signale (avertissement, à la racine) un document sans aucune opération dans `paths`
  ou `webhooks`, dans toutes les versions.
- Les adaptations opis existent en double, dans `tests/Support/OfficialSchema.php` (indépendant du code testé) et
  dans `OpisJsonSchemaValidator` : M6 ajoutera un test qui compare leurs verdicts sur toutes les fixtures.
- Conformité : petstore, petstore-expanded, callback-example, link-example, api-with-examples, uspto, tictactoe,
  webhook-example, non-oauth-scopes, query-example, tags-example, pour chaque ligne qui peut les exprimer, avec
  les cas de rejet en 3.0 et 3.1.
- Matrice : 16 lignes de conversion et 56 lignes de rejet (dont 21 mots-clés JSON Schema) × 9 versions, en strict
  et en permissif, plus les extensions réservées. Décisions prises pendant ce jalon : voir
  [architecture.md §3.3 et §5](architecture.md#33-diagnostics-et-mode-strict).
- État en fin de jalon : 2870 tests, 2551 échecs, tous sur une assertion, aucune erreur. Les tests qui passent
  déjà portent sur ce que M1 a réellement écrit (enums, `JsonPointer`, constructeurs, signatures) ou sur les cas
  valides des règles, encore vides.
