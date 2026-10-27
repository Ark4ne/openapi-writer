# M3 — Objets et sérialisation 3.1

**Objectif.** Implémenter le modèle objet et sa sérialisation vers OpenAPI 3.1. On commence par 3.1, la version la
plus proche du modèle (JSON Schema 2020-12).

**Dépend de :** M2.
**Spécification :** sections 4, 5, 7 et 8.

## Livrables

### Modèle

- [ ] `SpecObject::with()`, `withAdded()`, `without()`, `has()`, `__get` ; `MetadataFactory` (lecture des `#[Field]`, cache par classe)
- [ ] Immuabilité : chaque méthode renvoie un clone modifié, l'original ne change jamais
- [ ] Méthodes `add*` sur toutes les listes et maps (ajout en fin, doublon d'`objectId` refusé)
- [ ] Sentinelle interne « non défini », distincte de `null` (`default(null)`, `example(null)`, `const(null)`)
- [ ] `objectId` : clé de rangement dans le parent (propriété, composant, code de statut, chemin)
- [ ] Constructeurs sémantiques (`Schema::string()`, `Operation::get()`, `Parameter::path()`, `Response::ok()`,
      `MediaType::json()`…)
- [ ] Extensions `x-*` sur tous les objets extensibles, préfixes `x-oai-` et `x-oas-` refusés
- [ ] Exceptions immédiates sur les valeurs que la spec interdit dans toutes les versions
- [ ] Retrait de l'exclusion PHPStan `throws.unusedType` posée au M1 (`phpstan.neon.dist`)

### Références et composants

- [ ] `Reference::to()`, raccourcis `::ref()` typés, `Reference::for($object)`
- [ ] `Components` : rangement par `objectId`, exception sur doublon dans une même section
- [ ] Surcharges `summary` et `description` sur `Reference`

### Sérialisation 3.1

- [ ] `Normalizer` : parcours de tous les objets et champs, JSON Pointer, contrôle `since` / `until` et `VersionedEnum`
- [ ] `RewriterChain` et rewriters qui produisent la sortie 3.1 (`nullable` → `type: [X, "null"]`…)
- [ ] `Diagnostics` : mode strict (une exception avec tous les cas) et mode permissif (retrait + diagnostic)
- [ ] Ordre des clés : ordre des champs de la spec, ordre d'insertion pour les maps
- [ ] Maps vides écrites `{}`, `security: [{}]`
- [ ] `toArray()`, `toJson()` (flags par défaut, `JSON_PRETTY_PRINT` en option), `JsonSerializable`
- [ ] `toYaml()` via `symfony/yaml`, exception explicite s'il est absent
- [ ] Rejet en 3.1 des fonctionnalités propres à 3.2 (le détail des cas est dans M5)

## Critères de sortie

- [ ] Suites Objets, Références, Sérialisation 3.1 et Conformité 3.1 vertes
- [ ] Couverture de lignes ≥ 95 % sur le code livré
