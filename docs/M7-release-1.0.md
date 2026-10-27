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

## Notes de revue

- `bin/benchmark.php` : un passage de préchauffage précède les mesures (autoload, cache de métadonnées, premières
  écritures), les deux constructions alternent et la médiane est affichée ; la référence « en un appel » accumule
  les chemins sans recopie quadratique ; le nombre d'opérations, arrondi au multiple de 5, est annoncé. La première
  mesure avantageait `addPaths()` ; la décision donne les chiffres corrigés.
- `PathParametersRule` : une opération de la cible d'un `$ref` reste signalée à la référence, mais le message la
  nomme à sa place réelle (`/components/pathItems/Team/get`).
- `TagParentsRule` : un nom de tag déclaré deux fois garde les parents de chaque déclaration ; la recherche de cycle
  parcourt tous les parents.
- `SecurityRequirementsRule` : en 3.2, seul un nom contenant `/` ou `#` est une URI ; `oauth:main` est un nom de
  composant et doit être déclaré.
- `Violation::__toString()` réutilise `ClassName::short()`. `ReadmeTest` exécute le README une seule fois et valide
  aussi la sortie 3.0.4 du mode permissif contre le schéma officiel.

## Guide pour agents

- `skills/openapi-writer/` : `SKILL.md` (déclencheur, règles qui évitent les erreurs courantes, intégration dans un
  projet, erreurs, liste de contrôle), `reference.md` (chaque objet et chaque méthode, enums, sérialisation,
  validation), `recipes.md` (recettes complètes) et `versions.md` (conversions et fonctionnalités par version).
- Livré dans le paquet (pas d'`export-ignore`) : le projet consommateur le lie dans `.claude/skills/` et l'agent lit
  toujours la version installée.
- `tests/Unit/SkillTest.php` exécute les exemples de chaque fichier, valide sans aucune violation chaque document
  qu'ils construisent, et vérifie que `reference.md` nomme chaque méthode publique de chaque objet et chaque cas
  d'enum. `tests/Support/MarkdownExamples` exécute les blocs PHP, pour le README comme pour le skill.
