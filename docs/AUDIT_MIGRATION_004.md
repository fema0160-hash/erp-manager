Référence : erp-manager/docs/AUDIT_MIGRATION_004.md

# Audit et correction de la migration 004

## Résultat

La première version de la migration 004 a été corrigée avant toute installation. La version actuelle est un brouillon technique plus cohérent avec MariaDB 10.4.32 et les tables réellement présentes dans les migrations 001, 002 et 003.

## Défauts corrigés

1. **État de qualification dupliqué** : la première version stockait un état dans la table des qualifications et un autre dans la table des validations. Ces deux valeurs auraient pu diverger. La version corrigée déduit désormais l'état :
   - aucune ligne dans `market_result_validations` : qualification en attente ;
   - ligne avec `validee` : qualification validée ;
   - ligne avec `refusee` : qualification refusée.

2. **Association possible au mauvais marché** : les premières clés étrangères reliaient les identifiants sans garantir que la qualification appartenait au même marché que la ligne d'historique ou le marché officiel. Des clés étrangères composites garantissent maintenant cette cohérence :
   - `market_transitions(qualification_id, market_id)` vers `market_result_qualifications(id, market_id)` ;
   - `markets(official_result_qualification_id, id)` vers `market_result_qualifications(id, market_id)`.

3. **Concurrence et qualifications simultanées** : la migration précise désormais le verrouillage obligatoire du marché avec `SELECT ... FOR UPDATE` avant de vérifier qu'aucune qualification en attente n'existe. Cela doit être appliqué dans le service métier.

4. **Validation unique** : une contrainte UNIQUE empêche qu'une même qualification reçoive plusieurs validations finales. Un refus est conservé ; une nouvelle tentative passe par une nouvelle qualification.

5. **Compatibilité des identifiants** : les types des clés étrangères correspondent aux migrations existantes : `markets.id` et `users.id` en `INT UNSIGNED`, identifiants des qualifications en `BIGINT UNSIGNED`.

## Contrôles de cohérence avec les migrations existantes

- Migration 001 : `markets.id INT UNSIGNED`.
- Migration 002 : `users.id INT UNSIGNED`.
- Migration 003 : `market_transitions.market_id INT UNSIGNED`, `changed_by INT UNSIGNED`, et table `market_transitions` créée avant la migration 004.
- Les tables utilisent InnoDB et utf8mb4.
- Les contraintes CHECK sont compatibles avec la cible MariaDB 10.4.32 annoncée dans le projet.

## Permissions ajoutées

La migration insère avec `INSERT IGNORE` quatre permissions dans la table `permissions` : `markets.lifecycle.advance`, `markets.results.qualify`, `markets.results.validate` et `markets.lifecycle.reopen`. Elles sont ouvertes par défaut selon le modèle actuel ; les restrictions par utilisateur et entreprise restent configurables par GEST. La réouverture est en plus contrôlée côté serveur et réservée au rôle GEST.

## Règles d'application obligatoires

La migration ne suffit pas à garantir le processus métier. Le futur service PHP devra :

1. vérifier l'authentification et l'accès à la société/marché ;
2. vérifier le droit de qualifier ou valider ;
3. vérifier que le marché est en phase 5 et qu'aucune décision définitive ne le bloque ;
4. verrouiller la ligne du marché avec `SELECT ... FOR UPDATE` ;
5. empêcher deux qualifications en attente pour le même marché ;
6. à la validation, verrouiller aussi la qualification et vérifier qu'elle n'a pas déjà été traitée ;
7. enregistrer validation, résultat officiel, synchronisation des anciennes colonnes et événement d'historique dans une transaction unique ;
8. ne faire passer en phase 6 que le résultat attribué validé.

Le même utilisateur peut qualifier et valider par défaut. Les deux actions restent distinctes et historisées.

## Limites des vérifications

- Le contenu SQL a été relu par rapport aux migrations 001 à 003.
- Les noms et types des tables/colonnes et les relations ont été contrôlés statiquement.
- Le fichier a été relu après correction sur GitHub.
- Aucun serveur MariaDB n'a été connecté depuis cette session.
- La migration n'a pas été exécutée ; aucune base ni donnée n'a été modifiée.
- Un test réel sur une copie de la base reste requis avant installation.

## Fichier concerné

`database/migrations/004_result_qualification_validation.sql`

**Statut : corrigée pour revue, non approuvée et non exécutée.**
