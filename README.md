# ERP Manager

Application web PHP/MySQL de gestion des marchés, en français, conçue pour évoluer progressivement.

## État actuel

- Tableau de bord connecté à MySQL.
- Authentification et déconnexion sécurisées.
- Rôles initiaux : GEST, SECR et CHAD.
- Changement du mot de passe initial depuis l'application.
- Accès à toutes les entreprises et droits fonctionnels accordés par défaut ; les restrictions sont prévues par entreprise et par fonction.
- Les marchés et entreprises du tableau de bord sont des données de démonstration.

## Installation locale (XAMPP)

1. Placer le dépôt dans le dossier web de XAMPP.
2. Démarrer Apache et MySQL.
3. Vérifier `config/database.local.php` (copier `config/database.local.example.php` si nécessaire).
4. Dans phpMyAdmin, sélectionner la base `erp_manager`.
5. Importer, dans cet ordre et une seule fois :
   - `database/migrations/001_create_demo_tables.sql`
   - `database/migrations/002_create_auth_tables.sql`
6. Ouvrir `http://localhost/erp-manager/public/` (adapter le chemin si le dépôt est installé dans un sous-dossier différent).

## Comptes initiaux

| Rôle | Identifiant | Mot de passe initial |
|---|---|---|
| Administrateur général | `gest` | `123` |
| Secrétaire | `secr` | `123` |
| Chargé d’études | `chad` | `123` |

Le mot de passe initial est stocké sous forme de hash. Après connexion, une alerte rouge non bloquante invite l'utilisateur à le modifier. Choisir un nouveau mot de passe d'au moins 10 caractères.

## Sécurité

Ne jamais publier `config/database.local.php`, un fichier `.env` ou des mots de passe. Les identifiants ci-dessus sont uniquement des accès initiaux de développement : remplacer les mots de passe avant toute mise en ligne publique.

## Prochaine étape

Terminer l'interface GEST de gestion des utilisateurs et des restrictions, puis développer le CRUD du module Marchés.
