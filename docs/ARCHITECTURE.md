Référence : erp-manager/docs/ARCHITECTURE.md

# Architecture initiale

## Objectif

Poser une base légère, lisible et évolutive pour ERP Manager. Cette version est volontairement limitée à la structure et à l'interface générale.

## État fonctionnel

- Tableau de bord : actif.
- Marchés : visible, désactivé.
- Entreprises : visible, désactivé.
- Utilisateurs : visible, désactivé.
- Documents : visible, désactivé.
- Rapports : visible, désactivé.
- Paramètres : visible, désactivé.

Les éléments désactivés ne déclenchent aucune action et ne donnent accès à aucune fonctionnalité.

## Structure

- `public/` : seul répertoire destiné à être exposé par le serveur web.
- `public/assets/` : feuilles de style et ressources front-end.
- `app/` : futur code applicatif (services, contrôleurs, règles métier).
- `config/` : future configuration non sensible.
- `resources/views/` : vues PHP.
- `database/` : futurs scripts SQL et migrations.
- `modules/` : futurs modules activables progressivement.
- `storage/` : fichiers internes, journaux et cache.
- `docs/` : documentation du projet.

## Hors périmètre

Aucune base de données, authentification, autorisation, gestion multi-entreprises ni fonction métier n'est implémentée dans cette étape.
