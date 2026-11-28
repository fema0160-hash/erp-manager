Référence : erp-manager/docs/CAHIER_CHARGES_FICHE_MARCHE_COMPLETE.md

# Cahier des charges — Fiche complète d'un marché

- Projet : ERP Manager
- Branche de référence : `main`
- Périmètre : gestion des marchés publics, BTP et travaux publics au Maroc
- Langue de l'interface : français
- Devise : dirham marocain (MAD / DH)
- Statut : spécification fonctionnelle consolidée, à implémenter après audit technique

## 1. Objectif

La fiche marché est le dossier central qui accompagne chaque consultation depuis sa préparation jusqu'à sa clôture et son archivage. Elle doit être complète, organisée par phase, simple à utiliser et évolutive.

Les informations générales restent consultables à tout moment. Les rubriques métier apparaissent au fur et à mesure de l'avancement du marché. Les champs obligatoires sont marqués par un astérisque et contrôlés côté serveur.

La fiche ne doit pas imposer à l'utilisateur de remplir dès la création les informations qui ne deviennent disponibles que dans les phases ultérieures.

## 2. Présentation du cycle de vie

Le haut de la fiche présente deux rangées horizontales distinctes :

1. Première rangée : les huit phases, dans l'ordre, sur une seule ligne.
2. Deuxième rangée : les quatre décisions possibles après la phase 5, sur une seule ligne.

### 2.1 Les huit phases

| N° | Phase |
|---:|---|
| 01 | Préparation |
| 02 | Étude et BPU |
| 03 | Préparation de la soumission |
| 04 | Soumis |
| 05 | Attente du résultat |
| 06 | Préparation du marché attribué |
| 07 | Exécution et suivi du chantier |
| 08 | Clôturé et archivé |

Règles d'affichage :

- Les huit phases restent alignées sur une seule rangée sur ordinateur.
- Les libellés peuvent occuper plusieurs lignes à l'intérieur de chaque phase, sans faire passer une phase sur une deuxième rangée.
- Sur écran étroit, la rangée conserve son alignement et devient horizontalement défilable.
- Les phases terminées, la phase actuelle et les phases à venir sont visuellement différenciées.
- L'état, les dates et la progression proviennent des données réellement enregistrées, jamais de valeurs de démonstration.
- Une phase peut ouvrir ses détails (dates, documents, observations et historique) sans modifier le parcours.

### 2.2 Les quatre décisions après la phase 5

Les quatre décisions sont alignées sur une seule deuxième rangée :

| Décision | Sous-texte | Indication |
|---|---|---|
| Attribué | Poursuite vers la phase 6 | Continuer |
| Perdu | Fin du parcours normal | Terminé |
| Annulé | Fin du parcours normal | Terminé |
| Infructueux | Fin du parcours normal | Terminé |

Les quatre choix sont visibles dans le diagramme, mais ne sont activables qu'à la phase 5. Une qualification n'est pas une décision officielle : elle doit être validée selon les règles existantes. Seule une attribution validée ouvre la phase 6. Les trois autres résultats terminent le parcours normal et restent consultables.

## 3. Rubrique 1 — Informations générales

Cette rubrique reste accessible à tout moment, sous réserve des règles de modification et de traçabilité.

| Champ | Obligatoire | Règle |
|---|---|---|
| Numéro de dossier | Oui | Généré automatiquement, unique et non modifiable manuellement |
| N° appel d'offres | Oui | Référence officielle de la consultation |
| Objet / Intitulé | Oui | Objet complet du marché |
| Entreprise | Oui | Société concernée, sélectionnée parmi les entreprises du groupe |
| Client / Maître d'ouvrage | Oui | Organisme ou entité adjudicatrice |
| Maître d'œuvre | Oui | Organisme ou personne chargée de la maîtrise d'œuvre |
| Durée (mois) | Oui | Durée contractuelle initiale en mois |
| Renouvelable | Oui | Oui / Non |
| Durée de renouvellement (années) | Conditionnel | Obligatoire si le marché est renouvelable |

Informations complémentaires recommandées :

- année de consultation ;
- numéro et désignation du lot, le cas échéant ;
- nature du marché : travaux, fournitures ou services ;
- adresse et lieu d'exécution ;
- personne de contact du maître d'ouvrage et du maître d'œuvre ;
- référence interne ou référence externe complémentaire.

Le numéro de dossier interne ne doit jamais être confondu avec le numéro officiel de l'appel d'offres ou le numéro du marché attribué.

## 4. Rubrique 2 — Étude et estimation (visible dès la phase 2)

| Champ | Obligatoire | Règle |
|---|---|---|
| Date de lancement | Oui | Date de lancement de la consultation |
| Date de remise des offres | Oui | Date et heure limites |
| Date d'ouverture des plis | Oui | Date et heure prévues ou confirmées |
| Validé pour étude le | Oui | Date de validation de la prise en charge de l'étude |
| Charges d'étude (DH) | Oui | Coût interne de l'étude supporté par l'entreprise |
| Coût estimatif (DH) | Oui | Estimation financière du marché |
| Taux de retenue de garantie | Paramétré | Taux repris depuis les paramètres, non ressaisi librement dans chaque marché |

Données complémentaires recommandées :

- chargé d'études responsable ;
- date de début et date de fin de l'étude ;
- observations de l'étude ;
- document d'estimation et pièces du dossier de consultation.

Les charges d'étude et le coût estimatif sont deux montants distincts et ne doivent pas être confondus.

## 5. Rubrique 3 — Préparation de la soumission (phase 3)

### 5.1 Caution provisoire

| Champ / action | Règle |
|---|---|
| Montant de la caution provisoire (DH) | Montant exigé dans le dossier de consultation |
| Préparer | Action ou état de préparation de la caution |
| Obtenu | Confirmation que la caution a été obtenue |
| Justificatif | Copie du document de caution à joindre |
| Étude terminée le | Date de fin de l'étude |

Le suivi de la caution distingue au minimum les états « À préparer », « Préparée » et « Obtenue ». L'obtention doit pouvoir être accompagnée de son justificatif.

### 5.2 BPU — Bordereau des prix

Le module BPU doit proposer les deux modes suivants :

1. Importation d'un tableau existant, notamment depuis Excel.
2. Création et gestion des lignes directement dans ERP Manager.

Fonctions attendues :

- désignation ;
- unité ;
- quantité ;
- prix unitaire ;
- montant calculé ;
- total général ;
- ajout, modification et suppression contrôlée des lignes avant verrouillage ;
- import Excel avec prévisualisation, validation des colonnes et rapport des lignes rejetées ;
- pièces PDF et autres documents justificatifs attachés au BPU ;
- versions successives conservées avec auteur, date et motif ;
- distinction entre BPU de travail et version finale utilisée pour la soumission.

Les calculs de ligne et le total sont automatiques. Les montants sont enregistrés avec une précision décimale adaptée à la devise MAD.

## 6. Tableau standard des documents — phases 3 et 4

### 6.1 Modèle dans les paramètres

Un modèle standard est configuré dans :

**Paramètres → Gestion des marchés → Modèle des documents de soumission**

Le modèle permet au minimum au gestionnaire des paramètres de :

- ajouter et nommer des désignations de documents ;
- modifier leur ordre d'affichage ;
- activer ou désactiver une ligne pour les nouveaux dossiers ;
- indiquer si une pièce est obligatoire ou facultative.

Exemples de désignations à proposer dans le modèle initial, à confirmer et adapter dans les paramètres :

- papiers / documents administratifs ;
- copies des pièces ;
- reçu ou accusé de réception ;
- enveloppe extérieure ;
- enveloppe administrative ;
- enveloppe technique ;
- enveloppe financière ;
- caution provisoire ;
- tableaux BPU ;
- autres documents.

### 6.2 Colonnes du tableau

Le tableau comporte exactement trois colonnes :

| Désignation des documents | Reçu / préparé | Date |
|---|:---:|---|
| Désignation issue du modèle | Case à cocher | Date automatique |

La colonne « N° attribué » est supprimée et ne doit pas être recréée.

### 6.3 Fonctionnement

- Le tableau apparaît à partir de la phase 3 et reste accessible en phase 4.
- Le même tableau et les mêmes lignes sont conservés entre les deux phases.
- Lorsque l'utilisateur coche la case, la date du jour est enregistrée automatiquement dans le fuseau horaire `Africa/Casablanca`.
- Le décochage ne doit pas effacer la trace de l'action précédente : l'historique conserve l'utilisateur, la date et l'action.
- Les lignes du modèle sont copiées dans le dossier du marché afin de figer le modèle utilisé pour ce dossier.
- Une modification ultérieure du modèle dans les paramètres ne modifie pas les tableaux des marchés déjà créés.
- La structure et les désignations du tableau d'un marché ne sont pas modifiables directement par les utilisateurs dans la fiche.
- Les cases de suivi restent utilisables pendant les phases 3 et 4. À la validation du dépôt et au passage effectif en phase 5, le tableau est verrouillé en consultation, tout en conservant son historique.
- Une correction exceptionnelle après verrouillage doit être contrôlée, justifiée et historisée ; elle ne doit pas réécrire l'historique antérieur.

Le libellé de la case doit être explicite : « Reçu / préparé » pour la phase 3 et « Déposé » ou « Inclus dans l'offre » en phase 4, tout en conservant la même ligne documentaire et la même traçabilité. Si cette distinction nécessite deux états séparés, elle doit être réalisée sans dupliquer le modèle ni perdre l'état de préparation.

## 7. Rubrique 4 — Dépôt de l'offre (phase 4)

| Champ | Obligatoire | Règle |
|---|---|---|
| Chargé de dépôt | Oui | Utilisateur responsable du dépôt |
| Date et heure de dépôt | Oui | Date et heure réelles du dépôt |
| Observations | Selon le contexte | Commentaires sur le dépôt |
| Preuve de dépôt | Recommandé | Récépissé, accusé ou preuve numérique |

Le passage à la phase 5 exige l'enregistrement du dépôt et de son responsable. Le tableau standard des documents reste visible pendant la phase 4 et se verrouille après la confirmation du dépôt.

## 8. Rubrique 5 — Résultat de l'offre (phase 5)

| Champ | Obligatoire | Règle |
|---|---|---|
| N° du marché | Conditionnel | Obligatoire si le résultat est attribué |
| Résultat de l'offre | Oui | Attribué, Perdu, Annulé ou Infructueux |
| Taux de retenue de garantie | Paramétré | Valeur contractuelle issue des paramètres et figée pour le marché |
| Montant de retenue de garantie | Calculé | Calcul automatique à partir du taux applicable et de la base contractuelle définie |

Informations complémentaires selon le résultat :

- Attribué : date de décision, montant attribué, numéro du marché et justificatif de la décision.
- Perdu : entreprise concurrente attributaire, montant gagnant et observations.
- Annulé : motif et document justificatif si disponible.
- Infructueux : explication / motif et document justificatif si disponible.

Le numéro du marché et les données contractuelles de retenue de garantie ne doivent pas être exigés pour un résultat perdu, annulé ou infructueux.

La base de calcul exacte de la retenue doit être explicitée dans les paramètres ou la règle contractuelle applicable. Le système ne doit pas supposer que le taux s'applique à une base différente de celle prévue au marché.

## 9. Paramètres — Retenue de garantie

Le taux de retenue de garantie est réglé dans les paramètres généraux de gestion des marchés.

Règles :

- le taux est défini dans les paramètres ;
- il est repris automatiquement dans le dossier ;
- le montant est calculé automatiquement à partir du taux et du montant contractuel de référence ;
- le taux appliqué est conservé comme instantané historique dans chaque marché ;
- une modification du taux général ne recalcule pas les marchés déjà enregistrés ;
- toute correction exceptionnelle d'un marché doit être autorisée et historisée.

Exemple de calcul indicatif : base contractuelle 1 000 000 DH × taux 7 % = retenue 70 000 DH. Cet exemple ne fixe pas le taux légal ou contractuel.

## 10. Rubrique 6 — Préparation du marché attribué (phase 6)

Cette rubrique n'est accessible qu'après validation officielle du résultat « Attribué ».

| Champ / action | Obligatoire | Règle |
|---|---|---|
| Documents restant à compléter | Oui | Liste de contrôle des pièces manquantes, avec état et justificatif |
| Observations de l'étape | Oui | Commentaire sur la préparation du marché |
| N° ordre de service | Oui | Référence de l'ordre de service |
| Date de l'ordre de service | Oui | Date figurant sur l'ordre de service |
| Date de notification | Oui | Date de notification officielle |
| Montant de la caution définitive (DH) | Oui | Montant contractuel |
| Préparer | Action / état | Préparation de la caution définitive |
| Obtenu | Action / état | Confirmation d'obtention de la caution définitive |
| Justificatif | Recommandé | Copie de la caution définitive |

Données complémentaires recommandées :

- date de signature du marché ;
- montant définitif du marché ;
- durée contractuelle confirmée ;
- date de démarrage contractuelle ;
- date de fin contractuelle prévue ;
- copie du marché signé ;
- copie de l'ordre de service ;
- responsable de préparation du dossier.

La liste des documents restant à compléter doit être une checklist structurée et non un simple champ texte. Chaque pièce peut être marquée présente, manquante ou non applicable, avec commentaire et fichier justificatif.

## 11. Rubrique 7 — Exécution et suivi du chantier (phase 7)

| Champ | Obligatoire | Règle |
|---|---|---|
| Début du suivi | Oui | Date réelle de début du suivi |

La phase 7 est un suivi continu et doit accepter plusieurs enregistrements datés, pas seulement une seule note.

Fonctions et informations recommandées :

- responsable du suivi ;
- date de démarrage effectif du chantier ;
- date de fin contractuelle prévue ;
- avancement physique en pourcentage ;
- montant des travaux exécutés ;
- situations de travaux et décomptes ;
- avenants et modifications ;
- prolongations de délai ;
- arrêts et reprises de chantier ;
- comptes rendus et observations ;
- réceptions provisoire et définitive ;
- documents et photos du chantier ;
- alertes sur les retards et échéances.

Chaque événement doit conserver son auteur, sa date, ses pièces jointes et son historique de modification.

## 12. Rubrique 8 — Clôture et archivage (phase 8)

| Champ | Obligatoire | Règle |
|---|---|---|
| Fin du suivi | Oui | Date effective de fin du suivi |

Éléments complémentaires recommandés :

- date de réception provisoire ;
- date de réception définitive ;
- décompte définitif ;
- montant final du marché ;
- situation de la retenue de garantie ;
- restitution des cautions ;
- réserves éventuelles et levée des réserves ;
- dossier final et pièces obligatoires ;
- date de clôture administrative ;
- observations finales.

La clôture exige un contrôle de complétude. Les éléments manquants doivent être signalés et justifiés. La clôture et l'archivage ne doivent pas effacer les données ni l'historique.

## 13. Renouvellement des marchés

Le renouvellement est géré lorsqu'un marché est déclaré renouvelable.

Champs et fonctions :

- indicateur renouvelable Oui / Non ;
- durée initiale en mois ;
- durée de renouvellement en années ;
- dates de début et de fin de chaque période ;
- possibilité d'enregistrer plusieurs renouvellements successifs, dans les limites contractuelles ;
- historique complet des périodes et décisions de renouvellement ;
- alertes configurables avant chaque échéance ;
- notification du responsable concerné ;
- pièces justificatives de chaque renouvellement.

Un renouvellement ne doit jamais écraser la période précédente. Chaque période est conservée dans l'historique.

## 14. Accès et permissions — règle initiale

La règle fonctionnelle initiale demandée est un accès total par défaut pour tous les utilisateurs actifs :

- accès aux paramètres fonctionnels ;
- accès aux rubriques et fonctions métier ;
- accès aux entreprises et marchés, sauf restriction explicitement configurée ;
- possibilité d'utiliser les fonctions de la fiche selon les règles métier.

Le système actuel dispose déjà d'un mécanisme de restrictions facultatives par utilisateur, entreprise et permission : aucune restriction n'est appliquée par défaut. Les restrictions futures doivent rester possibles sans retirer l'accès par défaut demandé.

La gestion des comptes, l'authentification, la sécurité des sessions et la configuration des restrictions d'accès doivent rester protégées contre les opérations non autorisées. L'accès total par défaut ne signifie pas la suppression des contrôles de connexion, CSRF, journalisation ou isolation des données.

## 15. Historique, documents et sécurité

Chaque opération importante doit être historisée :

- création et modification d'une donnée ;
- changement de phase ;
- qualification et validation du résultat ;
- préparation et obtention d'une caution ;
- coche / décoche d'une ligne du tableau documentaire ;
- dépôt de l'offre ;
- changement d'une version BPU ;
- renouvellement ;
- clôture et réouverture exceptionnelle.

L'historique contient au minimum : marché, action, ancienne et nouvelle valeur lorsque pertinent, utilisateur, date/heure, motif et référence du document justificatif.

Les documents doivent être stockés dans un emplacement non directement public, avec contrôle d'accès au téléchargement, nom de stockage sécurisé, validation du type et de la taille, et traçabilité des versions.

Les contrôles d'accès, de société, de permission et de jeton CSRF sont exécutés côté serveur. Les données d'une société ne doivent pas être exposées à un utilisateur d'une autre société en l'absence d'accès accordé.

## 16. Paramètres fonctionnels à prévoir

| Paramètre | Comportement |
|---|---|
| Taux de retenue de garantie | Valeur générale, avec conservation de la valeur appliquée par marché |
| Base de calcul de la retenue | Règle contractuelle à sélectionner/configurer |
| Modèle standard des documents | Liste de désignations et ordre des lignes |
| Caractère obligatoire des documents | Obligatoire / facultatif |
| Alertes de renouvellement | Délais configurables avant échéance |
| Numérotation des dossiers | Préfixe, année, compteur et format |
| Devises / affichage | MAD / DH par défaut |
| Paramètres BPU | Formats d'importation et règles de validation |

Les paramètres utilisés par un marché doivent être copiés ou figés dans le dossier afin qu'une modification ultérieure des paramètres généraux ne change pas rétroactivement les données historiques.

## 17. Décisions déjà validées

- Fiche complète organisée par phases.
- Informations générales toujours accessibles.
- Cycle visuel : 8 phases sur une seule ligne et 4 décisions sur une deuxième ligne.
- Tableau documentaire standard configuré dans les paramètres, visible en phases 3 et 4.
- Tableau documentaire limité à trois colonnes : désignation, case à cocher, date.
- Colonne « N° attribué » supprimée.
- Date du jour enregistrée automatiquement au clic sur la case.
- Modèle documentaire figé dans chaque dossier ; historique conservé.
- BPU : importation et gestion intégrée.
- Retenue de garantie : taux paramétré, montant calculé automatiquement et valeur appliquée conservée par marché.
- Renouvellement : alertes avant échéance et historique de chaque période.
- Accès fonctionnel total par défaut pour tous les utilisateurs actifs, sans restriction initiale.
- Aucun changement rétroactif des marchés lors de la modification des paramètres généraux.

## 18. Points techniques à vérifier avant développement

Les éléments suivants doivent être audités dans le schéma et le code avant de préparer les migrations :

1. Modèle des entités maître d'ouvrage et maître d'œuvre, contacts et adresses.
2. Champs détaillés du marché et numérotation automatique.
3. Paramètres généraux et versionnement des paramètres.
4. Table de modèle documentaire et copie figée des lignes par marché.
5. Historique des coches/décoches et verrouillage après dépôt.
6. BPU, lignes, imports, contrôles et versions.
7. Cautions provisoires et définitives, états et justificatifs.
8. Renouvellements multiples, périodes et alertes.
9. Événements de suivi de chantier, situations, avenants et réceptions.
10. Documents, stockage sécurisé, droits et historique.
11. Cohérence avec les migrations 001 à 004 et les données existantes.

## 19. Plan de réalisation

1. Auditer les tables SQL, les clés étrangères, les index et les fichiers applicatifs existants.
2. Produire un rapport d'écarts entre le schéma actuel et cette spécification.
3. Proposer un MCD/MLD complémentaire sans exécuter de SQL.
4. Présenter les migrations nécessaires pour approbation.
5. Développer progressivement les paramètres et les rubriques de la fiche.
6. Tester les règles, l'historique, les permissions, les imports et l'affichage responsive.
7. Exécuter les migrations uniquement après sauvegarde et autorisation explicite.

**Règle de sécurité :** ce cahier des charges est une spécification. Il n'autorise pas à exécuter une migration SQL, modifier la base réelle, supprimer des données ou toucher à `config/database.local.php`.
