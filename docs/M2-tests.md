# M2 — Suite de tests complète

**Objectif.** Toute la suite de tests est écrite et revue avant l'implémentation. À la fin du jalon, elle compile,
passe PHPStan, et échoue entièrement sur des assertions.

**Dépend de :** M1.
**Spécification :** section 10 (stratégie de tests).

## Préparation

- [ ] Catalogue recoupé champ par champ avec le texte officiel d'OpenAPI 3.0.4, 3.1.2 et 3.2.0
- [ ] Schémas JSON officiels embarqués dans `src/Validation/schemas/` (3.0, 3.1, 3.2)
- [ ] Librairie JSON Schema choisie (candidat : `opis/json-schema`), compatible draft 4 (3.0) et 2020-12 (3.1, 3.2)
- [ ] Helper `assertSerializesTo($object, Version, string $fixture)`
- [ ] Helper `assertValidOpenApi(string $json, Version)` contre le schéma officiel
- [ ] Script de régénération des fixtures `tests/fixtures/<version>/`

## Suites

- [ ] **Objets** (`tests/Unit/Objects/`) : une classe par objet (29), avec construction, chaque champ, raccourcis
      sémantiques, immuabilité, `objectId`, extensions, valeurs interdites
- [ ] **Sérialisation** (`tests/Unit/Serialization/`) : sortie exacte de chaque objet en 3.0, 3.1 et 3.2 ; ordre des
      clés ; `{}` contre `[]` ; flags JSON ; YAML
- [ ] **Références** (`tests/Unit/References/`) : `::ref()`, `Reference::for()`, composants en double, surcharges 3.1
- [ ] **Matrice de versions** (`tests/Compatibility/`) : chaque ligne des tableaux de conversion et de rejet × chaque
      version, en mode strict et permissif
- [ ] **Conformité** (`tests/Conformance/`) : exemples officiels de l'OpenAPI Initiative reconstruits avec l'API
      (petstore, callbacks, links, webhooks, non-oauth-scopes, tictactoe…)
- [ ] **Validation** (`tests/Validation/`) : niveau 1 sur documents valides et invalides connus ; niveau 2 avec au
      moins un cas valide et un cas invalide par règle

## Critères de sortie

- [ ] Chaque champ du catalogue a au moins un test
- [ ] Chaque ligne des tableaux de la spécification (section 6) a au moins un test par version
- [ ] Aucun test ne s'arrête sur une erreur fatale : tous échouent sur une assertion
- [ ] PHPStan niveau max vert sur `tests/`
- [ ] Suite relue et validée par les contributeurs

## Notes

- Pendant ce jalon, les jobs `tests` et `coverage` de la CI sont rouges par construction.
- Les sorties attendues sont des fichiers JSON, jamais des chaînes dans le code des tests.
