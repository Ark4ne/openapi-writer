# M3 — Objets et sérialisation 3.1

**Objectif.** Implémenter le modèle objet et sa sérialisation vers OpenAPI 3.1. On commence par 3.1, la version la
plus proche du modèle (JSON Schema 2020-12).

**Dépend de :** M2.
**Spécification :** sections 4, 5, 7 et 8.

## Livrables

### Modèle

- [x] `SpecObject::with()`, `withAdded()`, `without()`, `has()`, `__get` ; `MetadataFactory` (lecture des `#[Field]`, cache par classe)
- [x] Immuabilité : chaque méthode renvoie un clone modifié, l'original ne change jamais
- [x] Méthodes `add*` sur toutes les listes et maps (ajout en fin, doublon d'`objectId` refusé)
- [x] Sentinelle interne « non défini », distincte de `null` (`default(null)`, `example(null)`, `const(null)`)
- [x] `objectId` : clé de rangement dans le parent (propriété, composant, code de statut, chemin)
- [x] Constructeurs sémantiques (`Schema::string()`, `Operation::get()`, `Parameter::path()`, `Response::ok()`,
      `MediaType::json()`…)
- [x] Extensions `x-*` sur tous les objets extensibles, préfixes `x-oai-` et `x-oas-` refusés
- [x] Exceptions immédiates sur les valeurs que la spec interdit dans toutes les versions
- [x] Retrait de l'exclusion PHPStan `throws.unusedType` posée au M1 (`phpstan.neon.dist`)

### Références et composants

- [x] `Reference::to()`, raccourcis `::ref()` typés, `Reference::for($object)`
- [x] `Components` : rangement par `objectId`, exception sur doublon dans une même section
- [x] Surcharges `summary` et `description` sur `Reference`

### Sérialisation 3.1

- [x] `Normalizer` : parcours de tous les objets et champs, JSON Pointer, contrôle `since` / `until` et `VersionedEnum`
- [x] `RewriterChain` et rewriters qui produisent la sortie 3.1 (`nullable` → `type: [X, "null"]`…)
- [x] `Diagnostics` : mode strict (une exception avec tous les cas) et mode permissif (retrait + diagnostic)
- [x] Ordre des clés : ordre des champs de la spec, ordre d'insertion pour les maps
- [x] Maps vides écrites `{}`, `security: [{}]`
- [x] `toArray()`, `toJson()` (flags par défaut, `JSON_PRETTY_PRINT` en option), `JsonSerializable`
- [x] `toYaml()` via `symfony/yaml`, exception explicite s'il est absent
- [x] Rejet en 3.1 des fonctionnalités propres à 3.2 (le détail des cas est dans M5)

## Critères de sortie

- [x] Suites Objets, Références, Sérialisation 3.1 et Conformité 3.1 vertes
- [x] Couverture de lignes ≥ 95 % sur le code livré

## Notes de réalisation

- Les rewriters ne portent que leur branche 3.1 / 3.2 ; leurs branches 3.0 relèvent de M4. `ResponseDescriptionRewriter`,
  `MediaTypeReferenceRewriter` et `MultiTypeRewriter` (un seul type écrit en chaîne) sont déjà complets.
- Le `Normalizer` remet les clés dans l'ordre des champs après les rewriters (architecture §3.1).
- Ajout au contrat : `Schema::addType()`, qui refuse un type déjà présent comme `type()`.
- L'exclusion PHPStan `throws.unusedType` ne couvre plus que `src/Validation/`, encore neutre jusqu'au M6.
- Couverture de lignes du code livré (hors `Validation`) : 96,9 %. Restent rouges : les cas 3.0 (M4) et la suite
  Validation (M6).
