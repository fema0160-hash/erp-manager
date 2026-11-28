Référence : erp-manager/docs/AUDIT_FONCTIONNEL_CYCLE_VIE_MARCHES.md

# Audit fonctionnel du cycle de vie des marchés

## 1. Périmètre et méthode

- Dépôt examiné : `fema0160-hash/erp-manager`
- Branche : `main`
- Révision examinée : `fe4d2f0b91d3a25534d58dd9611ca8e89f944d0f`
- Méthode : revue statique des fichiers présents sur GitHub.
- Aucun fichier applicatif, script SQL, paramètre, branche ou donnée de base n'a été modifié pendant l'audit.

**Limite importante :** cet audit porte sur le code versionné dans GitHub. Il ne constitue pas un test d'exécution sur XAMPP et ne permet pas de confirmer l'état réel de la base locale, les journaux PHP ni le comportement en production.

## 2. Fichiers examinés

| Fichier | Objet de l'examen |
|---|---|
| `public/index.php` | Routes, création et liste des marchés, filtrage, contrôles serveur |
| `app/Auth.php` | Authentification, accès entreprise, permissions et CSRF |
| `resources/views/markets.php` | Formulaire de création, filtres, liste, libellés des phases et statuts |
| `public/assets/js/dashboard.js` | Phases, statuts, filtres, tableau de bord et affichage des marchés |
| `database/migrations/001_create_demo_tables.sql` | Structure initiale de `markets`, anciens statuts et anciennes phases |
| `database/migrations/002_create_auth_tables.sql` | Rôles, permissions et restrictions par utilisateur/entreprise |
| `database/migrations/003_market_lifecycle.sql` | Nouvelles colonnes, contraintes, données de décision et table d'historique |
| `docs/CYCLE_VIE_MARCHES.md` | Règles fonctionnelles de référence |
| `docs/AUDIT_FONCTIONNEL_POST_MIGRATION_003.md` | Résultats et réserves de l'audit précédent |

L'arborescence de la révision examinée ne contient pas de classe `app/MarketLifecycle.php`, ni de dossier de contrôleurs ou de services métier dédié au cycle de vie.

## 3. Résumé exécutif

**Le schéma SQL du cycle de vie est présent, mais le cycle de vie n'est pas implémenté dans l'application.**

Le code PHP continue à créer, lire et filtrer les marchés à partir des anciennes colonnes `markets.status` et `markets.phase`. L'interface continue à afficher les cinq anciens statuts et les six anciennes phases. Les nouvelles colonnes `lifecycle_status` et `lifecycle_phase` ainsi que la table `market_transitions` ne sont pas exploitées par les traitements applicatifs examinés.

La création d'un marché après migration introduit en outre une incohérence : le code enregistre l'ancien statut `en_attente`, tandis que les nouvelles colonnes prennent leurs valeurs par défaut SQL (`en_cours` et phase 1). L'historique applicatif de création n'est pas enregistré.

## 4. Fonctionnalités constatées

| Fonctionnalité | Résultat | Constat |
|---|---|---|
| Connexion obligatoire pour accéder aux marchés | OPÉRATIONNEL | `requireLogin()` est appelé sur les routes marchés |
| Consultation de la liste | PARTIEL | Liste et filtres présents, mais basés sur les anciennes colonnes |
| Création d'un marché | PARTIEL | Formulaire, CSRF, validation des champs et contrôle de permission présents |
| Filtrage par entreprise | OPÉRATIONNEL DANS LE CODE EXAMINÉ | Les identifiants d'entreprises autorisés sont utilisés dans la requête |
| Filtrage par ancien statut | PARTIEL | Seuls les cinq anciens statuts sont acceptés |
| Affichage des nouvelles phases 1 à 8 | ABSENT | L'interface affiche encore six phases historiques |
| Affichage des sept statuts normalisés | ABSENT | L'interface affiche encore les anciens codes |
| Fiche détaillée d'un marché | ABSENT | Aucune route de détail n'est présente dans le routeur examiné |
| Modification d'un marché | ABSENT | Aucun traitement applicatif d'édition n'est présent |
| Suppression fonctionnelle d'un marché | ABSENT | Aucun traitement de suppression n'est présent |
| Décision d'attribution/perte/annulation/infructuosité | ABSENT | Aucun formulaire ou endpoint de décision n'est présent |
| Transition de phase contrôlée | ABSENT | Aucun service ni endpoint de transition n'est présent |
| Historique des transitions | ABSENT | Aucune lecture ou écriture applicative de `market_transitions` n'est présente |
| Réouverture exceptionnelle par GEST | ABSENT | Aucun traitement de réouverture avec motif n'est présent |

## 5. Écart entre la migration 003 et le code applicatif

### 5.1 Colonnes et statuts

La migration 003 ajoute notamment :

- `lifecycle_status` ;
- `lifecycle_phase` ;
- `decision_date` ;
- `decision_reason` ;
- `winner_company_name` ;
- `winner_amount` ;
- `decision_observations` ;
- `awarded_amount`.

Elle définit les statuts normalisés `en_cours`, `attribue`, `perdu`, `annule`, `infructueux`, `cloture` et `a_qualifier`, ainsi que les phases de 1 à 8.

Cependant, dans `public/index.php`, la liste des marchés sélectionne encore `m.status` et `m.phase`. Le tableau de bord sélectionne également ces anciennes colonnes. La vue `resources/views/markets.php` et le JavaScript `public/assets/js/dashboard.js` utilisent les anciens codes.

### 5.2 Incohérence à la création

Dans `public/index.php`, la création initialise :

- ancien statut : `en_attente` ;
- ancienne phase : 1 ;
- insertion : colonnes historiques uniquement.

La requête ne renseigne pas `lifecycle_status` ni `lifecycle_phase`. Après migration 003, les valeurs par défaut SQL donnent donc `en_cours` et phase 1 aux nouvelles colonnes, alors que l'interface et l'ancien champ indiquent « En préparation » via `en_attente`.

La création n'insère pas non plus de ligne dans `market_transitions`. Il existe donc un risque de divergence entre les deux modèles de statut dès le premier enregistrement créé après migration.

### 5.3 Libellés de phases divergents

Les trois représentations ne sont pas harmonisées :

- `docs/CYCLE_VIE_MARCHES.md` définit huit phases fonctionnelles ;
- `resources/views/markets.php` affiche six anciens libellés ;
- `public/assets/js/dashboard.js` affiche également six phases historiques.

Le tableau de bord ne représente donc pas le cycle normalisé prévu par la documentation.

### 5.4 Historique

La migration crée `market_transitions` et y enregistre les valeurs historiques au moment de la migration. En revanche, aucun traitement applicatif examiné ne crée de ligne pour une création, un changement de phase, une décision, une clôture ou une réouverture.

La présence de la table ne suffit donc pas à garantir un historique vivant et complet.

## 6. Permissions et isolation multi-entreprises

### Points conformes dans le code examiné

- Les routes marchés exigent une session authentifiée.
- La liste des entreprises accessibles est filtrée avec `hasCompanyAccess()` et `hasPermission(..., 'markets.view')`.
- La création vérifie `markets.create` pour l'entreprise sélectionnée.
- La création vérifie à nouveau côté serveur que l'entreprise appartient à la liste autorisée.
- Le formulaire POST vérifie le jeton CSRF.
- La requête de liste limite les résultats aux identifiants d'entreprises autorisés.
- Le tableau de bord applique également les contrôles d'accès entreprise et permission.

### Réserves

- `Auth::hasPermission()` autorise GEST sans restriction et autorise par défaut SECR/CHAD sauf restriction enregistrée. Les deux rôles ont donc le même comportement de base ; la différenciation repose sur les restrictions individuelles par entreprise et permission.
- Les permissions `markets.update` et `markets.delete` existent dans les données de référence, mais aucun endpoint correspondant n'a été trouvé dans le code examiné.
- Les contrôles de permission pour une future décision, transition, clôture ou réouverture ne peuvent pas être validés : ces actions n'existent pas encore.
- Le fait que GEST puisse tout consulter et administrer est cohérent avec le contournement global prévu dans `Auth::hasPermission()`. La règle de réouverture réservée à GEST reste toutefois à implémenter explicitement.

Aucune faille d'accès inter-entreprises n'a été démontrée dans les requêtes de consultation examinées. Les contrôles des futures actions d'écriture restent à construire et à tester.

## 7. Règles métier et contraintes SQL

La migration 003 prévoit des contraintes de présence pour certaines décisions :

- `perdu` : entreprise gagnante concurrente, montant du concurrent et observations ;
- `annule` et `infructueux` : motif obligatoire ;
- `attribue` : montant attribué et date de décision obligatoires.

Ces contraintes protègent certaines données au niveau SQL, mais elles ne remplacent pas les validations métier côté serveur. Aucun formulaire ni endpoint applicatif n'a été trouvé pour collecter et enregistrer ces décisions.

Aucun contrôle applicatif n'a été trouvé pour imposer les transitions autorisées, interdire le saut de phase, bloquer les modifications ordinaires des résultats définitifs ou exiger un motif de réouverture.

## 8. Tableau des corrections nécessaires

| Priorité | Correction | Motif |
|---|---|---|
| P1 | Faire des colonnes `lifecycle_*` la référence applicative | Éviter deux états concurrents |
| P1 | Corriger la création pour enregistrer un état initial cohérent | Éviter la divergence `en_attente` / `en_cours` |
| P1 | Créer un service métier central de cycle de vie | Centraliser et sécuriser les transitions |
| P1 | Implémenter les contrôles serveur GEST/SECR/CHAD et entreprise pour chaque action | Empêcher les changements non autorisés |
| P1 | Enregistrer chaque transition dans `market_transitions` dans la même transaction que la modification du marché | Garantir la traçabilité |
| P2 | Implémenter la qualification des dossiers `a_qualifier` et les formulaires de décision | Collecter les informations obligatoires |
| P2 | Implémenter la fiche détaillée et l'affichage de l'historique | Permettre le suivi et la vérification |
| P2 | Harmoniser les phases et statuts dans la liste et le tableau de bord | Éviter des libellés et filtres contradictoires |
| P2 | Implémenter la clôture et le verrouillage des résultats définitifs | Respecter les règles de consultation seule |
| P2 | Implémenter la réouverture GEST avec motif obligatoire et historisation | Encadrer les exceptions |
| P3 | Ajouter des tests automatisés et des tests d'intégration multi-entreprises | Vérifier les scénarios et prévenir les régressions |

## 9. Plan d'implémentation recommandé

1. **Préparation technique** : établir une copie de test de la base, vérifier les colonnes et données réelles après migration 003, puis définir une seule source de vérité applicative.
2. **Service central** : implémenter les règles de phases, statuts, transitions autorisées, décisions et réouverture dans une classe métier dédiée.
3. **Sécurité serveur** : contrôler à chaque requête la connexion, le rôle, la permission et l'accès à l'entreprise concernée.
4. **Historique transactionnel** : écrire la modification du marché et sa transition dans une seule transaction SQL.
5. **Interface** : ajouter la fiche marché, les actions autorisées, les formulaires de décision et l'historique ; harmoniser les filtres et tableaux de bord.
6. **Tests sur copie** : couvrir les transitions valides et invalides, les champs obligatoires, les rôles, les restrictions d'entreprise, les doublons de requête, les erreurs SQL et la réouverture.
7. **Validation** : présenter le bilan des tests et les changements SQL éventuels avant toute intervention sur la base principale.

## 10. Tests obligatoires avant mise en service

- Création d'un marché et vérification de la cohérence entre colonnes historiques et normalisées.
- Vérification de l'état initial et de l'historique associé.
- Transitions successives phase 1 → 2 → 3 → 4 → 5.
- Décision attribué, puis passage phase 5 → 6 → 7 → 8.
- Décisions perdu, annulé et infructueux avec champs obligatoires.
- Rejet des transitions qui sautent une phase ou reviennent en arrière.
- Blocage de toute modification ordinaire d'un résultat définitif.
- Réouverture avec GEST et motif ; refus pour SECR et CHAD.
- Refus d'accès à un marché d'une entreprise restreinte, même en modifiant manuellement l'identifiant envoyé.
- Respect des permissions individuelles pour consulter, créer et modifier.
- Vérification que chaque action et son historique sont atomiques : aucune mise à jour sans transition correspondante.
- Vérification des erreurs CSRF, des données invalides et des accès non authentifiés.

## 11. Verdict

**NON CONFORME — cycle de vie applicatif non implémenté.**

Le dépôt contient la migration SQL et la documentation de référence, mais l'application utilise encore l'ancien modèle de six phases et cinq statuts. La création, les décisions, les transitions, l'historique applicatif et la réouverture ne sont pas implémentés.

Les contrôles d'authentification, de consultation, de création et d'isolation des entreprises sont présents dans les traitements examinés. Ils ne suffisent toutefois pas à rendre opérationnel le nouveau cycle de vie.

**Aucune correction PHP ou SQL, aucune migration et aucune modification de base de données n'ont été effectuées pendant cet audit.**
