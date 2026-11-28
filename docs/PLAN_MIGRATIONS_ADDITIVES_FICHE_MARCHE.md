Référence : erp-manager/docs/PLAN_MIGRATIONS_ADDITIVES_FICHE_MARCHE.md

# Plan des migrations additives — fiche marché

- Cible : MariaDB 10.4.32 / InnoDB / utf8mb4
- Branche : `main`
- Statut : plan technique ; aucun script n'est autorisé à l'exécution
- Audit associé : `docs/AUDIT_COHERENCE_MCD_MLD_V1.md`

## 1. Principe de compatibilité

Les migrations 001 à 004 existantes ne sont pas réécrites ni rejouées automatiquement.

- 001 : sociétés et marchés.
- 002 : utilisateurs, sessions et permissions.
- 003 : cycle de vie au niveau du marché.
- 004 : qualifications, validations et historique de décision.
- 005 et suivantes : ajouts progressifs pour les lots indépendants et les fonctions métier.

Le fichier 004 reste le prérequis pour les qualifications, validations et phases 6 à 8. Le code actuel autorise encore l'avancement séquentiel des phases 1→2→3→4→5 sans 004. L'activation des lots indépendants exige en plus une version applicative adaptée à la migration 005.

## 2. Scripts préparés

| Migration | Fichier | Périmètre | Statut |
|---|---|---|---|
| 005 | `database/migrations/005_market_lots_independent.sql` | Lot n° 1 automatique, lots additionnels, rattachement des qualifications et transitions aux lots | Brouillon non exécuté |
| 006 | `database/migrations/006_market_core_details.sql` | Numérotation, paramètres par société, organismes, détails, étude, dépôt et attribution par lot | Brouillon non exécuté |
| 007 | `database/migrations/007_market_documents_checklist.sql` | Documents privés, modèles, checklist et événements append-only | Brouillon non exécuté |
| 008 | `database/migrations/008_market_guarantees.sql` | Cautions successives par lot, remplacements et justificatifs | Brouillon non exécuté |
| 009 | `database/migrations/009_market_bpu.sql` | Versions BPU par lot, lignes HT/TVA/TTC et imports | Brouillon non exécuté |
| 010 | `database/migrations/010_market_contract_periods_alerts.sql` | Périodes contractuelles, renouvellements, règles et occurrences d'alertes | Brouillon non exécuté |
| 011 | `database/migrations/011_market_execution_retention_closure.sql` | Exécution, situations, retenues TTC, avenants, réceptions et clôtures | Brouillon non exécuté |

### Migration 005 — dépendances

- Exige 001, 002, 003 et 004 installées.
- Ajoute la clé candidate `markets(id, company_id)`.
- Crée les lots et rattache les données historiques au lot n° 1.
- Le service `MarketLifecycle` et la fiche marché prennent désormais en charge la création, la sélection, la progression, la qualification, la validation, la réouverture et l'historique par lot. Les nouveaux marchés créés après 005 reçoivent automatiquement leur lot n° 1.
- La liste des marchés affiche un état synthétique lorsqu'un marché comporte plusieurs lots.
- Cette adaptation est enregistrée dans le code mais n'a pas encore été testée en environnement PHP/MariaDB.
- Ne doit pas être exécutée seule avec le code applicatif actuel.

### Migration 006 — dépendances

- Exige 005 et `market_lots`.
- Initialise les paramètres de retenue par société à 10 % et 7 %.
- Enregistre les instantanés par lot au moment de l'attribution.
- Les champs qui ne sont connus qu'aux phases ultérieures restent NULL avant leur saisie.

## 3. Dépendances des migrations 007 à 011

| Migration | Dépendances | Objets principaux |
|---|---|---|
| 007 | 005, 006 | Modèles documentaires, fichiers privés, checklist et événements |
| 008 | 005, 007 | Cautions successives et liens de remplacement |
| 009 | 005, 007 | Versions BPU, lignes, imports et rapports |
| 010 | 005, 007, 008 | Périodes, règles et occurrences d'alertes |
| 011 | 005 à 010 | Événements chantier, situations, retenues, avenants, réceptions et clôtures |

Les scripts 007 à 011 ont été créés sur `main` comme brouillons. Ils n'ont pas encore subi un test d'import réel sur MariaDB 10.4.32. La relecture statique doit notamment confirmer les FK composites, les triggers append-only et les règles de verrouillage applicatif avant toute installation.

## 4. Règles SQL et métier obligatoires

- Aucune suppression des données historiques 001 à 004.
- Aucune FK vers une donnée d'une autre société ou d'un autre lot.
- Tous les montants en `DECIMAL`.
- Les calculs BPU HT/TVA/TTC sont faits côté serveur, par ligne.
- Retenue : 10 % du TTC de chaque situation validée, plafonnée à 7 % du TTC initial attribué au lot ; taux et plafond modifiables par société.
- Les prélèvements de retenue sont historisés sans modification ni suppression ; une situation ne déclenche pas deux fois le même prélèvement.
- Plusieurs cautions successives sont conservées, sans écrasement.
- Les contraintes qui dépendent de plusieurs lignes (plafond cumulé, non-chevauchement, une seule qualification en attente par lot) sont garanties dans des transactions applicatives avec verrouillage.
- Les DDL MariaDB provoquent des commits implicites : chaque migration doit être contrôlée avant/après et ne doit pas être relancée aveuglément.

## 5. Code applicatif et tests

- `app/MarketLifecycle.php` : méthodes dédiées aux lots, verrouillage du lot en transaction, qualifications et validations filtrées par lot.
- `public/index.php` : sélection du lot, routage des actions vers les méthodes lot lorsque le schéma 005 est détecté, compatibilité avec le parcours historique avant 005.
- `resources/views/market-detail.php` : navigation entre lots, création d'un lot et affichage du cycle/historique du lot sélectionné.
- `resources/views/markets.php` et tableau de bord : affichage synthétique « Plusieurs lots » / « Phases indépendantes » lorsque plusieurs états indépendants coexistent ; les filtres de statut de la liste recherchent les marchés possédant au moins un lot dans le statut demandé.
- Plan de tests : `docs/TESTS_FONCTIONNELS_LOTS_V1.md`.

Aucun test automatisé PHP, aucun test navigateur et aucun test d'import MariaDB n'ont encore été exécutés dans cette passe.

## 6. Procédure avant toute installation

1. Lire `SHOW CREATE TABLE` des tables concernées et comparer avec les prérequis du script.
2. Vérifier que chaque migration n'a pas déjà été installée.
3. Effectuer une sauvegarde complète et vérifier sa restauration.
4. Tester les migrations sur une copie de la base.
5. Vérifier les comptages, les clés étrangères, les index, les CHECK et l'isolation multi-sociétés.
6. Déployer le code applicatif compatible dans la même fenêtre de maintenance.
7. Obtenir l'autorisation explicite avant d'exécuter sur la base de travail ou de production.

La présence d'un script dans GitHub ne signifie jamais qu'il a été exécuté ou qu'il est autorisé à l'être.
