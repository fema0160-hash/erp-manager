Référence : erp-manager/docs/REGLES_QUALIFICATION_VALIDATION_RESULTATS.md

# Qualification et validation des résultats des marchés

## 1. Décision fonctionnelle retenue

Par défaut, tous les utilisateurs disposant d'un accès au marché peuvent qualifier le résultat et valider cette qualification.

La qualification et la validation sont deux actions distinctes. Elles ne sont pas réservées à GEST par défaut.

Le paramétrage ultérieur permettra de choisir les rôles ou utilisateurs autorisés à :
- qualifier un résultat ;
- valider un résultat ;
- éventuellement imposer une validation par GEST ou par un autre profil.

Jusqu'à la mise en place de ce paramétrage, la règle par défaut est l'accès à ces deux actions pour tous les utilisateurs autorisés sur l'entreprise et le marché concernés.

## 2. Déroulement

1. Le marché atteint la phase 5 — Attente du résultat.
2. Un utilisateur autorisé renseigne une qualification : attribué, perdu, annulé ou infructueux.
3. Les champs obligatoires correspondant au résultat sont contrôlés côté serveur.
4. Un utilisateur autorisé valide la qualification.
5. La décision validée devient le résultat officiel du marché et détermine la suite du cycle.

La qualification seule ne doit pas déclencher la phase 6. Seule une qualification validée « attribué » permet le passage en phase 6.

## 3. Données obligatoires

- Attribué : date de décision et montant attribué.
- Perdu : entreprise concurrente attributaire, montant retenu et observations.
- Annulé : motif obligatoire.
- Infructueux : motif / explication obligatoire.

Les pièces justificatives peuvent être associées au dossier selon les capacités documentaires disponibles.

## 4. Conséquences des résultats validés

- Attribué : phase 5 vers phase 6, puis phase 7 et phase 8 selon les contrôles prévus.
- Perdu : reste en phase 5, dossier consultable et modification ordinaire bloquée.
- Annulé : reste en phase 5, dossier consultable et modification ordinaire bloquée.
- Infructueux : reste en phase 5, dossier consultable et modification ordinaire bloquée.

Les trois résultats négatifs ne doivent jamais ouvrir les phases 6 à 8.

## 5. Historique et sécurité

Chaque qualification et chaque validation doivent être historisées séparément avec :
- le marché et l'entreprise concernés ;
- l'action effectuée ;
- l'ancien et le nouvel état ;
- l'utilisateur ayant effectué l'action ;
- la date et l'heure ;
- le motif et les informations de décision.

Les contrôles d'accès à l'entreprise et au marché sont appliqués à chaque lecture et action. Les requêtes POST doivent vérifier le jeton CSRF. Les transitions sont exécutées dans une transaction afin que la mise à jour du marché et l'écriture de l'historique soient atomiques.

Une réouverture exceptionnelle d'un résultat définitif reste réservée à GEST, avec motif obligatoire et historisation.

## 6. Paramétrage ultérieur

Une rubrique de paramètres permettra de configurer indépendamment :
- qui peut qualifier ;
- qui peut valider ;
- si la validation est obligatoire ;
- si le validateur doit être différent du qualificateur.

Valeur initiale : tous les utilisateurs autorisés peuvent qualifier et valider ; validation requise avant application du résultat au cycle.

## 7. Prérequis technique avant activation

Le schéma SQL actuellement documenté contient les données de décision et une table d'historique des transitions, mais ne distingue pas encore explicitement l'état « qualifié » de l'état « validé » ni leurs auteurs respectifs.

Une évolution de schéma devra donc être préparée pour conserver distinctement la qualification, la validation et leurs traces. Cette évolution fera l'objet d'une migration séparée, présentée pour approbation explicite avant toute exécution sur une base de données.

Aucune opération SQL ni modification de base de données n'est autorisée par le présent document.
