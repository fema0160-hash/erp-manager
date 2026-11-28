Référence : erp-manager/docs/PLAN_TESTS_CYCLE_VIE_MARCHES.md

# Plan de tests — cycle de vie des marchés

## Préconditions

- Travailler sur une copie de la base locale, jamais directement sur la base de production.
- Vérifier que les migrations 001, 002 et 003 sont installées.
- Installer la migration 004 uniquement après sauvegarde et autorisation explicite.
- Utiliser au minimum un compte GEST, un SECR et un CHAD, ainsi que deux sociétés pour vérifier l'isolation.

## Transitions des phases

1. Créer un marché : vérifier statut `en_cours`, phase 1 et événement `creation`.
2. Avancer successivement : 1→2, 2→3, 3→4, 4→5.
3. Vérifier qu'un saut de phase, un retour arrière et une transition depuis un statut négatif sont refusés.
4. Vérifier qu'un utilisateur sans accès à la société ne peut ni ouvrir la fiche ni envoyer une action.

## Qualification et validation

5. En phase 5, soumettre successivement chaque résultat :
   - attribué : date de décision et montant obligatoires ;
   - perdu : concurrent, montant et observations obligatoires ;
   - annulé : motif obligatoire ;
   - infructueux : motif obligatoire.
6. Vérifier qu'une qualification seule ne change ni le statut ni la phase officielle.
7. Vérifier qu'une seconde qualification en attente est refusée.
8. Refuser une qualification sans motif : l'action doit être rejetée.
9. Refuser une qualification avec motif : la proposition reste historisée et le marché reste en phase 5.
10. Créer une nouvelle qualification après refus.
11. Valider chaque type de résultat : seul le résultat validé devient officiel.
12. Vérifier qu'une qualification ne peut pas être validée deux fois et qu'une attribution validée seule ouvre la phase 6.

## Phases 6 à 8

13. En phase 6, vérifier que le passage en phase 7 exige une note sur les conditions de démarrage.
14. En phase 7, vérifier que le passage en phase 8 exige la case de confirmation et une note de contrôle de complétude.
15. Vérifier que la clôture fixe le statut `cloture` et que le dossier reste consultable.

## Permissions et historique

16. Vérifier que SECR et CHAD autorisés peuvent qualifier et valider par défaut.
17. Dans Utilisateurs et droits, retirer le droit de qualification à un compte sur une société et vérifier le refus côté serveur.
18. Répéter pour la validation et l'avancement des phases.
19. Vérifier que seul GEST voit et peut exécuter la réouverture.
20. Réouvrir un marché avec motif ; vérifier que les anciennes décisions restent visibles et que le marché revient en phase 5.
21. Vérifier chaque événement : action, ancien/nouvel état, utilisateur, date/heure, motif et qualification liée.

## Concurrence et intégrité

22. Envoyer deux qualifications presque simultanément : une seule doit rester en attente.
23. Envoyer deux validations simultanément sur la même qualification : une seule doit réussir.
24. Provoquer une erreur pendant une validation : aucune mise à jour partielle du marché, de la validation ou de l'historique ne doit subsister.
25. Vérifier la cohérence des colonnes normalisées `lifecycle_status/lifecycle_phase` et historiques `status/phase`.

## État

Ce document est un plan de test. Aucun des scénarios ci-dessus n'est déclaré exécuté. Les tests doivent être réalisés sur XAMPP avec une copie de sauvegarde après installation contrôlée de la migration 004.
