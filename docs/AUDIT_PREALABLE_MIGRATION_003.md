# Audit préalable de la migration 003 — Cycle de vie des marchés

## 1. Périmètre et méthode

Audit strictement en lecture seule. Aucune migration exécutée, aucune instruction SQL modificative lancée, aucun fichier de code modifié. Seules des requêtes de lecture (`SELECT`, `SHOW`, `INFORMATION_SCHEMA`) ont été utilisées. La base `erp_manager` est en version MariaDB 10.4.32.

## 2. Résultat des contrôles

### Contrôle 1 — Examen des migrations 001, 002 et 003
- `001_create_demo_tables.sql` : crée `companies` et `markets` (5 sociétés et 8 marchés de démonstration), contraintes CHECK sur `status` (5 valeurs) et `phase` (1 à 6).
- `002_create_auth_tables.sql` : crée `users`, `user_sessions`, `permissions`, `user_company_restrictions`, `user_permission_restrictions`, et insère les permissions dont `markets.view/create/update/delete`, ainsi que les comptes `gest`, `secr`, `chad`.
- `003_market_lifecycle.sql` : migration préparée, non exécutée. Base actuelle : 7 tables (001 + 002), aucune colonne `lifecycle_*`, aucune table `market_transitions`. Confirmé par `SHOW TABLES` et `INFORMATION_SCHEMA`.

### Contrôle 2 — Types exacts des clés primaires et étrangères
- `markets.id` : `INT UNSIGNED` PK.
- `users.id` : `INT UNSIGNED` PK.
- `companies.id` : `INT UNSIGNED` PK.
- `markets.company_id` : `INT UNSIGNED` FK → `companies.id` (`ON UPDATE CASCADE ON DELETE RESTRICT`).
- Dans la migration 003 : `market_transitions.market_id` `INT UNSIGNED` FK → `markets.id` ; `changed_by` `INT UNSIGNED NULL` FK → `users.id`. Les types correspondent exactement. Aucun risque de rejet pour incompatibilité de type.

### Contrôle 3 — Contraintes, index et colonnes existantes
- `markets` : PK `id`, UNIQUE `uq_markets_reference (reference)`, index `idx_markets_company`, `idx_markets_status`, `idx_markets_phase`, FK `fk_markets_company`, CHECK `status` (5 valeurs), `phase` (1–6), `amount >= 0`, `is_demo` (0/1).
- `users` : PK `id`, UNIQUE `username`, index `role,is_active`, CHECK `role`, `must_change_password`, `is_active`.
- Aucune colonne `lifecycle_status`, `lifecycle_phase`, `decision_date`, etc. n'existe actuellement.

### Contrôle 4 — Lecture des marchés et tableau de conversion
Voir tableau § 3.

### Contrôle 5 — Anciens statuts particuliers
- `gagne` → `a_qualifier` (phase 5 conservée) ; GEST devra confirmer date et montant attribué.
- `perdu_annule` → `a_qualifier` (phase 5) ; GEST devra qualifier (perdu / annulé / infructueux).
- `cloture` → `cloture` (phase 8).
- `en_attente` et `en_cours` → `en_cours` (phase convertie selon la table).

### Contrôle 6 — Conversion des phases 1 à 6 vers 8
Correspondance confirmée par la documentation et la migration (voir § 4).

### Contrôle 7 — Contraintes CHECK et risques de rejet
- `chk_markets_lifecycle_status` : 7 valeurs (`en_cours`, `attribue`, `perdu`, `annule`, `infructueux`, `cloture`, `a_qualifier`). Les lignes migrées reçoivent `en_cours`, `cloture` ou `a_qualifier` : aucune violation.
- `chk_markets_lifecycle_phase` : 1 à 8. Les phases converties sont dans cette plage.
- `chk_markets_decision_data` : exige des champs pour `perdu` (entreprise concurrente, montant, observations), `annule`/`infructueux` (motif) et `attribue` (montant et date). Les lignes migrées sont `a_qualifier`/`en_cours`/`cloture`, aucune ne déclenche ces exigences. Pas de rejet attendu.
- `market_transitions` : `to_phase` 1–8, `from_phase` NULL ou 1–8. Les insertions de migration utilisent `from_phase = NULL` et `to_phase` dans la plage. OK.
- MariaDB 10.4.32 applique les contraintes CHECK (famille 10.2.1+). Le script cible bien cette famille.

### Contrôle 8 — Création de `market_transitions`
- FK `fk_market_transitions_market` : `market_id` → `markets.id`, `ON DELETE RESTRICT`. Types correspondants.
- FK `fk_market_transitions_user` : `changed_by` → `users.id`, `ON DELETE RESTRICT`. Colonne nullable (NULL autorisé pour les lignes de migration).
- Index `idx_market_transitions_market_date (market_id, changed_at)` et `idx_market_transitions_user (changed_by)`.
- Risque : absence de contrainte d'unicité → une exécution multiple insérerait des doublons. La migration n'est pas idempotente.

### Contrôle 9 — Perte d'information, incohérence, ambiguïté métier
- `perdu_annule` ne distingue pas perdu / annulé / infructueux → placé en `a_qualifier` (intervention GEST requise). Les valeurs sources sont conservées dans `legacy_status`/`legacy_phase` et dans les anciennes colonnes.
- `gagne` ne fournit pas la date de décision ni le montant attribué → placé en `a_qualifier` (phase 5). GEST devra compléter.
- Anciennes phases 2 et 3 sont réordonnées (montage dossier avant chiffrage) selon la signification historique confirmée.
- Ancienne phase 6 est toujours convertie en `cloture` / phase 8, quel que soit l'ancien statut, car son libellé historique est « Clôture & historique ».
- Aucune ligne n'est supprimée ; les anciennes colonnes restent disponibles pour contrôle.

### Contrôle 10 — Risque lié à l'unicité globale de la référence
- `uq_markets_reference` porte uniquement sur `reference` (pas sur `company_id, reference`). Deux marchés de sociétés différentes ne peuvent pas partager la même référence. La migration 003 ne modifie pas cette contrainte.
- Présence de données de test (`SSSSSS`, `M-TEST-001`) qui occupent des références globales.

## 3. Tableau des marchés et conversions proposées

| # | Référence | Ancien statut | Ancienne phase | Nouveau statut proposé | Nouvelle phase proposée |
|---|---|---|---|---|---|
| 1 | M-2026-008 | en_attente | 1 | en_cours | 1 |
| 2 | M-2026-007 | en_cours | 2 | en_cours | 3 |
| 3 | M-2026-006 | en_cours | 3 | en_cours | 2 |
| 4 | M-2026-005 | gagne | 5 | a_qualifier | 5 |
| 5 | M-2026-004 | en_attente | 2 | en_cours | 3 |
| 6 | M-2026-003 | en_cours | 4 | en_cours | 4 |
| 7 | M-2026-002 | cloture | 6 | cloture | 8 |
| 8 | M-2026-001 | perdu_annule | 5 | a_qualifier | 5 |
| 9 | SSSSSS | en_cours | 1 | en_cours | 1 |
| 10 | M-TEST-001 | en_attente | 1 | en_cours | 1 |

## 4. Correspondance des phases (rappel)

| Ancienne phase | Signification historique | Nouvelle phase |
|---:|---|---:|
| 1 | Repérage & DCE | 1 — Préparation |
| 2 | Montage dossier | 3 — Préparation de la soumission |
| 3 | Chiffrage & offre | 2 — Étude et BPU |
| 4 | Dépôt & soutenance | 4 — Soumis |
| 5 | Attribution / Verdict | 5 — Attente du résultat |
| 6 | Clôture & historique | 8 — Clôturé / archivé |

## 5. Anomalies classées par gravité

### Majeures
- **A1** — La migration n'est pas idempotente : une double exécution provoquerait l'échec de `ALTER TABLE ADD COLUMN` et des doublons dans `market_transitions`. Exécution unique obligatoire.
- **A2** — Aucune sauvegarde automatique dans le script ; DDL avec validations implicites (pas de ROLLBACK global).
- **A3** — Deux marchés seront placés en `a_qualifier` (M-2026-005 et M-2026-001) et nécessiteront une qualification manuelle par GEST avant toute utilisation métier.

### Moyennes
- **A4** — Branche morte dans le `CASE` de `lifecycle_phase` (`WHEN phase = 6 THEN 5` après `WHEN phase = 6 THEN 8`). Sans effet (évaluation descendante), mais à nettoyer pour la lisibilité.
- **A5** — Unicité globale de la référence (`uq_markets_reference`) sans tenir compte de la société ; risque de collision inter-sociétés.
- **A6** — Absence d'index sur `lifecycle_status` / `lifecycle_phase` dans la migration ; impact performance futur à surveiller.

### Mineures
- **A7** — Présence de marchés de test (`SSSSSS`, `M-TEST-001`) dans la base, créés lors de la validation de l'étape 1.
- **A8** — Libellés de phases divergents entre `markets.php` et `dashboard.js` avant harmonisation (déjà traité en étape 1 pour les constantes, mais l'affichage reste sur les anciennes valeurs tant que la migration n'est pas appliquée).

## 6. Corrections SQL recommandées (non exécutées)

1. Supprimer la branche morte du `CASE` (`WHEN phase = 6 THEN 5`) pour la lisibilité.
2. Prévoir une sauvegarde complète de la base avant exécution (`mysqldump`).
3. Étudier, dans une migration future distincte, le passage de l'unicité de `reference` à une unicité composée `(company_id, reference)` après vérification des données existantes.
4. Ajouter, si nécessaire, des index sur `lifecycle_status` et `lifecycle_phase` après la mise en service.
5. Documenter et contrôler l'exécution unique (vérification préalable de l'absence des colonnes `lifecycle_*`).
6. Prévoir la qualification GEST des lignes `a_qualifier` avant tout basculement des écrans vers les nouvelles colonnes.

## 7. Verdict final

**VALIDÉE SOUS RÉSERVES**

La migration est techniquement compatible avec le schéma et les données actuels : types cohérents, contraintes CHECK satisfaites par les valeurs converties, aucune perte de données irréversible (les sources sont conservées). Toutefois, les réserves suivantes doivent être levées avant exécution :
- sauvegarde complète de la base ;
- plan de qualification GEST pour les dossiers `a_qualifier` ;
- exécution unique, sans rerun ;
- nettoyage éventuel des données de test si souhaité.

Aucune opération SQL modificative ni migration n'a été exécutée dans le cadre de cet audit. La qualification GEST et toute autre fonctionnalité attendent la validation explicite.
