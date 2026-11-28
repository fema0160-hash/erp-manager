Référence : erp-manager/docs/AUDIT_ECARTS_FICHE_MARCHE.md

# Audit des écarts — fiche complète du marché

- Dépôt : `fema0160-hash/erp-manager`
- Branche : `main`
- Révision de référence : commit `d867e8b` (diagramme horizontal ajouté)
- Méthode : lecture statique des migrations SQL, du service métier, du contrôleur et des vues versionnés.
- Limite : aucun test d'exécution PHP/MariaDB ni aucune lecture de la base locale n'a été effectué dans cet audit.

## 1. Conclusion

La fiche détaillée et le cycle de vie de base existent. En revanche, le schéma et les écrans actuels ne couvrent pas encore la fiche complète définie dans `CAHIER_CHARGES_FICHE_MARCHE_COMPLETE.md`.

Les tables actuelles sont centrées sur les sociétés, les comptes, les permissions, les marchés avec quelques champs généraux, le cycle de vie et les validations de résultats. Les fonctionnalités BPU, tableau standard des documents, paramètres métier, cautions détaillées, renouvellements et suivi de chantier restent à concevoir.

Le diagramme horizontal des huit phases et des quatre décisions vient d'être ajouté à la vue de la fiche. Il s'agit d'une modification d'interface uniquement ; elle ne change pas le modèle de données ni les transitions.

## 2. État du schéma SQL observé

| Migration | Éléments présents |
|---|---|
| 001 | `companies` et `markets` avec référence, objet, société, ancien statut/phase, montant, date et indicateur de démonstration |
| 002 | `users`, `user_sessions`, `permissions`, `user_company_restrictions`, `user_permission_restrictions` |
| 003 | colonnes normalisées du cycle de vie, données de décision de base et `market_transitions` |
| 004 | `market_result_qualifications`, `market_result_validations`, liens d'historique et permissions du cycle |

La migration 004 est un fichier de migration préparé ; sa présence dans GitHub ne prouve pas qu'elle est installée dans la base locale ou en production. Son installation doit rester soumise à sauvegarde et autorisation explicite.

## 3. Écarts fonctionnels constatés

| Besoin validé | État dans le code consulté | Travail restant |
|---|---|---|
| Numéro de dossier automatique | Non présent ; le formulaire demande actuellement une référence saisie | Créer une numérotation automatique distincte du N° d'appel d'offres |
| N° appel d'offres | Non présent comme champ dédié | Ajouter au modèle de marché |
| Objet et entreprise | Présents | Conserver et harmoniser les libellés |
| Client / maître d'ouvrage | Non présent comme entité liée | Concevoir les organismes et leurs coordonnées |
| Maître d'œuvre | Non présent comme entité liée | Concevoir les intervenants et leurs coordonnées |
| Durée et renouvelable | Non présents | Ajouter durée initiale, renouvellements et périodes |
| Dates de consultation | Non présentes | Ajouter les dates de lancement, remise et ouverture |
| Charges d'étude | Non présentes | Ajouter le montant interne d'étude |
| Coût estimatif | Champ montant général existant, sans sémantique complète | Clarifier et séparer montant estimatif, offre et attribution |
| Taux de retenue paramétrable | Non présent | Créer paramètres et instantané de taux par marché |
| Caution provisoire | Non présente comme objet métier | Créer montant, état préparer/obtenu et justificatif |
| BPU importable et intégré | Non présent | Créer lignes BPU, import contrôlé et versionnement |
| Tableau standard documentaire | Non présent | Créer modèle paramétrable et copie figée par marché |
| Cases reçues et dates automatiques | Non présent | Créer états, dates et historique de coche/décoche |
| Dépôt de l'offre | Non présent | Ajouter responsable, date/heure, observations et preuve |
| Numéro du marché attribué | Non présent comme champ distinct | Ajouter au résultat attribué |
| Calcul de retenue de garantie | Non présent | Calculer selon le taux et la base contractuelle configurée |
| Caution définitive | Non présente comme objet métier | Ajouter montant, états et justificatifs |
| Ordre de service et notification | Non présents | Ajouter références et dates |
| Documents restant à compléter | Pas de checklist documentaire métier | Créer checklist et justificatifs |
| Suivi du chantier | Pas d'événements de chantier | Créer événements, responsables, avancement et pièces |
| Clôture détaillée | La phase 7→8 repose actuellement sur une attestation et une note | Ajouter contrôles de réception, décompte, cautions et dossier final |
| Renouvellements et alertes | Non présents | Créer historique des périodes et moteur d'alertes |
| Paramètres métier | Aucune route de paramètres fonctionnels trouvée dans `public/index.php` examiné | Concevoir l'écran et le stockage des paramètres |
| Accès initial total | Le service `Auth` accorde les droits par défaut et les restrictions sont facultatives | Conserver le comportement par défaut ; vérifier l'accès aux futurs paramètres |
| Diagramme du cycle | Ajouté dans `resources/views/market-detail.php` | Tester visuellement et vérifier le responsive sur XAMPP |

## 4. Éléments applicatifs existants à préserver

- `app/Auth.php` : authentification, sessions, CSRF, accès entreprise et permissions accordées par défaut.
- `app/MarketLifecycle.php` : transitions, qualifications, validations, réouverture et historique.
- `public/index.php` : contrôleur des routes et contrôles serveur.
- `resources/views/market-detail.php` : fiche détaillée, résultats, validations et historique.
- `resources/views/markets.php` : création et liste des marchés.
- Migrations 001 à 004 : schéma existant, à ne pas réécrire ni rejouer aveuglément.

Le rôle GEST conserve dans le code actuel la gestion des comptes et des restrictions. Les restrictions d'entreprise et de permissions ne sont pas actives par défaut. Le futur écran de paramètres fonctionnels doit être conçu sans retirer l'accès fonctionnel total initial demandé.

## 5. Ordre technique recommandé

### Étape A — Modèle de données et audit détaillé

1. Définir les entités maître d'ouvrage, maître d'œuvre et contacts.
2. Définir les champs complémentaires du marché et la numérotation automatique.
3. Définir le stockage/versionnement des paramètres.
4. Définir les modèles de documents et les lignes copiées dans chaque marché.
5. Définir BPU, lignes, imports et versions.
6. Définir cautions provisoires/définitives et retenues de garantie.
7. Définir renouvellements, périodes et alertes.
8. Définir événements de chantier et éléments de clôture.
9. Produire MCD/MLD et dictionnaire de données.

### Étape B — Préparation des migrations

- Préparer une ou plusieurs migrations additives, compatibles avec MariaDB 10.4.32.
- Ne pas supprimer ni renommer les colonnes historiques sans stratégie de compatibilité documentée.
- Prévoir les index, clés étrangères, contraintes et règles d'isolation multi-sociétés.
- Vérifier les migrations sur une copie de sauvegarde avant toute installation.
- Ne pas exécuter de migration sans autorisation explicite.

### Étape C — Implémentation progressive

1. Paramètres généraux et numérotation.
2. Fiche générale et données de consultation.
3. Modèle documentaire et checklist des phases 3/4.
4. BPU et import Excel.
5. Cautions et retenue de garantie.
6. Dépôt et résultat.
7. Marché attribué, ordre de service et documents.
8. Suivi de chantier.
9. Renouvellements et alertes.
10. Clôture, archivage et contrôles de complétude.

## 6. Contrôles à effectuer

- PHP lint sur tous les fichiers PHP modifiés.
- Tests de création et modification des marchés.
- Tests d'import BPU avec fichier valide, colonnes invalides et lignes rejetées.
- Tests du verrouillage du modèle documentaire et de l'historique des cases.
- Tests des calculs de retenue et de conservation des taux historiques.
- Tests de renouvellements multiples et alertes.
- Tests de permissions et d'isolation entre sociétés.
- Tests navigateur sur grand écran et téléphone.
- Tests MariaDB sur une copie de sauvegarde.

## 7. Sécurité et limites

- Aucun fichier de configuration local ou secret ne doit être modifié ou publié.
- Les documents téléversés doivent être stockés hors du répertoire public et servis après contrôle d'accès.
- Les opérations POST doivent vérifier CSRF et permissions côté serveur.
- L'accès total par défaut ne supprime pas l'authentification, la journalisation ou l'isolation des données.
- Les migrations 003 et 004 ne doivent pas être exécutées à nouveau si elles sont déjà installées.
- Le présent audit est une revue statique du dépôt, pas une validation de l'environnement XAMPP.
