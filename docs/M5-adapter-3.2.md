# M5 — Adaptateur 3.2

**Objectif.** Écrire le modèle en OpenAPI 3.2.0 avec toutes ses nouveautés, et les rejeter proprement quand la
cible est 3.0 ou 3.1.

**Dépend de :** M3 (M4 n'est pas bloquant).
**Spécification :** sections 5 et 6. **Architecture :** [§2.3 et §2.4](architecture.md#23-métadonnées-déclaratives--field) — chaque nouveauté est déclarée par `since: Version::V3_2_0` sur le champ ou le cas d'enum.

## Nouveautés 3.2 (sortie native en 3.2, non exprimables en 3.0 et 3.1)

- [ ] `OpenApi.$self`
- [ ] `components.mediaTypes`
- [ ] `Server.name`
- [ ] Méthode `QUERY` et `PathItem.additionalOperations`
- [ ] Paramètre `in: querystring`
- [ ] `Tag.summary`, `Tag.parent`, `Tag.kind` (`parent` retiré avant 3.2, pas de conversion en `x-tagGroups`)
- [ ] Flux séquentiels : `MediaType.itemSchema`, `prefixEncoding`, `itemEncoding` ; `Encoding.encoding`,
      `prefixEncoding`, `itemEncoding`
- [ ] `MediaType.description`
- [ ] `Example.dataValue` et `Example.serializedValue`
- [ ] `Discriminator.defaultMapping`
- [ ] `Xml.nodeType`
- [ ] Flux OAuth2 `deviceAuthorization` et `OAuthFlow.deviceAuthorizationUrl`
- [ ] `SecurityScheme.oauth2MetadataUrl` et `SecurityScheme.deprecated`
- [ ] `Response.summary`, et `Response.description` facultative (omise en 3.2, `""` avant)

## Critères de sortie

- [ ] Matrice de versions verte pour 3.2.0, et pour le rejet de chaque nouveauté en 3.0 et 3.1
- [ ] Conformité 3.2 verte, documents validés contre le schéma officiel 3.2
