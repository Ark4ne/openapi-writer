# M5 — Adaptateur 3.2

**Objectif.** Écrire le modèle en OpenAPI 3.2.0 avec toutes ses nouveautés, et les rejeter proprement quand la
cible est 3.0 ou 3.1.

**Dépend de :** M3 (M4 n'est pas bloquant).
**Spécification :** sections 5 et 6. **Architecture :** [§2.3 et §2.4](architecture.md#23-métadonnées-déclaratives--field) — chaque nouveauté est déclarée par `since: Version::V3_2_0` sur le champ ou le cas d'enum.

## Nouveautés 3.2 (sortie native en 3.2, non exprimables en 3.0 et 3.1)

- [x] `OpenApi.$self`
- [x] `components.mediaTypes`
- [x] `Server.name`
- [x] Méthode `QUERY` et `PathItem.additionalOperations`
- [x] Paramètre `in: querystring`
- [x] `Tag.summary`, `Tag.parent`, `Tag.kind` (`parent` retiré avant 3.2, pas de conversion en `x-tagGroups`)
- [x] Flux séquentiels : `MediaType.itemSchema`, `prefixEncoding`, `itemEncoding` ; `Encoding.encoding`,
      `prefixEncoding`, `itemEncoding`
- [x] `MediaType.description`
- [x] `Example.dataValue` et `Example.serializedValue`
- [x] `Discriminator.defaultMapping`
- [x] `Xml.nodeType`
- [x] Flux OAuth2 `deviceAuthorization` et `OAuthFlow.deviceAuthorizationUrl`
- [x] `SecurityScheme.oauth2MetadataUrl` et `SecurityScheme.deprecated`
- [x] `Response.summary`, et `Response.description` facultative (omise en 3.2, `""` avant)

## Critères de sortie

- [x] Matrice de versions verte pour 3.2.0, et pour le rejet de chaque nouveauté en 3.0 et 3.1
- [x] Conformité 3.2 verte, documents validés contre le schéma officiel 3.2

## Notes de réalisation

- Les nouveautés étaient déjà déclarées par `since: Version::V3_2_0` (champs) et `VersionedEnum::since()` (cas
  `query`, `querystring`, style `cookie`) depuis M1, et écrites ou rejetées par le `Normalizer` depuis M3 : la
  matrice 3.2 et les rejets en 3.0 / 3.1 étaient verts à l'ouverture du jalon. Les deux seuls rewriters propres à 3.2
  (`ResponseDescriptionRewriter`, `MediaTypeReferenceRewriter`) sont livrés depuis M3.
- Ajouté au jalon, à partir des contraintes du schéma officiel 3.2 que le modèle laissait passer :
  - `Example` : `value` exclut `dataValue` et `serializedValue`, `serializedValue` exclut `externalValue` ;
  - `MediaType` et `Encoding` : `encoding` exclut `prefixEncoding` et `itemEncoding` ;
  - `Xml` : `nodeType` exclut `attribute` et `wrapped`, et n'accepte que les cinq valeurs de la spec ;
  - `PathItem.additionalOperations` refuse une méthode fixe, quelle que soit sa casse.
- `Xml.nodeType` n'est pas converti en `attribute` / `wrapped` avant 3.2 : la correspondance n'existe que pour
  certains cas (`attribute`, tableaux), la ligne `xml-node-type` reste un rejet.
- Restent rouges : la suite Validation (M6).
