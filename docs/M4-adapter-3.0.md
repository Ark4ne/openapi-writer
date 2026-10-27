# M4 — Adaptateur 3.0

**Objectif.** Écrire le même modèle en OpenAPI 3.0.x : conversions automatiques quand le sens est préservé, rejet
(strict) ou retrait avec diagnostic (permissif) sinon.

**Dépend de :** M3.
**Spécification :** section 6. **Architecture :** [§3](architecture.md#3-la-sérialisation) — chaque conversion = un `Rewriter`, chaque fonctionnalité non exprimable = un `since` sur le champ.

## Conversions automatiques

- [ ] `nullable()` avec un type → `type: X` + `nullable: true`
- [ ] `nullable()` sur un `$ref` ou sans type → `anyOf: [{$ref}, {type: object, nullable: true, enum: [null]}]`
- [ ] `Schema::null()` → `type: object` + `nullable: true` + `enum: [null]`
- [ ] `exclusiveMinimum(n)` / `exclusiveMaximum(n)` → `minimum: n` + `exclusiveMinimum: true` (idem pour le maximum)
- [ ] `const(v)` → `enum: [v]`
- [ ] `examples(a, b)` sur un `Schema` → `example: a`
- [ ] Plusieurs types non nuls → `anyOf` d'un schéma par type
- [ ] `$ref` + champs voisins → `allOf: [{$ref}]` + champs voisins
- [ ] `contentMediaType` binaire → `format: binary`
- [ ] `contentEncoding: base64` → `format: byte`
- [ ] `Response` sans description → `description: ""`
- [ ] Aucun chemin → `paths: {}`
- [ ] Sous-schéma booléen (`items: false`, `not: true`…) → `{}` / `{not: {}}`

## Non exprimables en 3.0

- [ ] `webhooks`, `jsonSchemaDialect`, `components.pathItems`
- [ ] `info.summary`, `license.identifier`
- [ ] `summary` et `description` sur un `Reference` (`$ref` sur un `PathItem` existe déjà en 3.0 : écrit tel quel)
- [ ] Mots-clés JSON Schema 2020-12 sans équivalent : `prefixItems`, `contains`, `minContains`, `maxContains`,
      `if`/`then`/`else`, `$defs`, `$id`, `$anchor`, `$dynamicAnchor`, `$dynamicRef`, `$schema`, `$comment`,
      `unevaluatedItems`, `unevaluatedProperties`, `dependentRequired`, `dependentSchemas`, `patternProperties`,
      `propertyNames`, `contentSchema`
- [ ] Type de sécurité `mutualTLS`, scopes sur un schéma de sécurité non OAuth2
- [ ] Toutes les fonctionnalités propres à 3.2 (liste dans M5)

## Modes

- [ ] Strict : une seule `UnsupportedFeatureException` qui liste toutes les fonctionnalités, avec leur JSON Pointer
- [ ] Permissif : retrait + `Diagnostic` (niveau, pointeur, message) pour chaque fonctionnalité

## Critères de sortie

- [ ] Matrice de versions verte pour 3.0.0 à 3.0.4
- [ ] Conformité 3.0 verte, documents validés contre le schéma officiel 3.0
