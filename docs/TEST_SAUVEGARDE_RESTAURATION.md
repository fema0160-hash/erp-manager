Référence : erp-manager/docs/TEST_SAUVEGARDE_RESTAURATION.md

# Test de sauvegarde et de restauration — préalable migration 003

Note chronologique : ce test a été réalisé avant l'exécution de la migration 003. La base principale contenait alors 10 marchés.

## 1. Objet

Préparer et valider une sauvegarde complète de la base `erp_manager` (structure et données) et tester sa restauration sur une base distincte, sans modifier la base principale ni exécuter la migration 003.

## 2. Environnement

- Serveur : MariaDB 10.4.32 (XAMPP).
- Base principale : `erp_manager` (non modifiée).
- Base de test de restauration : `erp_manager_restore_test` (créée pour le test).

## 3. Sauvegarde

- Outil : `mysqldump` 10.4.32.
- Options : `--single-transaction --routines --triggers`.
- Fichier : `storage/backups/erp_manager_backup_20261128_003834.sql` (17 287 octets).
- Contenu : structure et données de toutes les tables (companies, markets, users, permissions, restrictions, sessions).

## 4. Restauration sur base de test

- Création de la base `erp_manager_restore_test` (utf8mb4 / utf8mb4_unicode_ci).
- Import du fichier de sauvegarde via `mysql`.
- Code de sortie : 0 (succès).

## 5. Vérifications

- Tables restaurées : 7 (companies, markets, permissions, user_company_restrictions, user_permission_restrictions, user_sessions, users).
- Nombre de marchés : 10 (identique à la base principale).
- Nombre d'utilisateurs : 3 (identique).
- Nombre d'entreprises : 5 (identique).
- Comparaison ligne par ligne des marchés (id, reference, status, phase) : identique entre `erp_manager` et `erp_manager_restore_test`.

## 6. Confirmations

- Aucune modification de la base principale `erp_manager`.
- Aucune migration exécutée, y compris le fichier SQL 003.
- Aucune instruction SQL modificative lancée sur la base principale.
- `config/database.local.php` non modifié.
- Aucune donnée supprimée.
- La base de test `erp_manager_restore_test` peut être supprimée après validation sans impact sur la base principale.

## 7. Conclusion

La sauvegarde et la restauration sont fonctionnelles et fidèles. La procédure est prête pour la migration 003, sous réserve de votre validation explicite. Aucune suite n'a été engagée.
