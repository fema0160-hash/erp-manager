Référence : erp-manager/docs/ETAPE_2_TRANSITIONS_QUALIFICATION_VALIDATION.md

# Étape 2 — Transitions, qualification, validation et historique

## 1. Éléments livrés dans le code

- Service central `app/MarketLifecycle.php` :
  - transitions séquentielles 1→2→3→4→5 ;
  - passage 6→7 avec note obligatoire sur les conditions de démarrage ;
  - passage 7→8 avec confirmation explicite et note de contrôle de complétude ;
  - création d'une qualification sans effet sur le résultat officiel ;
  - validation ou refus d'une qualification ;
  - application atomique du résultat officiel après validation ;
  - réouverture exceptionnelle par GEST avec motif ;
  - synchronisation des colonnes normalisées et historiques ;
  - écriture des événements d'historique dans la même transaction.
- Contrôleur `public/index.php` :
  - contrôle de connexion, CSRF, accès entreprise/marché et permissions ;
  - vérification de présence des tables de migration 004 ;
  - refus des actions si la migration 004 n'est pas installée.
- Nouvelle fiche `resources/views/market-detail.php` :
  - consultation de la fiche ;
  - actions de progression ;
  - formulaire de qualification selon le résultat ;
  - validation/refus ;
  - historique des propositions, décisions et transitions ;
  - réouverture GEST.
- Liste des marchés : le numéro du marché ouvre maintenant sa fiche détaillée.
- Migration 004 : ajout des permissions dédiées :
  - `markets.lifecycle.advance`
  - `markets.results.qualify`
  - `markets.results.validate`
  - `markets.lifecycle.reopen`

## 2. Règles appliquées

- Les phases 1 à 5 avancent une par une.
- Une qualification n'est pas une décision officielle.
- Une seule qualification en attente est autorisée par marché.
- Une validation positive rend le résultat officiel.
- Un refus exige un motif et conserve la proposition dans l'historique.
- Après un refus, une nouvelle qualification peut être créée.
- Seule une attribution validée ouvre la phase 6.
- Les résultats perdu, annulé et infructueux restent en phase 5 et ne peuvent pas avancer vers l'exécution.
- Les phases 6→7 et 7→8 exigent respectivement une note de démarrage et une attestation de contrôle de complétude.
- La réouverture est réservée à GEST et exige un motif ; l'historique antérieur est conservé.
- Par défaut, tous les utilisateurs ayant accès au marché peuvent qualifier et valider. Les restrictions de permissions par entreprise sont appliquées.

## 3. Limite du contrôle de clôture

Le projet ne dispose pas encore d'un module complet de pièces et de checklist de clôture. Le passage 7→8 exige donc une confirmation explicite et une description des éléments vérifiés. Cette étape est une attestation de l'utilisateur ; elle ne prétend pas vérifier automatiquement la présence de chaque pièce.

## 4. Prérequis de base de données

La fiche détaillée reste consultable après la migration 003. Les nouvelles actions et l'historique détaillé exigent la migration 004.

La migration 004 n'a pas été exécutée. Aucune base de données n'a été modifiée pendant cette étape. Ne pas activer le workflow avant son installation contrôlée sur une copie de sauvegarde.

## 5. Vérifications effectuées et restantes

Effectué :
- relecture des migrations 001 à 004 et des types de clés étrangères ;
- contrôle statique des fichiers modifiés après leur enregistrement sur GitHub ;
- contrôle de présence des contrôles CSRF, d'accès entreprise et des permissions ;
- vérification que la création initiale conserve la compatibilité avec le schéma 003.

À effectuer avant mise en service :
- lint PHP sur l'environnement XAMPP ;
- exécution de la migration 004 sur une copie de la base ;
- tests des huit transitions, des quatre résultats, des refus et des réouvertures ;
- tests multi-utilisateurs et restrictions d'entreprise ;
- tests de concurrence et de rollback ;
- tests navigateur sur ordinateur et mobile.

**Statut : code préparé sur GitHub ; tests d'exécution et migration SQL non effectués.**
