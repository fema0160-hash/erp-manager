Référence : erp-manager/docs/AUDIT_COHERENCE_MCD_MLD_V1.md

# Audit de cohérence MCD / MLD — fiche marché

- Dépôt : `fema0160-hash/erp-manager`
- Branche : `main`
- Cible : MariaDB 10.4.32 / InnoDB / utf8mb4
- Révision : MCD/MLD consolidés après arbitrages métier
- Méthode : lecture statique des migrations 001 à 006, du service `MarketLifecycle`, du contrôleur `public/index.php), du MCD et du MLD versionnés.
- Limite : aucune connexion à la base XAMPP ou distante ; aucun SQL exécuté ; aucune preuve d'installation des migrations locales ou de production.

## 1. Conclusion

**Conception consolidée sur les décisions métier reçues ; migrations 005 et 006 préparées en brouillon. Aucune migration n'est autorisée à l'exécution.**

Les décisions relatives aux lots, à la TVA, à la retenue TTC, aux cautions successives et au périmètre des paramètres sont intégrées dans le MCD/MLD.

Le contrôle statique confirme les types fondamentaux des migrations 001 à 004 et la cohérence des principales clés candidates proposées. Il reste néanmoins une étape d'audit SQL ligne par ligne sur les migrations 007 et suivantes, ainsi qu'un essai sur copie de base, avant toute demande d'installation.

## 2. Décisions métier consolidées

1. **Lots** : création automatique d'un lot n° 1 à la création du marché. Possibilité d'ajouter plusieurs lots. Résultat, validation, phases, BPU, attribution, cautions, contrat, retenue et suivi d'exécution sont indépendants par lot.
2. **BPU / TVA** : calcul par ligne en HT, TVA et TTC. Le taux de TVA est conservé par ligne ; les montants sont recalculés côté serveur. Le total de chaque version est la somme des lignes.
3. **Retenue de garantie** :
   - prélèvement initial : 10 % du TTC de chaque situation de travaux validée ;
   - plafond cumulé : 7 % du montant TTC initial attribué au lot ;
   - dernier prélèvement limité au solde disponible avant plafond ;
   - taux et plafond modifiables dans les paramètres propres à chaque société ;
   - instantanés et événements de calcul conservés ; aucune modification rétroactive.
4. **Cautions** : plusieurs cautions successives provisoires ou définitives sont conservées. Une caution remplacée n'est jamais écrasée.
5. **Paramètres** : paramètres par société en V1. Les paramètres communs au groupe sont reportés à une étape ultérieure.

## 3. Contrôle des migrations 001 à 004

### Migration 001 — sociétés et marchés

- `companies.id`, `markets.id` et `markets.company_id` sont en `INT UNSIGNED`.
- `markets.id` est la clé primaire ; `markets.company_id` référence `companies.id`.
- `markets.reference` est globalement unique dans le schéma initial et reste conservé pour compatibilité.
- La clé candidate `UNIQUE(markets.id, markets.company_id)` n'existe pas dans 001 ; elle est ajoutée par la migration 005 pour les relations composites.

### Migration 002 — utilisateurs et permissions

- `users.id` et `permissions.id` sont en `INT UNSIGNED`.
- Les restrictions d'accès sont facultatives ; les permissions fonctionnelles sont accordées par défaut par le service d'authentification.
- Les références d'audit vers les utilisateurs utilisent `ON DELETE RESTRICT` dans les nouvelles tables afin de préserver l'historique.

### Migration 003 — cycle de vie

- Ajoute `lifecycle_status` et `lifecycle_phase` à `markets`.
- Crée `market_transitions` avec `id BIGINT UNSIGNED`, `market_id INT UNSIGNED) et `changed_by INT UNSIGNED`.
- Les anciennes colonnes `status` et `phase` sont conservées.
- Le code actuel gère le cycle au niveau du marché ; le futur code doit déplacer l'état opérationnel au niveau du lot sans perdre l'état historique.

### Migration 004 — qualification et validation

Fichier : `database/migrations/004_result_qualification_validation.sql`.

- Crée `market_result_qualifications` et `market_result_validations`.
- Ajoute `qualification_id` à `market_transitions) et `official_result_qualification_id` à `markets`.
- Les références composites qualification/marché sont cohérentes dans le fichier.
- Le contrôleur vérifie la présence des deux tables, des deux colonnes, des deux contraintes et des quatre permissions.
- La présence du fichier dans Git ne prouve pas son installation en base.

**Disponibilité confirmée dans le code actuel :**
- Sans migration 004, l'avancement séquentiel des phases 1→2→3→4→5 reste autorisé.
- Sans migration 004, les qualifications, validations et les opérations des phases 6 à 8 sont bloquées avec un message indiquant que la migration 004 est requise.
- L'installation de 004 reste soumise à une sauvegarde vérifiée et à une autorisation explicite.

**Limite fonctionnelle actuelle :** la version courante de `MarketLifecycle` qualifie encore un résultat au niveau du marché. La gestion indépendante par lot nécessite la migration 005 ET une version applicative adaptée. La migration 004 n'est pas réécrite rétroactivement : elle reste le prérequis du mécanisme de qualification ; 005 lui ajoute le rattachement aux lots.

## 4. Contrôle des nouvelles migrations préparées

### Migration 005 — `005_market_lots_independent.sql`

**Statut : BROUILLON — NON EXÉCUTÉ**

- Ajoute la clé candidate `UNIQUE(markets.id, markets.company_id)`.
- Crée `market_lots` avec clé `BIGINT UNSIGNED), numéro séquentiel, état et phase propres au lot.
- Crée le lot n° 1 de chaque marché existant en copiant son état normalisé actuel.
- Ajoute `lot_id` et `company_id` aux qualifications existantes et les rattache au lot n° 1.
- Ajoute `lot_id` et `company_id` aux transitions existantes et les rattache au lot n° 1 ; les transitions de qualification sont rattachées au même lot que leur qualification.
- Ajoute le résultat officiel au niveau du lot tout en conservant le pointeur historique au niveau de `markets`.
- N'invente aucun résultat et ne supprime aucune donnée historique.

**Condition importante :** ne pas exécuter 005 seule avec le code actuel. Le service, les contrôleurs, les vues et les listes doivent être adaptés au lot avant d'utiliser les nouvelles colonnes. Le script exige 004 déjà installée.

### Migration 006 — `006_market_core_details.sql`

**Statut : BROUILLON — NON EXÉCUTÉ**

- Ajoute le numéro interne et la date de modification du marché.
- Crée les paramètres par société et leur historique.
- Initialise les paramètres de retenue à 10 % et 7 % pour chaque société disposant du compte GEST attendu.
- Crée le compteur atomique de numérotation par société/année.
- Crée les organismes, les détails généraux, les informations d'étude, le dépôt et les détails d'attribution par lot.
- Les champs non disponibles au début du dossier restent NULL jusqu'à leur saisie.
- Les données d'attribution conservent un instantané du taux, du plafond et de la base TTC.

**Dépendance :** 006 exige 005 et suppose que `market_lots` existe.

## 5. Relations et contraintes MariaDB 10.4.32

Contrôles de conception effectués :

- Les colonnes FK utilisent les mêmes types et attributs signés/non signés que leurs clés cibles.
- Les identifiants des sociétés et marchés restent en `INT UNSIGNED) ; les identifiants des lots, organismes et historiques sont en `BIGINT UNSIGNED).
- Les références composites sont appuyées par des index uniques dans le même ordre.
- Les montants et taux utilisent `DECIMAL), jamais `FLOAT`.
- Les contraintes `CHECK` portent sur les règles de ligne ; les plafonds cumulés, états en attente et non-chevauchements sont contrôlés transactionnellement par l'application.
- Les suppressions d'historique, de lots ayant des opérations ou de documents référencés sont empêchées par `RESTRICT`.
- Les clés composites marché/société et lot/marché/société garantissent que les objets enfants restent rattachés au bon périmètre.

Points techniques à vérifier sur une copie MariaDB avant installation :

1. Résultat exact de `SHOW CREATE TABLE` pour toutes les tables 001 à 004.
2. Présence/absence réelle de chaque index, colonne et contrainte attendus avant DDL.
3. Compatibilité effective de chaque FK composite après création de son index candidat.
4. Nombre de marchés, qualifications et transitions avant/après migration 005.
5. Nombre de sociétés et présence du compte `gest` avant l'initialisation des paramètres 006.
6. Reprise contrôlée si un DDL échoue après un commit implicite.

## 6. Écarts encore à traiter dans les prochaines migrations

| Domaine | Travail SQL restant |
|---|---|
| Documents | `stored_documents`, modèles documentaires, checklist, historique des coches |
| Cautions | cautions successives, numéros de séquence, lien de remplacement et pièces |
| Retenue | événements append-only par situation validée, calcul TTC et plafond cumulé |
| BPU | versions, lignes, imports, calcul HT/TVA/TTC et verrouillage par lot |
| Contrat | périodes, renouvellements, alertes et règles de non-chevauchement |
| Exécution | événements chantier, situations de travaux, avenants, réceptions et clôture par lot |

Ces scripts ne sont pas déclarés prêts tant que leurs clés composites et dépendances documentaires n'ont pas été relues et testées.

## 7. Ordre de déploiement proposé

1. Vérifier la base réelle et l'état des migrations 001 à 004.
2. Sauvegarder puis restaurer la sauvegarde dans une base de test.
3. Adapter l'application au cycle par lot tout en conservant la compatibilité des marchés existants.
4. Tester migration 005 sur la copie.
5. Tester migration 006 sur la copie.
6. Préparer et auditer les migrations 007 et suivantes.
7. Réaliser les tests fonctionnels, d'isolation multi-sociétés et de non-régression.
8. Demander une autorisation explicite avant toute installation sur la base de travail ou de production.

## 8. Limites et sécurité

Cet audit est statique. Il ne confirme ni la version réelle de la base locale, ni l'installation des migrations, ni l'intégrité des données XAMPP. Aucune migration n'a été exécutée. Aucun fichier de configuration local n'a été modifié.

La présence d'un fichier SQL dans GitHub n'est jamais une autorisation d'exécution.
