Référence : erp-manager/docs/CYCLE_VIE_MARCHES.md

# Cycle de vie des marchés — proposition fonctionnelle

## 1. Principe de modélisation

Le cycle doit distinguer deux informations qui ne doivent pas être confondues :

- **Phase** : l'étape de traitement du dossier.
- **Statut / résultat** : l'état du marché ou la décision obtenue.

Une décision (gagné, perdu, annulé ou infructueux) ne doit donc pas être utilisée comme une phase.

## 2. Phases proposées

| N° | Phase | Description |
|---:|---|---|
| 1 | Préparation | Enregistrement du dossier, identification de l'organisme, des pièces et des dates |
| 2 | Étude et BPU | Analyse du dossier, chiffrage, élaboration et validation des versions du BPU |
| 3 | Préparation de la soumission | Constitution et contrôle du dossier de candidature et de l'offre |
| 4 | Soumis | Dépôt de l'offre et enregistrement de la date et des références de dépôt |
| 5 | Attente du résultat | Suivi de la séance et attente de la décision officielle |
| 6 | Préparation du marché attribué | Après attribution : préparation du dossier d'exécution, garanties et éléments nécessaires au démarrage |
| 7 | Clôturé / archivé | Dossier terminé, conservé en consultation et dans l'historique |

La phase 6 n'est accessible qu'après une décision d'attribution. Les marchés perdus, annulés ou infructueux ne passent pas en phase 6.

## 3. Statuts et résultats

| Code fonctionnel | Libellé | Effet |
|---|---|---|
| EN_COURS | En cours | Le dossier suit les phases 1 à 5 |
| ATTRIBUE | Attribué / gagné | Autorise le passage à la phase 6 |
| PERDU | Perdu | Issue négative définitive, dossier consultable |
| ANNULE | Annulé | Issue définitive, motif obligatoire, dossier consultable |
| INFRUCTUEUX | Infructueux | Issue définitive, motif obligatoire, dossier consultable |
| CLOTURE | Clôturé | Fin du traitement, dossier archivé et consultable |

Le résultat ATTRIBUE est conservé pendant la préparation du marché attribué. Les informations de l'offre initiale restent historisées ; les données de préparation/exécution sont gérées dans leur partie dédiée.

## 4. Transitions autorisées

- Phase 1 → phase 2
- Phase 2 → phase 3
- Phase 3 → phase 4
- Phase 4 → phase 5
- Depuis la phase 5 : décision ATTRIBUE, PERDU, ANNULE ou INFRUCTUEUX.
- ATTRIBUE : phase 5 → phase 6.
- Phase 6 → phase 7 après vérification des éléments de clôture.
- PERDU, ANNULE et INFRUCTUEUX : aucune transition ordinaire supplémentaire.
- Réouverture exceptionnelle : GEST uniquement, avec motif obligatoire et conservation de l'historique.

Aucune étape ne peut être sautée. Les transitions inverses sont interdites en fonctionnement normal.

## 5. Informations obligatoires selon la décision

- **Perdu** : entreprise concurrente attributaire, montant retenu et observations.
- **Annulé** : motif d'annulation.
- **Infructueux** : motif / explication de l'infructuosité.
- **Attribué** : décision d'attribution, montant retenu et pièces justificatives disponibles.
- **Clôturé** : contrôle de complétude du dossier final et pièces manquantes signalées.

Les champs obligatoires doivent être contrôlés côté serveur, pas uniquement dans le navigateur.

## 6. Historique et droits

Chaque transition doit enregistrer au minimum : marché concerné, ancienne phase et ancien statut, nouvelle phase et nouveau statut, utilisateur, date/heure, motif et données justificatives pertinentes.

Les dossiers PERDU, ANNULE, INFRUCTUEUX et CLOTURE restent visibles en consultation. Leur modification ordinaire est bloquée. Une réouverture exceptionnelle est réservée à GEST et doit être justifiée et journalisée.

Les contrôles d'accès aux marchés et à leurs sociétés restent appliqués à chaque lecture et à chaque action.

## 7. Compatibilité avec la base actuelle

La migration actuelle définit seulement cinq statuts (en_cours, cloture, gagne, perdu_annule, en_attente) et limite les phases à 1–6. Elle ne permet pas de distinguer séparément PERDU, ANNULE et INFRUCTUEUX, ni d'enregistrer un historique détaillé des transitions.

**Aucune modification SQL n'est effectuée par ce document.** Avant le développement des transitions, une migration devra être préparée et présentée séparément pour validation explicite. Elle devra notamment prévoir les statuts séparés, les phases harmonisées, les données obligatoires des décisions et une table d'historique. Les données existantes devront être converties sans perte.

## 8. Critères de validation avant mise en production

- Les phases et statuts affichés sont cohérents dans les listes, fiches et tableaux de bord.
- Chaque transition est contrôlée côté serveur et soumise aux permissions.
- Les champs obligatoires de décision sont vérifiés.
- L'historique est immuable pour les utilisateurs ordinaires.
- Les issues définitives sont consultables sans modification ordinaire.
- La réouverture GEST exige un motif et laisse une trace.
- Les données existantes sont migrées de manière documentée et vérifiable.
