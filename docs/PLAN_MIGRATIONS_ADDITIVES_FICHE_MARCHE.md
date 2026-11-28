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

### Migration 005 — dépendances

- Exige 001, 002, 003 et 004 installées.
- Ajoute la clé candidate `markets(id, company_id)`.
- Crée les lots et rattache les données historiques au lot n° 1.
- Exige une adaptation du service métier et des écrans avant utilisation fonctionnelle.
- Ne doit pas être exécutée seule avec le code applicatif actuel.

### Migration 006 — dépendances

- Exige 005 et `market_lots`.
- Initialise les paramètres de retenue par société à 10 % et 7 %.
- Enregistre les instantanés par lot au moment de l'attribution.
- Les champs qui ne sont connus qu'aux phases ultérieures restent NULL avant leur saisie.

## 3. Migrations restantes à préparer

| Migration proposée | Périmètre | Dépendances principales |
|---|---|---|
| 007 | Documents privés, modèles documentaires et checklist des phases 3/4 avec historique append-only | 005, 006 |
| 008 | Cautions provisoires/définitives successives, remplacements et justificatifs | 005, 006, 007 |
| 009 | BPU par lot, versions, lignes HT/TVA/TTC, imports et rapports d'erreurs | 005, 007 |
| 010 | Périodes contractuelles, renouvellements, règles et occurrences d'alertes | 005, 006, 007, 008 |
| 011 | Événements de chantier, situations de travaux, retenues TTC, avenants, réceptions et clôtures par lot | 005, 006, 007, 008, 009, 010 |

L'ordre définitif sera ajusté après vérification de toutes les clés étrangères, notamment les références composites vers les fichiers, les lots, les situations et les cautions.

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

## 5. Procédure avant toute installation

1. Lire `SHOW CREATE TABLE` des tables concernées et comparer avec les prérequis du script.
2. Vérifier que chaque migration n'a pas déjà été installée.
3. Effectuer une sauvegarde complète et vérifier sa restauration.
4. Tester les migrations sur une copie de la base.
5. Vérifier les comptages, les clés étrangères, les index, les CHECK et l'isolation multi-sociétés.
6. Déployer le code applicatif compatible dans la même fenêtre de maintenance.
7. Obtenir l'autorisation explicite avant d'exécuter sur la base de travail ou de production.

La présence d'un script dans GitHub ne signifie jamais qu'il a été exécuté ou qu'il est autorisé à l'être.
