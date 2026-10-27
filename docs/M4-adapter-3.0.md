# M4 — Adaptateur 3.0

**Objectif.** Écrire le même modèle en OpenAPI 3.0.x : conversions automatiques quand le sens est préservé, rejet
(strict) ou retrait avec diagnostic (permissif) sinon.

**Dépend de :** M3.
**Spécification :** section 6. **Architecture :** [§3](architecture.md#3-la-sérialisation) — chaque conversion = un `Rewriter`, chaque fonctionnalité non exprimable = un `since` sur le champ.

## Conversions automatiques

- [x] `nullable()` avec un type → `type: X` + `nullable: true`
- [x] `nullable()` sur un `$ref` ou sans type → `anyOf: [{$ref}, {type: object, nullable: true, enum: [null]}]`
- [x] `Schema::null()` → `type: object` + `nullable: true` + `enum: [null]`
- [x] `exclusiveMinimum(n)` / `exclusiveMaximum(n)` → `minimum: n` + `exclusiveMinimum: true` (idem pour le maximum)
- [x] `const(v)` → `enum: [v]`
- [x] `examples(a, b)` sur un `Schema` → `example: a`
- [x] Plusieurs types non nuls → `anyOf` d'un schéma par type
- [x] `$ref` + champs voisins → `allOf: [{$ref}]` + champs voisins
- [x] `contentMediaType` binaire → `format: binary`
- [x] `contentEncoding: base64` → `format: byte`
- [x] `Response` sans description → `description: ""`
- [x] Aucun chemin → `paths: {}`
- [x] Sous-schéma booléen (`items: false`, `not: true`…) → `{}` / `{not: {}}`

## Non exprimables en 3.0

- [x] `webhooks`, `jsonSchemaDialect`, `components.pathItems`
- [x] `info.summary`, `license.identifier`
- [x] `summary` et `description` sur un `Reference` (`$ref` sur un `PathItem` existe déjà en 3.0 : écrit tel quel)
- [x] Mots-clés JSON Schema 2020-12 sans équivalent : `prefixItems`, `contains`, `minContains`, `maxContains`,
      `if`/`then`/`else`, `$defs`, `$id`, `$anchor`, `$dynamicAnchor`, `$dynamicRef`, `$schema`, `$comment`,
      `unevaluatedItems`, `unevaluatedProperties`, `dependentRequired`, `dependentSchemas`, `patternProperties`,
      `propertyNames`, `contentSchema`
- [x] Type de sécurité `mutualTLS`, scopes sur un schéma de sécurité non OAuth2
- [x] Toutes les fonctionnalités propres à 3.2 (liste dans M5)

## Modes

- [x] Strict : une seule `UnsupportedFeatureException` qui liste toutes les fonctionnalités, avec leur JSON Pointer
- [x] Permissif : retrait + `Diagnostic` (niveau, pointeur, message) pour chaque fonctionnalité

## Critères de sortie

- [x] Matrice de versions verte pour 3.0.0 à 3.0.4
- [x] Conformité 3.0 verte, documents validés contre le schéma officiel 3.0

## Notes de réalisation

- Toutes les branches 3.0 tiennent dans les rewriters existants ; aucun `since` n'a été ajouté au modèle.
- Ordre des clés : le schéma « null seul » de 3.0 s'écrit `{enum: [null], type: object, nullable: true}`, dans
  l'ordre des champs comme tout autre nœud (le `Normalizer` trie après les rewriters). Les fixtures 3.0 qui
  l'écrivaient `type`, `nullable`, `enum` ont été réordonnées, et `anyOf` utilise la même forme
  (`NullTypeRewriter::NULL_SCHEMA`).
- Une conversion n'est signalée qu'une fois : avec plusieurs types, c'est `MultiTypeRewriter` qui le signale (et non
  `NullableRewriter` ou `NullTypeRewriter`) ; avec `contentEncoding`, c'est `ContentEncodingRewriter`.
- Cas limites fixés par de nouvelles lignes de la matrice :
  - `type('string', 'null')` → `type: string` + `nullable: true` (`null-in-the-type-list`) ;
  - `const` à côté d'un `enum` → `enum: [v]`, la constante est la plus stricte (`const-in-an-enum`) ;
  - `example` à côté d'`examples` → l'`example` est gardé (`schema-examples-with-example`) ;
  - borne exclusive à côté d'une borne inclusive → la plus stricte des deux est gardée
    (`exclusive-and-inclusive-bounds`) ;
  - plusieurs types à côté d'un `anyOf` → `allOf: [{anyOf: types}]` + l'`anyOf` d'origine (`multiple-types-with-any-of`) ;
  - `$ref` à côté d'un `allOf` → la référence passe en tête de l'`allOf` (`ref-with-siblings-and-all-of`) ;
  - `not: true` / `not: false` → `{not: {}}` / `{not: {not: {}}}` (`boolean-not`).
- Non exprimables ajoutés : un `contentEncoding` autre que `base64` (`content-encoding`) ; les scopes hors OAuth2
  sont aussi cherchés dans les opérations des callbacks (`non-oauth-scopes-in-a-callback`).
- Un `format` déjà défini l'emporte sur `binary` / `byte`.
- Revue de code appliquée :
  - `NullableWithoutTypeRewriter` passe après les autres rewriters de `Schema` : la branche de l'`anyOf` reçoit des
    mots-clés déjà écrits pour 3.0 (`nullable-without-type-with-conversions`), triés dans l'ordre des champs ;
  - un `$ref` nullable avec des contraintes voisines voit sa branche enveloppée dans un `allOf`
    (`nullable-on-a-ref-with-constraints`) ;
  - les scopes hors OAuth2 sont aussi cherchés dans `components.callbacks`
    (`non-oauth-scopes-in-a-component-callback`) ;
  - un `contentMediaType` dont l'encodage n'est pas `base64` est non exprimable
    (`content-media-type-with-encoding`) ;
  - un type `null` seul garde un `enum` sans `null`, qui n'accepte rien dans toutes les versions
    (`null-type-with-enum`) ;
  - `const(null)` sans type devient le schéma « null seul » (`const-null`), et une constante hors de l'`enum` est
    non exprimable (`const-outside-the-enum`).
- Restent rouges : la suite Validation (M6).
