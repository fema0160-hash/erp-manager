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
| `gagne` | `a_qualifier` | Phase 5 conservée ; GEST doit confirmer la date et le montant attribué avant de valider `attribue` |
| `cloture` | `cloture` | Phase 8 |
| `perdu_annule` | `a_qualifier` | Intervention GEST requise |

L'ancien statut `perdu_annule` ne contient pas assez d'information pour déterminer s'il s'agit d'un marché perdu, annulé ou infructueux. Le script ne devine pas cette information : les dossiers concernés sont placés en `a_qualifier`.

La correspondance des six anciennes phases a été confirmée par l'utilisateur :

| Ancienne phase | Signification historique confirmée | Conversion proposée |
|---:|---|---|
| 1 | Repérage & DCE | Nouvelle phase 1 — Préparation |
| 2 | Montage dossier | Nouvelle phase 3 — Préparation de la soumission |
| 3 | Chiffrage & offre | Nouvelle phase 2 — Étude et BPU |
| 4 | Dépôt & soutenance | Nouvelle phase 4 — Soumis |
| 5 | Attribution / Verdict | Nouvelle phase 5 — Attente du résultat ; passage en phase 6 uniquement après qualification et validation de l'attribution par GEST |
| 6 | Clôture & historique | Nouvelle phase 8 — Clôturé / archivé, quel que soit l'ancien statut |

Les anciennes phases 2 et 3 sont donc réordonnées selon leur signification réelle : le montage du dossier précède le chiffrage et l'offre dans le nouveau cycle retenu. L'ancienne phase 6 est convertie en statut `cloture` et phase 8 sans condition sur l'ancien statut, car son libellé historique confirmé est « Clôture & historique ». Un ancien statut `cloture` est également normalisé en phase 8. L'ancien statut `gagne` n'est pas converti automatiquement en `attribue` : la migration le place en `a_qualifier`, phase 5, car les informations historiques ne garantissent pas la présence de la date de décision et du montant attribué exigés. GEST devra compléter et confirmer ces données. L'ancien statut `perdu_annule` reste également en `a_qualifier`. Toutes les valeurs sources restent conservées dans l'historique de migration.

## 5. Contrôles techniques et prérequis

Avant toute exécution :
1. Version confirmée sur XAMPP : MariaDB 10.4.32.
2. Confirmer que les migrations 001 et 002 sont installées.
3. Faire une sauvegarde complète de `erp_manager`.
4. Compter les marchés et examiner les statuts/phases existants.
5. Vérifier les dossiers qui seront classés `a_qualifier`.

Le script cible MySQL 8.0.16 ou supérieur et MariaDB 10.2.1 ou supérieur, versions qui appliquent les contraintes CHECK. La version relevée sur l'environnement XAMPP est MariaDB 10.4.32 : les types des clés étrangères correspondent aux tables 001/002 et les constructions SQL utilisées sont compatibles avec cette famille de versions. La migration n'a toutefois pas été exécutée sur cette base ; une sauvegarde et les contrôles de données restent obligatoires.

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
- le traitement GEST des dossiers `a_qualifier`, notamment les anciens marchés `gagne` et `perdu_annule` ;
- les champs obligatoires pour les décisions perdu, annulé, infructueux et attribué ;
- la compatibilité du script avec la version réelle de MySQL/MariaDB.

**Aucune exécution SQL ne sera faite sans autorisation explicite distincte.**
