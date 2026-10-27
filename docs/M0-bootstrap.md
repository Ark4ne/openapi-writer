# M0 — Dépôt et outillage

**Objectif.** Un dépôt prêt à recevoir du code : paquet Composer, outils de qualité et CI qui tourne à chaque push.

**Dépend de :** rien.
**Spécification :** sections 11 (CI) et 12 (outillage).

## Livrables

- [x] `composer.json` : `ark4ne/openapi-writer`, espace de noms `Ark4ne\OpenApiWriter\`, `php ^8.2` + `ext-json` uniquement
- [x] PHPUnit `^11.5 || ^12.0 || ^13.0`, quatre suites déclarées (Unit, Compatibility, Conformance, Validation)
- [x] PHPUnit strict : échec sur test incomplet, ignoré, risqué, avertissement, notice ou dépréciation
- [x] PHPStan niveau max + `phpstan-strict-rules` + `phpstan-phpunit` sur `src/`, `tests/` et `bin/`
- [x] PHP-CS-Fixer en PER-CS (`.php-cs-fixer.dist.php`)
- [x] Configuration Infection (`infection.json5`, MSI minimum 85 %)
- [x] Script de seuil de couverture `bin/coverage-check.php` (95 % des lignes)
- [x] Workflow `.github/workflows/ci.yml` : jobs `tests`, `static`, `coverage`, `mutation`, `audit`
- [x] Scripts Composer : `test`, `test:coverage`, `stan`, `cs`, `cs:fix`, `mutation`, `progress`, `ci`
- [x] `README.md`, `CONTRIBUTING.md`, `CHANGELOG.md`, `LICENSE` (MIT), `.editorconfig`, `.gitattributes`, `.gitignore`
- [x] `CLAUDE.md` et dossier `docs/` (un document par jalon + récapitulatif généré)
- [ ] Dépôt GitHub `ark4ne/openapi-writer` créé
- [ ] Premier commit poussé sur `main`
- [ ] Protection de `main` : jobs `tests`, `static` et `coverage` obligatoires avant fusion

## Critères de sortie

- [x] Toutes les vérifications passent en local (PHP 8.5, dépendances au plus haut et au plus bas)
- [ ] CI verte sur GitHub pour PHP 8.2, 8.3, 8.4 et 8.5, dépendances `lowest` et `highest`

## Notes

- Depuis M1, `src/` contient du code : les jobs `coverage` et `mutation` tournent et restent rouges par construction
  jusqu'à ce que les tests du M2 et le code du M3 couvrent `src/`.
- La CI tourne sur `push` vers `main` et sur chaque pull request ; une branche est couverte par sa PR, sans double
  exécution de la matrice.
- Dépendances `lowest` vérifiées sur PHP 8.5 : PHPUnit 11.5.0 passe, `failOnPhpunitDeprecation` compris.
- PHP-CS-Fixer tourne sur PHP 8.4 en CI ; `setUnsupportedPhpVersionAllowed(true)` permet de le lancer sur 8.5 en local.
