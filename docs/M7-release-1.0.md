# M7 — Version 1.0.0

**Objectif.** Publier la 1.0.0 sur Packagist, puis brancher la librairie dans `laravel-openapi-generator` V1.

**Dépend de :** M6.

## Livrables

- [x] `README.md` : installation et un exemple par famille d'objets (document, chemins, paramètres, corps,
      réponses, schémas, composants, sécurité, choix de version, mode permissif, validation)
- [x] `CONTRIBUTING.md` : comment ajouter un champ (objet → adaptateurs de version → tests → fixture)
- [x] `CHANGELOG.md` : entrée 1.0.0
- [x] Toutes les questions ouvertes de la spécification sont closes
- [ ] Tag `v1.0.0` et publication sur Packagist
- [ ] Badges CI et couverture dans le README

## Critères de sortie

- [ ] CI verte sur `main` au moment du tag
- [ ] `laravel-openapi-generator` V1 utilise la librairie et produit une sortie 3.0 identique à ses snapshots actuels

## Notes d'implémentation

- Les exemples PHP du README sont exécutés par `tests/Unit/ReadmeTest.php` : ils tournent comme un seul script, la
  sortie du démarrage rapide est comparée à `tests/fixtures/3.1/readme/quick-start.json`, le document construit au
  fil des exemples doit être valide en 3.0, 3.1 et 3.2 sans aucune violation, et les diagnostics du mode permissif
  sont ceux que le README annonce.
- Questions ouvertes de la spécification (section 14) : les quatre premières étaient tranchées depuis M0/M1 ; les
  trois dernières (librairie JSON Schema, coût de l'immuabilité, portée des exceptions immédiates) sont consignées
  dans les décisions de [README.md](README.md). `bin/benchmark.php` (`composer benchmark`) refait la mesure.
- Le badge CI est en place ; le badge de couverture attend un service de couverture (Codecov ou équivalent) branché
  sur le job `coverage`, à configurer avec le dépôt GitHub.
- Restent bloqués par la création du dépôt GitHub (M0) : badges, CI verte sur `main`, tag `v1.0.0` et publication
  Packagist. L'intégration dans `laravel-openapi-generator` se fait dans ce dépôt-là.
