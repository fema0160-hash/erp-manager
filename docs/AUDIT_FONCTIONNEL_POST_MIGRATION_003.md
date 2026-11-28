Référence : erp-manager/docs/AUDIT_FONCTIONNEL_POST_MIGRATION_003.md

# Audit fonctionnel complet après migration 003 — ERP Manager

## 1. Périmètre et méthode

Audit strictement non destructif. Aucune donnée de la base principale n'a été modifiée, aucun marché ni utilisateur supprimé, aucune permission réelle modifiée, aucune migration supplémentaire exécutée. Les tests de comportement nécessitant des données ont été évalués par revue de code et par consultation de la base ; aucune simulation sur la base principale.

Note chronologique : ce rapport décrit l'état après la migration 003 et avant la suppression des marchés de démonstration `M-2026-005` et `M-2026-001` (10 marchés présents à ce moment). État actuel : 8 marchés (voir `SUPPRESSION_MARCHES_DEMO.md`).

## 2. Audit du cycle de vie (8 phases)

| Phase | Libellé attendu | Présent dans `MarketLifecycle::PHASES` | Présent côté JS (dynamique) | Résultat |
|---:|---|---|---|---|
| 1 | Préparation | Oui | Oui | CONFORME |
| 2 | Étude et BPU | Oui | Oui | CONFORME |
| 3 | Préparation de la soumission | Oui | Oui | CONFORME |
| 4 | Soumis | Oui | Oui | CONFORME |
| 5 | Attente du résultat | Oui | Oui | CONFORME |
| 6 | Préparation du marché attribué | Oui | Oui | CONFORME |
| 7 | Exécution / suivi du chantier | Oui | Oui | CONFORME |
| 8 | Clôturé / archivé | Oui | Oui | CONFORME |

Ordre numérique respecté (1 à 8). Titre du tableau de bord dynamique (6 ou 8 phases selon l'état de migration). Résultat global : CONFORME.

## 3. Audit des statuts

| Statut | Présent dans `MarketLifecycle::STATUSES` | Contrainte SQL CHECK | Résultat |
|---|---|---|---|
| en_cours | Oui | Oui | CONFORME |
| attribue | Oui | Oui | CONFORME |
| perdu | Oui | Oui | CONFORME |
| annule | Oui | Oui | CONFORME |
| infructueux | Oui | Oui | CONFORME |
| cloture | Oui | Oui | CONFORME |
| a_qualifier | Oui | Oui | CONFORME |

Contrôles métier prévus par le schéma :
- Attribution : `awarded_amount` et `decision_date` obligatoires (CHECK) — CONFORME au schéma.
- Perdu : `winner_company_name`, `winner_amount`, `decision_observations` obligatoires (CHECK) — CONFORME au schéma.
- Annulé / infructueux : `decision_reason` obligatoire (CHECK) — CONFORME au schéma.
- Qualification : réservée à GEST (à implémenter côté application).
- Réouverture : réservée à GEST avec motif (à implémenter côté application).

Résultat : CONFORME pour le schéma ; les contrôles applicatifs correspondants ne sont pas encore implémentés (voir § 5).

## 4. Audit des transitions

| Contrôle | Résultat | Commentaire |
|---|---|---|
| Transitions autorisées implémentées dans l'application | NON TESTÉ | Aucun endpoint de transition n'est implémenté à ce stade (CRUD préparé mais non activé). |
| Blocage des transitions interdites | NON TESTÉ | Idem. |
| Interdiction de sauter des phases | NON TESTÉ | Idem. |
| Préparation du marché attribué après validation | NON TESTÉ | Idem. |
| Clôture et archivage | NON TESTÉ | Idem. |
| Réouverture exceptionnelle par GEST | NON TESTÉ | Idem. |
| Enregistrement dans `market_transitions` | CONFORME (schéma) | Table créée, FK et index présents ; écriture applicative à implémenter. |

Fichiers concernés : `public/index.php`, `app/MarketLifecycle.php`, `database/migrations/003_market_lifecycle.sql`.

## 5. Audit des permissions

| Rôle | Accès vérifié | Résultat |
|---|---|---|
| GEST | Connexion, tableau de bord, marchés, utilisateurs et droits | CONFORME |
| SECR | Connexion, tableau de bord, marchés (selon restrictions) | CONFORME |
| CHAD | Connexion, tableau de bord, marchés (selon restrictions) | CONFORME |

- `hasCompanyAccess` et `hasPermission` appliqués dans `marches` et le tableau de bord.
- GEST contourne les restrictions par conception (documenté).
- Contrôles côté serveur présents pour `markets.view` et `markets.create`.
- `markets.update` et `markets.delete` : préparés dans la structure mais non activés (pas de contournement possible à ce stade car aucune fonction d'écriture n'est exposée).

Résultat : CONFORME pour les contrôles existants ; les contrôles avancés (qualification, réouverture) sont à implémenter.

## 6. Audit de l'interface

| Contrôle | Résultat | Commentaire |
|---|---|---|
| Liste des marchés | CONFORME | 10 marchés affichés à ce moment (8 après suppression des deux marchés de démonstration), aucun message d'erreur. |
| Fiche détaillée d'un marché | NON TESTÉ | Aucune route `marche_detail` implémentée. |
| Formulaires de modification | NON TESTÉ | Aucun formulaire d'édition activé. |
| Affichage des phases et statuts | CONFORME | Libellés centralisés, affichage correct. |
| Historique | NON TESTÉ | Aucune vue d'historique implémentée. |
| Messages d'erreur et de validation | CONFORME | Aucune erreur PHP/SQL observée. |
| Tableau de bord | CONFORME | Chargement OK, `ERP_MARKET_MIGRATED = true`. |
| Filtres par société | CONFORME | Filtrage par entreprise accessible. |
| Affichage mobile | NON TESTÉ | Non vérifié dans le cadre de cet audit. |
| Régression visuelle | CONFORME | Aucune régression observée sur les pages principales. |

## 7. Audit multi-sociétés

| Contrôle | Résultat | Commentaire |
|---|---|---|
| Isolation des données entre sociétés | CONFORME (code) | Filtrage par `allowedCompanyIds` dans `marches` et le tableau de bord. |
| Accès SECR/CHAD à une autre société sans autorisation | CONFORME (code) | `hasCompanyAccess` bloque l'accès. |
| Respect du contexte société | CONFORME | Filtre par entreprise appliqué. |
| Filtres pour utilisateurs autorisés | CONFORME | Testé pour GEST, SECR, CHAD. |

Aucune permission réelle n'a été modifiée.

## 8. Contrôle technique

| Contrôle | Résultat | Commentaire |
|---|---|---|
| Erreurs PHP | CONFORME | Aucune erreur dans les journaux après migration. |
| Erreurs JavaScript | CONFORME | `node --check` OK ; tableau de bord fonctionnel. |
| Erreurs SQL | CONFORME | Aucune erreur lors du chargement des marchés. |
| Requêtes et jointures liées au cycle de vie | CONFORME | `SELECT m.*` compatible migré/non migré. |
| Contrôles de sécurité côté serveur | CONFORME | CSRF, sessions, permissions. |
| Cohérence anciennes/nouvelles colonnes | CONFORME | `MarketLifecycle::lifecycleStatus` / `lifecyclePhase` avec détection. |
| Compatibilité MariaDB 10.4.32 | CONFORME | Contraintes CHECK appliquées, migration réussie. |

## 9. Éléments non testés et raisons

- Transitions applicatives, fiches détaillées, formulaires d'édition, historique, affichage mobile : non testés car les fonctionnalités correspondantes ne sont pas encore implémentées (seule l'étape 1 a été réalisée). Aucune anomalie de régression n'est attendue.

## 10. Fichiers concernés

- `app/MarketLifecycle.php`
- `public/index.php`
- `resources/views/markets.php`
- `resources/views/dashboard.php`
- `public/assets/js/dashboard.js`
- `database/migrations/003_market_lifecycle.sql`
- `docs/EXECUTION_MIGRATION_003.md`

## 11. Recommandations

1. Implémenter les endpoints de transition de phase et de décision (étape suivante).
2. Implémenter la fiche détaillée et l'édition (`markets.update`).
3. Implémenter la consultation de l'historique `market_transitions`.
4. Qualifier par GEST les dossiers `a_qualifier` (M-2026-005, M-2026-001) avec justificatifs. Note : ces deux marchés ont été supprimés définitivement (voir `SUPPRESSION_MARCHES_DEMO.md`) ; la recommandation est sans objet pour l'état actuel.
5. Ajouter des tests automatisés (PHPUnit / tests d'intégration) pour les permissions et les transitions.

## 12. Verdict général

**CONFORME avec réserves** — Le schéma, les données et l'affichage sont conformes après la migration 003. Les contrôles applicatifs avancés (transitions, édition, historique) ne sont pas encore implémentés et figurent comme NON TESTÉ, sans anomalie de régression.
