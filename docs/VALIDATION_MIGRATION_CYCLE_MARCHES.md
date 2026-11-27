Référence : erp-manager/docs/VALIDATION_MIGRATION_CYCLE_MARCHES.md

# Validation de la migration SQL — cycle de vie des marchés

## 1. Objet

Le fichier `database/migrations/003_market_lifecycle.sql` prépare la structure nécessaire aux 8 phases, aux résultats distincts et à l'historique des changements.

**Statut : préparée pour revue — non exécutée.**

Aucune connexion à la base locale n'a été utilisée et aucune donnée n'a été modifiée.

## 2. Fichiers concernés

- `database/migrations/003_market_lifecycle.sql` : script SQL proposé.
- `docs/CYCLE_VIE_MARCHES.md` : règles fonctionnelles de référence.

La migration suppose que les migrations 001 et 002 sont déjà installées.

## 3. Choix de conception

### 3.1 Compatibilité et conservation des données

Les colonnes historiques `markets.status` et `markets.phase` ne sont ni supprimées ni modifiées.

La migration ajoute :
- `lifecycle_status` : statut normalisé ;
- `lifecycle_phase` : phase normalisée de 1 à 8 ;
- les informations de décision et d'attribution ;
- `market_transitions` : historique des valeurs initiales et des futures transitions.

Les anciennes valeurs sont copiées dans `market_transitions.legacy_status` et `legacy_phase`. Cette approche permet de contrôler la conversion avant que le futur code n'utilise exclusivement les nouvelles colonnes.

### 3.2 Statuts prévus

- `en_cours`
- `attribue`
- `perdu`
- `annule`
- `infructueux`
- `cloture`
- `a_qualifier` : statut technique temporaire pour les anciennes données ambiguës, à traiter par GEST.

### 3.3 Données de décision

- Marché perdu : entreprise concurrente attributaire, montant retenu et observations.
- Marché annulé ou infructueux : motif obligatoire.
- Marché attribué : date de décision et montant attribué.

Des contraintes SQL empêchent de valider certains statuts sans leurs informations obligatoires.

### 3.4 Historique

Chaque ligne d'historique conserve le marché, le type d'action, l'ancien et le nouvel état, l'utilisateur, la date et le motif. Les lignes créées par la migration ont un utilisateur NULL, car elles ne correspondent pas à une action humaine.

La future application devra créer une ligne d'historique à chaque transition et ne devra pas permettre aux utilisateurs ordinaires de modifier ou supprimer cet historique.

## 4. Conversion des anciennes données

| Ancien statut | Nouveau statut proposé | Traitement |
|---|---|---|
| `en_attente` | `en_cours` | Phase convertie |
| `en_cours` | `en_cours` | Phase convertie |
| `gagne` | `attribue` | Phase 6 |
| `cloture` | `cloture` | Phase 8 |
| `perdu_annule` | `a_qualifier` | Intervention GEST requise |

L'ancien statut `perdu_annule` ne contient pas assez d'information pour déterminer s'il s'agit d'un marché perdu, annulé ou infructueux. Le script ne devine pas cette information : les dossiers concernés sont placés en `a_qualifier`.

Les phases historiques 2 et 3 sont réordonnées pour correspondre au nouveau cycle : ancienne phase 2 « Publication » vers nouvelle phase 3 ; ancienne phase 3 « Pour étude » vers nouvelle phase 2. Les anciennes phases 4 et 5 restent respectivement 4 et 5. Les anciennes phases 6 associées à un statut en attente/en cours sont placées en `a_qualifier`, phase 5, car elles sont contradictoires.

**Point de validation métier :** confirmer que cette correspondance des anciennes phases 2 et 3 reflète bien les dossiers réellement enregistrés. Les valeurs sources restent conservées pour permettre la vérification.

## 5. Contrôles techniques et prérequis

Avant toute exécution :
1. Confirmer la version exacte de MySQL ou MariaDB utilisée par XAMPP.
2. Confirmer que les migrations 001 et 002 sont installées.
3. Faire une sauvegarde complète de `erp_manager`.
4. Compter les marchés et examiner les statuts/phases existants.
5. Vérifier les dossiers qui seront classés `a_qualifier`.

Le script est conçu pour MySQL 8.0.16 ou supérieur, avec application effective des contraintes CHECK. La syntaxe et le comportement doivent être confirmés sur la version MariaDB éventuellement fournie par XAMPP avant toute exécution.

Le script est à exécuter une seule fois. Les instructions DDL peuvent provoquer des validations implicites ; il ne faut donc pas supposer que l'ensemble du fichier est annulable par un simple ROLLBACK.

## 6. Contrôles après migration

Le script contient deux requêtes de contrôle commentées :
- répartition des marchés par nouveau statut et nouvelle phase ;
- liste des marchés à qualifier, avec leurs anciennes valeurs et le motif.

Ces contrôles devront être exécutés et leurs résultats examinés avant le développement qui basculera les écrans vers les nouvelles colonnes.

## 7. Ce qui n'est pas inclus

Cette migration ne :
- modifie pas les écrans ni les routes PHP ;
- n'active pas les transitions dans l'application ;
- ne modifie pas les permissions ;
- ne supprime aucune ancienne colonne ;
- ne modifie pas la base locale ou distante ;
- ne réalise pas la qualification automatique des anciens marchés.

Le développement PHP devra ensuite appliquer côté serveur les transitions autorisées, vérifier les droits et champs obligatoires, et enregistrer chaque opération dans `market_transitions`.

## 8. Décision attendue

Avant exécution, valider :
- la correspondance des anciennes phases 2 et 3 ;
- le traitement GEST des dossiers `a_qualifier` ;
- les champs obligatoires pour les décisions perdu, annulé, infructueux et attribué ;
- la compatibilité du script avec la version réelle de MySQL/MariaDB.

**Aucune exécution SQL ne sera faite sans autorisation explicite distincte.**
