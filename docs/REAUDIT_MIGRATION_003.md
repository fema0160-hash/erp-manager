Référence : erp-manager/docs/REAUDIT_MIGRATION_003.md

# Ré-audit de la migration 003 — après correction

Note chronologique : ce rapport décrit l'état avant l'exécution de la migration 003 sur la base principale. La migration a ensuite été exécutée avec succès (voir `EXECUTION_MIGRATION_003.md`).

## 1. Corrections appliquées au fichier SQL

Fichier : `database/migrations/003_market_lifecycle.sql`.

1. Suppression de la branche morte `WHEN phase = 6 THEN 5` dans le `CASE` de `lifecycle_phase` (l'évaluation descendante rendait cette branche inatteignable).
2. Ajout de `IF NOT EXISTS` à chaque `ALTER TABLE markets ADD COLUMN`.
3. Ajout de `IF NOT EXISTS` à `CREATE TABLE market_transitions`.
4. Ajout de `IF NOT EXISTS` à chaque `ALTER TABLE markets ADD CONSTRAINT`.
5. Ajout d'une garde d'idempotence sur `INSERT INTO market_transitions` via `WHERE NOT EXISTS (...)` pour éviter les doublons en cas de réexécution.

Aucune modification de la logique de conversion ni des données.

## 2. Vérification statique

- Types des clés primaires/étrangères : conformes (`INT UNSIGNED`).
- Contraintes CHECK : plages et valeurs cohérentes avec les données converties.
- Clauses `ON DELETE RESTRICT` conservées.
- Commentaires et contrôles post-migration conservés.

## 3. Test d'exécution sur copie jetable

- Base de test créée : `erp_manager_migration_test` (copie restaurée depuis la sauvegarde du 28/11/2026).
- Première exécution : code de sortie 0 (succès).
- Deuxième exécution (idempotence) : code de sortie 0 (succès), aucune erreur.
- Vérification des données après exécution :
  - `market_transitions` : 10 lignes (aucun doublon après la deuxième exécution).
  - Répartition `lifecycle_status` / `lifecycle_phase` conforme au tableau de conversion (2 `a_qualifier` phase 5, 1 `cloture` phase 8, 7 `en_cours` phases 1–4).
- La base de test a ensuite été supprimée. La base principale `erp_manager` n'a pas été modifiée.

## 4. Anomalies restantes (après correction)

### Moyennes
- **A5** — Unicité globale de la référence (`uq_markets_reference`) sans tenir compte de la société ; à traiter dans une migration future.
- **A6** — Absence d'index sur `lifecycle_status` / `lifecycle_phase` ; à surveiller.

### Mineures
- **A7** — Présence de marchés de test (`SSSSSS`, `M-TEST-001`) dans la base principale.
- **A8** — Affichage encore sur les anciennes valeurs tant que la migration n'est pas appliquée à la base principale.

### Résolues
- **A1** (non-idempotence) — atténuée par les gardes `IF NOT EXISTS` et la garde sur l'insertion.
- **A2** (absence de sauvegarde) — procédure de sauvegarde/restauration validée (voir `docs/TEST_SAUVEGARDE_RESTAURATION.md`).
- **A4** (branche morte) — supprimée.

## 5. Verdict final

**VALIDÉE**

La migration corrigée s'exécute avec succès sur une copie jetable, est idempotente, et produit les résultats attendus. La base principale `erp_manager` n'a pas été modifiée. La migration est prête à être exécutée sur la base principale après votre validation explicite et une sauvegarde préalable.
