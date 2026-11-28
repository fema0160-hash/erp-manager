Référence : erp-manager/docs/ETAPE_1_HARMONISATION_CYCLE_VIE.md

# Étape 1 — Harmonisation du cycle de vie des marchés

Note chronologique : ce rapport décrit l'état avant l'exécution de la migration 003 (8 marchés de démonstration et le marché de test `M-TEST-001`). La migration 003 a ensuite été exécutée avec succès (voir `EXECUTION_MIGRATION_003.md`).

## 1. Fichiers créés
- `app/MarketLifecycle.php` : source unique des phases (8) et statuts du cycle de vie, avec libellés actuels (legacy) et prévus par la migration 003, plus couche de lecture compatible (`lifecycleStatus`, `lifecyclePhase`, `detectLifecycleColumns`).

## 2. Fichiers modifiés
- `public/index.php` : chargement de `MarketLifecycle` ; détection des colonnes `lifecycle_*` (lecture seule) ; requêtes en `SELECT m.*` ; filtre de statut dynamique selon l'état de migration ; calcul préparatoire de `$canUpdateMarket` et `$canDeleteMarket` (non activés) ; tableau de bord alimenté via les helpers de compatibilité.
- `resources/views/markets.php` : libellés issus de `MarketLifecycle` (selon migration) ; affichage via `lifecycleStatus`/`lifecyclePhase` ; libellés initiaux centralisés.
- `resources/views/dashboard.php` : titre du cycle de vie dynamique (6 ou 8 phases) ; injection des globales JS `ERP_MARKET_MIGRATED`, `ERP_MARKET_PHASES`, `ERP_MARKET_STATUSES`, `ERP_MARKET_LEGACY_PHASES`, `ERP_MARKET_LEGACY_STATUSES`.
- `public/assets/js/dashboard.js` : construction dynamique de `PHASES` et `STATUS_META` à partir des globales, avec styles et libellés centralisés.
- `docs/ARCHITECTURE.md` : mention de `MarketLifecycle.php`.

## 3. Changements réalisés
- Centralisation des libellés de phases et statuts (anciens et nouveaux) dans une seule classe PHP.
- Compatibilité de lecture avec les anciennes colonnes `status`/`phase` et les nouvelles `lifecycle_status`/`lifecycle_phase`, sans supposer leur existence (détection par requête `SELECT ... LIMIT 0` capturée).
- Aucune référence SQL aux nouvelles colonnes si elles sont absentes.
- Préparation (sans activation) des contrôles `markets.update` et `markets.delete`.
- Conservation stricte de `hasPermission` et `hasCompanyAccess` pour GEST, SECR et CHAD.

## 4. Tests exécutés et résultats
- Syntaxe PHP (`php -l`) : `app/MarketLifecycle.php`, `public/index.php`, `resources/views/markets.php`, `resources/views/dashboard.php` — OK.
- Syntaxe JavaScript (`node --check`) : `public/assets/js/dashboard.js` — OK.
- Affichage des marchés (base non migrée) : 8 marchés affichés, aucun message d'erreur.
- Tableau de bord (base non migrée) : chargement OK, `ERP_MARKET_MIGRATED = false`.
- Anciennes données et anciens statuts : affichage conservé (6 phases, 5 statuts legacy).
- Permissions et restrictions par entreprise : connexion et accès vérifiés pour GEST, SECR et CHAD.
- Création d'un marché : fonctionnelle (test `M-TEST-001` créé).
- Fonctionnement sur base non migrée : confirmé (aucune erreur SQL liée aux colonnes `lifecycle_*`).

## 5. Anomalies restantes
- Le marché de test `M-TEST-001` a été créé lors de la validation fonctionnelle et reste en base (aucune suppression effectuée conformément aux contraintes).
- La migration 003 n'a pas été exécutée ; les colonnes `lifecycle_*` et la table `market_transitions` n'existent pas encore.
- Le tableau de bord affiche encore 6 phases tant que la migration n'est pas appliquée.
- Aucune fonctionnalité CRUD d'édition/suppression n'est activée (préparation uniquement).

## 6. Confirmation
- Aucune table n'a été modifiée.
- Aucune migration n'a été exécutée, y compris le fichier SQL 003.
- Aucune commande SQL de modification n'a été lancée.
- `config/database.local.php` n'a pas été modifié.
- Aucun compte utilisateur n'a été modifié.
- Aucune donnée n'a été supprimée.
