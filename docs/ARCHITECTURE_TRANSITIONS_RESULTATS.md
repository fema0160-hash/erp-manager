Référence : erp-manager/docs/ARCHITECTURE_TRANSITIONS_RESULTATS.md

# Architecture des transitions et des résultats des marchés

## 1. Objet

Ce document prépare l'implémentation du cycle de vie des marchés dans ERP Manager. Il définit les règles métier et les responsabilités techniques avant toute modification du code de traitement ou du schéma SQL.

**Ce document ne contient pas de migration exécutable et n'autorise aucune modification de la base de données.**

## 2. Règles générales retenues

- Les phases décrivent l'avancement du dossier.
- Le statut décrit l'état officiel du marché.
- Une qualification de résultat est une proposition, pas encore une décision officielle.
- La validation confirme ou refuse cette proposition.
- Seule une qualification validée peut modifier le statut officiel.
- Tous les utilisateurs ayant accès au marché peuvent qualifier et valider par défaut.
- Par défaut, le même utilisateur peut qualifier puis valider. Un paramètre futur pourra imposer deux personnes différentes.
- Les droits restent limités aux sociétés et marchés auxquels l'utilisateur a accès.
- GEST conserve le droit de réouverture exceptionnelle, avec motif obligatoire et historique.

## 3. Parcours normal des phases

| Phase actuelle | Action autorisée | Phase suivante |
|---|---|---:|
| 1 — Préparation | Terminer la préparation | 2 |
| 2 — Étude et BPU | Terminer l'étude et le chiffrage | 3 |
| 3 — Préparation de la soumission | Finaliser le dossier | 4 |
| 4 — Soumis | Enregistrer le dépôt et attendre le résultat | 5 |
| 5 — Attente du résultat | Qualifier puis valider le résultat | Selon le résultat |
| 6 — Préparation du marché attribué | Valider les conditions de démarrage | 7 |
| 7 — Exécution / suivi du chantier | Valider la clôture du dossier | 8 |
| 8 — Clôturé / archivé | Consultation uniquement | Aucune |

Aucune phase ne peut être sautée. Les transitions ordinaires en arrière sont interdites. Une correction exceptionnelle passe par la réouverture GEST, motivée et historisée.

## 4. Qualification et validation du résultat

### 4.1 Qualification

À partir de la phase 5, un utilisateur autorisé peut soumettre une qualification parmi :

- attribue
- perdu
- annule
- infructueux

La qualification enregistre les données nécessaires et l'identité de son auteur. Elle reste en attente de validation.

Tant qu'elle n'est pas validée :
- le statut officiel reste inchangé ;
- la phase reste 5 ;
- aucune préparation d'exécution ne peut commencer ;
- la qualification reste identifiable comme proposition en attente.

### 4.2 Validation

Un utilisateur autorisé peut valider ou refuser la qualification.

- **Validée** : la décision devient officielle et ses effets sont appliqués au marché.
- **Refusée** : le statut officiel et la phase du marché restent inchangés ; le motif du refus est conservé. Une nouvelle qualification peut ensuite être déposée.
- **Nouvelle qualification** : elle crée un nouvel enregistrement ; elle ne remplace pas la proposition précédente.

Par défaut, aucune règle n'oblige à ce que le validateur soit une personne différente du qualificateur.

### 4.3 Données obligatoires

| Résultat proposé | Données obligatoires |
|---|---|
| Attribué | Date de décision et montant attribué |
| Perdu | Entreprise concurrente attributaire, montant retenu et observations |
| Annulé | Motif d'annulation |
| Infructueux | Motif ou explication |

Les contrôles sont exécutés côté serveur. Les contrôles HTML ne sont qu'une aide à la saisie.

## 5. Effets d'une validation

| Résultat validé | Statut officiel | Phase après validation | Suite |
|---|---|---:|---|
| Attribué | attribue | 6 | Phase 7 puis phase 8 |
| Perdu | perdu | 5 | Consultation uniquement |
| Annulé | annule | 5 | Consultation uniquement |
| Infructueux | infructueux | 5 | Consultation uniquement |

Les résultats négatifs ne donnent jamais accès aux phases 6, 7 ou 8.

Le passage de la phase 6 à 7, puis de 7 à 8, fera l'objet de contrôles métier dédiés. La clôture devra notamment vérifier la complétude du dossier final.

## 6. Proposition de modèle de données pour la future migration

Le schéma actuel contient les colonnes de décision directement dans markets et la table market_transitions. Il ne permet pas de conserver proprement plusieurs propositions successives, leurs refus et leurs validations distinctes.

La migration à préparer devra s'appuyer sur les éléments suivants.

### 6.1 Table market_result_qualifications

Une ligne représente une proposition de résultat. Les propositions successives sont conservées séparément.

Champs envisagés :
- identifiant ;
- identifiant du marché ;
- code du résultat proposé ;
- date de décision ;
- motif ;
- entreprise concurrente attributaire ;
- montant concurrent ;
- observations ;
- montant attribué ;
- utilisateur ayant qualifié ;
- date et heure de qualification.

Les champs spécifiques au résultat restent obligatoires selon le code proposé.

### 6.2 Table market_result_validations

Une ligne représente une action de validation ou de refus sur une qualification.

Champs envisagés :
- identifiant ;
- identifiant de la qualification ;
- décision (validee ou refusee) ;
- utilisateur ayant validé ou refusé ;
- date et heure ;
- motif de refus ou commentaire.

Une qualification ne peut recevoir qu'une seule décision finale. L'état est dérivé de la présence et de la valeur de cette décision : sans ligne de validation, elle est en attente ; avec une ligne, elle est validée ou refusée. Après un refus, une nouvelle qualification est créée. Le verrouillage du marché côté serveur empêche plusieurs qualifications simultanément en attente.

### 6.3 Lien avec le marché officiel et l'historique

- markets conservera les champs du résultat officiel pour la compatibilité avec le code existant.
- Un lien vers la qualification ayant produit le résultat officiel permet de retrouver la preuve de validation.
- market_transitions conserve les changements de phase et de statut et peut être reliée à la qualification concernée.
- Les clés étrangères composites garantissent que la qualification référencée appartient au même marché.
- Les événements de qualification et de validation seront historisés même lorsqu'ils ne changent pas encore la phase ou le statut. Dans ce cas, l'état de départ et d'arrivée de l'événement sera identique.
- Les événements historiques ne seront pas modifiables ni supprimables depuis l'interface ordinaire.

Ce modèle permet de conserver les propositions refusées, les propositions successives et la décision officielle sans confondre leurs états.

## 7. Permissions et paramètres futurs

### Règle initiale

Les utilisateurs qui ont accès au marché peuvent qualifier et valider. Le contrôle de l'accès à l'entreprise et du droit de consultation du marché doit être effectué à chaque action.

Le mécanisme actuel d'autorisations applique un accès général par défaut aux utilisateurs non-GEST, sauf restriction enregistrée. Il faudra vérifier explicitement les droits dédiés lors de l'implémentation.

### Paramètres à prévoir ultérieurement

Une rubrique de paramètres pourra configurer séparément :
- les rôles ou utilisateurs autorisés à qualifier ;
- les rôles ou utilisateurs autorisés à valider ;
- l'obligation ou non d'une validation ;
- l'obligation éventuelle d'un validateur différent du qualificateur.

Les valeurs par défaut resteront celles confirmées dans le présent document : tous les utilisateurs autorisés peuvent qualifier et valider, validation obligatoire, même personne autorisée à effectuer les deux actions.

## 8. Sécurité et intégrité

Chaque action devra respecter les contrôles suivants :

1. Authentification obligatoire.
2. Vérification de l'accès à la société du marché.
3. Vérification de l'accès au marché et de la permission correspondant à l'action.
4. Vérification du jeton CSRF pour toute action POST.
5. Validation stricte des codes, montants, dates et champs obligatoires côté serveur.
6. Verrouillage du marché et de la qualification concernée pendant les opérations concurrentes.
7. Transaction SQL unique pour enregistrer la validation, mettre à jour le résultat officiel et ajouter l'historique.
8. Aucun passage en phase 6 sans attribution validée.
9. Aucun passage en phase 7 ou 8 sans respect des contrôles de phase.
10. Réouverture exceptionnelle réservée à GEST, avec motif obligatoire.

Les contrôles d'autorisation ne doivent jamais dépendre uniquement de l'affichage ou du masquage d'un bouton.

## 9. Ordre d'implémentation proposé

1. Faire relire et approuver la migration 004, puis l'installer uniquement après autorisation explicite.
2. Ajouter les méthodes métier de progression des phases 1 à 5.
3. Ajouter la création d'une qualification et son historique.
4. Ajouter la validation et le refus, avec application atomique du résultat officiel.
5. Ajouter les transitions 6 → 7 → 8 et leurs contrôles.
6. Ajouter la fiche détaillée du marché et les écrans d'action.
7. Ajouter l'affichage de l'historique complet.
8. Réaliser les tests automatisés et les tests sur une copie de la base avant toute mise en service.

## 10. État et limites

À la date de rédaction :
- le service central MarketLifecycle gère la création initiale, les transitions 1→5 et 6→8, la qualification, la validation/refus et la réouverture GEST ;
- les propositions et validations sont enregistrées séparément et appliquées dans des transactions ;
- l'historique détaillé est consultable dans la fiche du marché ;
- les actions disposent de permissions dédiées, ouvertes par défaut aux utilisateurs ayant accès au marché ;
- la migration 004 a été préparée et corrigée dans `database/migrations/004_result_qualification_validation.sql` ;
- la migration 004 n'a pas été exécutée et attend une approbation explicite ;
- les tests PHP, navigateur et MariaDB réels restent à effectuer.

**Toute création ou exécution d'une nouvelle migration SQL fera l'objet d'une validation explicite préalable.**
