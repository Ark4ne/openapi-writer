# M6 — Validation

**Objectif.** `Validator::validate($openApi, Version)` renvoie la liste des `Violation` (JSON Pointer, règle,
message), sans jamais lever d'exception sur un document invalide.

**Dépend de :** M3, M4, M5.
**Spécification :** section 9.

## Niveau 1 — schéma officiel

- [ ] Validation contre le schéma officiel 3.0 (draft 4)
- [ ] Validation contre les schémas officiels 3.1 et 3.2 (draft 2020-12)
- [ ] Librairie JSON Schema en dépendance suggérée ; exception explicite si elle est absente

## Niveau 2 — règles sémantiques (une classe par règle)

- [ ] `operationId` unique dans le document
- [ ] Chaque variable `{x}` d'un chemin a un paramètre `in: path` obligatoire correspondant
- [ ] Pas de doublon `name` + `in` dans les paramètres d'une opération
- [ ] Toute référence locale `#/...` pointe sur un élément existant
- [ ] Toute exigence de sécurité nomme un `securityScheme` déclaré ; les scopes OAuth2 existent dans le flux
- [ ] Tags utilisés déclarés (avertissement) ; en 3.2, `Tag.parent` existe et sans cycle
- [ ] Chemins équivalents à un nom de variable près signalés
- [ ] En 3.1 et plus, au moins `paths`, `components` ou `webhooks`
- [ ] `default` et `example` conformes au type du schéma (avertissement)
- [ ] Règles activables et désactivables, ajout de règles personnalisées

## Critères de sortie

- [ ] Suite Validation verte
- [ ] Couverture de lignes ≥ 95 % sur l'ensemble du projet
- [ ] MSI (Infection) ≥ 85 %
