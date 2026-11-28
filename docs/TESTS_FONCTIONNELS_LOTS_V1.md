Référence : erp-manager/docs/TESTS_FONCTIONNELS_LOTS_V1.md

# Plan de tests fonctionnels — lots indépendants V1

- Cible : PHP 8.x / MariaDB 10.4.32 / InnoDB
- Statut : plan de test préparé ; aucun test réel de base n'a été exécuté.
- Précondition : copie restaurable de la base, migrations 001 à 005 installées uniquement sur cette copie, code applicatif compatible déployé sur un environnement de test.

## 1. Compatibilité avant migration 005

- [ ] Vérifier qu'une base arrêtée à la migration 004 affiche encore les fiches et permet les phases 1→2→3→4→5.
- [ ] Vérifier que les qualifications, validations et phases 6 à 8 restent bloquées si la migration 004 est absente.
- [ ] Vérifier qu'aucune requête vers `market_lots`, `lot_id` ou `company_id` de qualification n'est exécutée lorsque la migration 005 est absente.
- [ ] Vérifier que la création d'un marché reste compatible avec le schéma antérieur à 005.

## 2. Migration 005 et initialisation

- [ ] Sauvegarder puis restaurer une copie de la base.
- [ ] Exécuter 005 uniquement sur la copie, après validation de ses précontrôles.
- [ ] Vérifier qu'un marché ancien reçoit exactement un lot n° 1.
- [ ] Vérifier que le lot n° 1 reprend le statut, la phase et le montant historique du marché.
- [ ] Vérifier que toutes les qualifications et transitions historiques sont rattachées au lot n° 1.
- [ ] Vérifier que les comptages avant/après correspondent et qu'aucune ligne n'a `lot_id` ou `company_id` NULL.
- [ ] Vérifier toutes les FK composites de lots, qualifications, transitions et résultat officiel.

## 3. Création et indépendance des lots

- [ ] Créer un nouveau marché après 005 et vérifier la création automatique du lot n° 1.
- [ ] Ajouter un lot n° 2 au même marché.
- [ ] Ajouter un lot n° 3 et vérifier la numérotation séquentielle.
- [ ] Vérifier qu'un utilisateur sans `markets.create` ne peut pas ajouter un lot.
- [ ] Faire avancer le lot n° 1 de la phase 1 à la phase 2 ; vérifier que les phases des lots n° 2 et 3 ne changent pas.
- [ ] Faire avancer le lot n° 2 indépendamment jusqu'à la phase 5.
- [ ] Vérifier que chaque fiche affiche uniquement l'état du lot sélectionné.

## 4. Qualifications et validations

- [ ] Créer une qualification en attente sur le lot n° 1.
- [ ] Vérifier qu'une deuxième qualification en attente est refusée sur ce même lot.
- [ ] Vérifier qu'une qualification peut être créée sur le lot n° 2 sans être bloquée par celle du lot n° 1.
- [ ] Refuser une qualification avec motif et vérifier que le statut officiel du lot ne change pas.
- [ ] Valider une attribution et vérifier que seul le lot concerné passe en phase 6 avec son montant et son identifiant de résultat officiel.
- [ ] Valider un résultat perdu, annulé ou infructueux et vérifier que seul le lot concerné est modifié.
- [ ] Tenter de valider l'identifiant d'une qualification d'un autre lot : l'opération doit être refusée.
- [ ] Vérifier que l'historique du lot ne mélange pas les événements des autres lots.

## 5. Réouverture, concurrence et isolation

- [ ] Vérifier que la réouverture est réservée à GEST et exige un motif.
- [ ] Vérifier que la réouverture d'un lot ne modifie aucun autre lot.
- [ ] Simuler deux qualifications simultanées sur le même lot : une seule doit pouvoir rester en attente.
- [ ] Simuler deux validations simultanées de la même qualification : une seule doit réussir.
- [ ] Vérifier qu'un utilisateur d'une société ne peut consulter, sélectionner ou modifier les lots d'une autre société.
- [ ] Vérifier qu'un identifiant de lot appartenant à un autre marché ne peut pas être utilisé dans une action POST.

## 6. Liste des marchés et parcours de navigation

- [ ] Vérifier qu'un marché avec un seul lot affiche l'état de ce lot.
- [ ] Vérifier qu'un marché avec plusieurs lots affiche « Plusieurs lots » et « Phases indépendantes » dans la liste.
- [ ] Vérifier que le filtre par statut retourne les marchés ayant au moins un lot dans ce statut.
- [ ] Vérifier que tous les formulaires de la fiche conservent le lot sélectionné après soumission.
- [ ] Vérifier que le bouton retour, le changement de lot et les redirections après action fonctionnent.

## 7. Critères de validation

La fonctionnalité lots ne sera considérée prête qu'après :
- réussite de tous les tests ci-dessus sur une copie de base ;
- contrôle des logs PHP et MariaDB sans erreur ;
- vérification des permissions et de l'isolation multi-sociétés ;
- confirmation de compatibilité MariaDB 10.4.32 ;
- validation du rendu dans le navigateur sur XAMPP.

**Important :** ne pas exécuter la migration 005 sur la base de travail ou de production pour réaliser ces tests. Le code présent dans GitHub ne prouve pas que la migration est installée.
