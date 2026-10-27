# Feuille de route — OpenAPI Writer

Ce dossier suit la construction de la librairie, du dépôt vide (M0) à la version 1.0.0 (M7). Chaque jalon a son
document avec la liste de ses livrables ; le tableau ci-dessous en est calculé automatiquement.

**Spécification de référence :** [Spécification — Librairie PHP d'écriture OpenAPI (3.0 → 3.2)](https://claude.ai/artifact/PKmRGrPDwdnietSK4Aik3X)
**Architecture, concepts et patterns :** [architecture.md](architecture.md) — à lire avant toute contribution.

## Progression

<!-- progress:start -->
**Avancement global : `█████████░` 90 % (139 / 153 livrables)**

| Jalon | Statut | Avancement | Livrables |
| --- | --- | --- | --- |
| [M0 — Dépôt et outillage](M0-bootstrap.md) | En cours | `███████░░░` 75 % | 12 / 16 |
| [M1 — Contrat d'API](M1-api-contract.md) | En cours | `█████████░` 96 % | 32 / 33 |
| [M2 — Suite de tests complète](M2-tests.md) | Terminé | `██████████` 100 % | 17 / 17 |
| [M3 — Objets et sérialisation 3.1](M3-objects-serialization-3.1.md) | Terminé | `██████████` 100 % | 22 / 22 |
| [M4 — Adaptateur 3.0](M4-adapter-3.0.md) | Terminé | `██████████` 100 % | 23 / 23 |
| [M5 — Adaptateur 3.2](M5-adapter-3.2.md) | Terminé | `██████████` 100 % | 16 / 16 |
| [M6 — Validation](M6-validation.md) | En cours | `█████████░` 94 % | 17 / 18 |
| [M7 — Version 1.0.0](M7-release-1.0.md) | À faire | `░░░░░░░░░░` 0 % | 0 / 8 |
<!-- progress:end -->

## Mettre à jour la progression

1. Cocher le livrable dans le document du jalon (`- [ ]` devient `- [x]`).
2. Lancer `composer progress` : le tableau ci-dessus est régénéré à partir des cases cochées.
3. Committer les deux fichiers ensemble. La CI (`php bin/progress.php --check`) échoue si le tableau n'est pas à jour.

Le statut est déduit des cases : aucune cochée = **À faire**, une partie = **En cours**, toutes = **Terminé**.

## Ordre des jalons

Les tests sont écrits avant le code : M2 se termine avec une suite complète et rouge, puis chaque jalon suivant
en fait passer une partie au vert.

1. [M0 — Dépôt et outillage](M0-bootstrap.md)
2. [M1 — Contrat d'API](M1-api-contract.md)
3. [M2 — Suite de tests complète](M2-tests.md)
4. [M3 — Objets et sérialisation 3.1](M3-objects-serialization-3.1.md)
5. [M4 — Adaptateur 3.0](M4-adapter-3.0.md)
6. [M5 — Adaptateur 3.2](M5-adapter-3.2.md)
7. [M6 — Validation](M6-validation.md)
8. [M7 — Version 1.0.0](M7-release-1.0.md)

## Décisions

| Date | Décision |
| --- | --- |
| 2026-10-27 | Abandon de `goldspecdigital/oooas` (aucune release depuis juillet 2022, 3.0 uniquement) au profit d'une librairie autonome, sans framework |
| 2026-10-27 | `zircote/swagger-php` écarté comme modèle objet |
| 2026-10-27 | Paquet `ark4ne/openapi-writer`, espace de noms `Ark4ne\OpenApiWriter\`, licence MIT |
| 2026-10-27 | Versions OpenAPI 3.0.0 → 3.0.4, 3.1.0 → 3.1.2 et 3.2.0 ; PHP `^8.2` |
| 2026-10-27 | Version par défaut 3.1.2 ; mode strict par défaut, mode permissif en option |
| 2026-10-27 | `Tag.parent` retiré avant 3.2, sans conversion en `x-tagGroups` |
| 2026-10-27 | Validation JSON Schema : `opis/json-schema` comme candidat, à confirmer au M2 |
| 2026-10-27 | Lecture des champs par `__get` + `@property-read` (comme GoldSpec), conservée pour le moment |
| 2026-10-27 | Listes et maps : setter qui remplace + méthode `add<Champ>()` qui ajoute, toutes deux renvoient un clone |
| 2026-10-27 | Champs déclarés par attributs PHP `#[Field(...)]` sur les propriétés (et non par une méthode `fields()`) |
| 2026-10-27 | Pas de classes d'adaptateur par version : `since` / `until` déclaratifs + une chaîne de rewriters (voir [architecture.md](architecture.md)) |
| 2026-10-27 | `VersionedEnum` dans `Enums/` (règle de dépendance) ; `#[Field(patterned: true)]` pour les champs « patterned » |
| 2026-10-27 | `Schema::ref()` renvoie un `Schema` portant `$ref` (champs voisins et `nullable()` possibles) ; les autres `::ref()` renvoient un `Reference` |
| 2026-10-27 | Version déclarée par le champ `OpenApi::openapi()` (et non `OpenApi::create(Version)`, dont l'argument est l'`objectId`) |
| 2026-10-27 | Ordre des mots-clés de `Schema` : celui de la sortie GoldSpec, ajouts 2020-12 placés dans leur famille (snapshots du générateur) |
