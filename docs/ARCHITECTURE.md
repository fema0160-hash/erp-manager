Référence : erp-manager/docs/ARCHITECTURE.md

# Architecture ERP Manager

## Objectif
Construire une application PHP/MySQL légère, en français, évolutive par étapes et utilisable sous XAMPP.

## État fonctionnel actuel

- Tableau de bord : connecté à MySQL et protégé par authentification.
- Authentification : connexion, déconnexion et gestion des sessions.
- Rôles : GEST, SECR et CHAD.
- Mot de passe initial : 123, stocké uniquement sous forme de hash ; changement demandé par une alerte rouge non bloquante.
- Gestion GEST : création de comptes SECR/CHAD, activation/désactivation, réinitialisation du mot de passe et restrictions par entreprise/fonction.
- Autorisations : accès total par défaut ; seules les restrictions enregistrées bloquent un accès.
- Marchés : données de démonstration consultables sur le tableau de bord ; le CRUD reste à développer.
- Entreprises : données de démonstration ; gestion des entreprises à développer.

## Structure

- `public/` : point d'entrée web et ressources publiques.
- `app/Auth.php` : authentification, sessions et vérification des restrictions.
- `config/` : configuration non sensible ; `database.local.php` reste local et ignoré par Git.
- `resources/views/` : vues PHP.
- `database/migrations/` : scripts SQL versionnés.
- `modules/` : futurs modules activables progressivement.
- `storage/` : fichiers internes, journaux et cache.
- `docs/` : documentation du projet.

## Règles d'accès

- Tous les utilisateurs disposent par défaut de l'accès à toutes les entreprises.
- Tous les droits fonctionnels sont accordés par défaut.
- Une restriction explicite dans les tables de restrictions retire l'accès concerné.
- Le rôle GEST conserve l'administration générale et n'est pas limité par ces restrictions.
- L'écran Utilisateurs & droits est réservé au rôle GEST.

## Installation de la base

Importer dans l'ordre :
1. `database/migrations/001_create_demo_tables.sql`
2. `database/migrations/002_create_auth_tables.sql`

La deuxième migration crée les tables d'authentification, les permissions et les trois comptes initiaux.

## Sécurité

- Mots de passe hashés avec `password_hash()` et vérifiés avec `password_verify()`.
- Requêtes PDO préparées.
- Sessions PHP strictes, cookies HttpOnly et SameSite=Lax.
- Jetons CSRF pour les formulaires sensibles.
- Verrouillage temporaire après cinq échecs de connexion.
- Expiration après 30 minutes d'inactivité.
