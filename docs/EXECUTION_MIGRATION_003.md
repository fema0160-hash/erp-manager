Référence : erp-manager/docs/EXECUTION_MIGRATION_003.md

# Exécution de la migration 003 — Cycle de vie des marchés

## 1. Informations générales

- Date et heure : 2026-11-28 00:48:54 (heure locale).
- Base concernée : `erp_manager` (serveur MariaDB 10.4.32, 127.0.0.1:3306).
- Version du fichier SQL : `database/migrations/003_market_lifecycle.sql` (version corrigée et réauditée, 12 occurrences de `IF NOT EXISTS`).
- Sauvegarde vérifiée : `storage/backups/erp_manager_backup_20261128_003834.sql` (SHA-256 : 4B2B3342E309CC2C9084FE2FE1E52AAF29FC4887419EC75A5E972B0873723170).
- `config/database.local.php` : non modifié.

## 2. Résultat de l'exécution

- Commande : `mysql -u root -h 127.0.0.1 -P 3306 erp_manager < 003_market_lifecycle.sql`.
- Code de sortie : 0 (succès).
- Aucune erreur SQL.

## 3. Résultats des contrôles

### 3.1 Colonnes et table créées
- Colonnes ajoutées à `markets` : `lifecycle_status` (VARCHAR(20), NOT NULL, DEFAULT 'en_cours'), `lifecycle_phase` (TINYINT UNSIGNED, NOT NULL, DEFAULT 1), ainsi que `decision_date`, `decision_reason`, `winner_company_name`, `winner_amount`, `decision_observations`, `awarded_amount`.
- Table `market_transitions` créée.

### 3.2 Marchés convertis (10)
| id | reference | status (ancien) | phase (ancienne) | lifecycle_status | lifecycle_phase |
|---:|---|---|---:|---|---:|
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

### 3.3 Transitions
- Nombre de lignes dans `market_transitions` : 10.
- Type d'action : `migration` (10).
- Aucun doublon.

### 3.4 Contraintes, index et clés étrangères
- Contraintes CHECK présentes : `chk_markets_status`, `chk_markets_phase`, `chk_markets_amount`, `chk_markets_is_demo`, `chk_markets_lifecycle_status`, `chk_markets_lifecycle_phase`, `chk_markets_decision_data`.
- Clés étrangères présentes : `fk_markets_company` (markets → companies), `fk_market_transitions_market` (market_transitions → markets), `fk_market_transitions_user` (market_transitions → users).

### 3.5 Fonctionnement de l'application
- Connexion et accès vérifiés pour les rôles GEST, SECR et CHAD.
- Pages `marches` et `dashboard` : code HTTP 200, aucun message d'erreur.
- Drapeau `ERP_MARKET_MIGRATED = true` détecté côté client.

### 3.6 Données historiques
- Les anciennes colonnes `status` et `phase` sont conservées et inchangées pour les 10 marchés.
- Les valeurs sources sont enregistrées dans `market_transitions.legacy_status` et `legacy_phase`.

### 3.7 Qualification GEST
- Les marchés `M-2026-005` et `M-2026-001` sont en statut `a_qualifier` (phase 5). Aucune qualification automatique n'a été effectuée ; leur traitement reste une décision de GEST.

## 4. Anomalies

Aucune anomalie détectée lors de l'exécution ou des vérifications.

## 5. Confirmation de l'intégrité des données

- 10 marchés préservés, anciennes colonnes intactes.
- 10 transitions créées sans doublon.
- Contraintes et clés étrangères conformes.
- Application fonctionnelle pour GEST, SECR et CHAD.
- Aucune donnée supprimée, aucun marché modifié manuellement.

## 6. Conclusion

**SUCCÈS** — La migration 003 a été exécutée avec succès sur la base principale `erp_manager`. L'intégrité des données est confirmée.
