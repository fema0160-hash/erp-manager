Référence : erp-manager/docs/AUDIT_COHERENCE_MCD_MLD_V1.md

# Audit de cohérence MCD / MLD — fiche marché

- Dépôt : `fema0160-hash/erp-manager`
- Branche auditée : `main`
- Cible : MariaDB 10.4.32 / InnoDB / utf8mb4
- Méthode : lecture statique des migrations 001 à 004, du service `MarketLifecycle`, du contrôleur `public/index.php`, du MCD V1 et du MLD V1.
- Limite : aucun accès à la base XAMPP ou à une base distante n'a été effectué ; aucune migration n'a été exécutée.

## 1. Conclusion générale

**Audit documentaire et statique : non validé pour génération/exécution immédiate des migrations.**

Le socle SQL 001 à 003 est cohérent avec les types fondamentaux utilisés dans les propositions : `companies.id`, `markets.id`, `markets.company_id` et `users.id` sont en `INT UNSIGNED` ; les identifiants des historiques sont en `BIGINT UNSIGNED`. Les clés étrangères simples de la migration 004 utilisent des types compatibles.

Le MLD V1 doit cependant être corrigé sur les lots, les cautions successives, la retenue de garantie et certaines relations composites avant de produire des scripts SQL définitifs.

## 2. Décisions métier reçues

Les règles suivantes remplacent les points « à arbitrer » correspondants du MCD/MLD V1 :

1. **Lots** : un seul lot est créé automatiquement par défaut. Le modèle doit permettre d'ajouter plusieurs lots indépendants ensuite. Chaque lot doit disposer de son BPU, de son résultat et de son avancement de cycle indépendants.
2. **BPU / TVA** : chaque ligne est calculée en HT, TVA et TTC. Le taux de TVA est enregistré par ligne ; les montants sont calculés côté serveur et les totaux de version sont la somme des lignes.
3. **Retenue de garantie** : taux de prélèvement initial de 10 %, plafond de 7 %, tous deux modifiables dans les paramètres. Les valeurs appliquées à un marché doivent être figées pour préserver l'historique.
4. **Cautions** : plusieurs cautions successives du même type doivent être conservées comme des enregistrements distincts ; aucune caution remplacée ne doit être écrasée.
5. **Paramètres** : paramètres propres à chaque société dans la première version. Les paramètres communs au groupe sont reportés à une étape ultérieure.

## 3. Contrôle des migrations existantes

### Migration 001 — sociétés et marchés

- `companies.id`, `markets.id` et `markets.company_id` sont en `INT UNSIGNED`.
- `markets.id` est la clé primaire ; `markets.company_id` référence `companies.id`.
- `markets.reference` est unique globalement dans le schéma initial. Le futur numéro interne par société doit rester distinct et ne doit pas remplacer cette référence sans migration de compatibilité.
- Le schéma initial ne contient pas de clé unique `(markets.id, markets.company_id)`. Elle est nécessaire avant les clés étrangères composites proposées.

### Migration 002 — utilisateurs et permissions

- `users.id` et `permissions.id` sont en `INT UNSIGNED`.
- Les restrictions utilisateur sont facultatives ; les permissions fonctionnelles sont accordées par défaut par le service d'authentification.
- Les futures références d'audit à un utilisateur doivent utiliser `ON DELETE RESTRICT` afin de conserver les historiques.

### Migration 003 — cycle de vie

- Ajoute les colonnes `lifecycle_status` et `lifecycle_phase` à `markets`.
- Crée `market_transitions` avec `id BIGINT UNSIGNED`, `market_id INT UNSIGNED` et `changed_by INT UNSIGNED`.
- Les données historiques restent dans les anciennes colonnes ; la migration copie l'état normalisé dans l'historique.
- Les phases 1 à 5 utilisent actuellement les colonnes de cycle au niveau du marché.

### Migration 004 — qualification et validation

Fichier présent : `database/migrations/004_result_qualification_validation.sql`.

- Le script crée `market_result_qualifications` et `market_result_validations`.
- Il ajoute `qualification_id` à `market_transitions` et `official_result_qualification_id` à `markets`.
- Les index composites prévus pour les références `(qualification_id, market_id)` et `(id, market_id)` sont cohérents entre eux dans la version actuelle.
- Le contrôleur vérifie la présence des deux tables, des deux colonnes, des deux contraintes et des quatre permissions avant d'autoriser les opérations qui en dépendent.
- La présence du fichier dans Git ne prouve pas son installation dans la base locale ou en production.

**Point bloquant :** la migration 004 et le service `MarketLifecycle` actuels gèrent un résultat au niveau du marché entier. Ils ne gèrent pas encore un résultat, un BPU et un cycle indépendants par lot. La migration 004 doit donc être révisée avant installation pour intégrer le lot par défaut et les références de qualification par lot ; le service et les contrôles de disponibilité du contrôleur devront être adaptés en conséquence.

## 4. Écarts de cohérence identifiés dans le MLD V1

| Réf. | Écart | Correction nécessaire |
|---|---|---|
| C01 | Aucun objet `market_lots` ; `lot_reference` et `lot_label` sont placés dans `market_details` | Créer `market_lots`, créer automatiquement le lot n° 1 et déplacer les données de lot dans cette entité |
| C02 | Résultat et phase stockés au niveau du marché | Porter l'état et la phase courants sur chaque lot ; rattacher qualifications et transitions au lot |
| C03 | BPU non rattaché à un lot | Ajouter `lot_id` aux versions BPU, lignes/imports selon le modèle retenu et garantir le même marché/société |
| C04 | `UNIQUE(market_id, guarantee_type)` limite à une caution de chaque type | Remplacer par un numéro de séquence et conserver chaque caution successivement ; prévoir un lien facultatif vers la caution remplacée |
| C05 | Le calcul de retenue n'exprime pas séparément prélèvement et plafond | Paramétrer le taux de prélèvement (10 %) et le plafond (7 %) ; figer les deux valeurs et la base monétaire dans le dossier |
| C06 | Les paramètres sont par société, mais la proposition n'explicite pas le report des paramètres de groupe | Conserver le périmètre société en V1 ; traiter le niveau groupe dans une version ultérieure |
| C07 | La règle de cohérence société de `market_alerts.rule_id` n'est pas garantie par une FK composite | Ajouter une clé unique `(id, company_id)` sur les règles et une FK composite depuis les alertes |
| C08 | Plusieurs tables référencent des documents ou des enfants sans préciser partout la clé composite complète | Vérifier chaque FK sur `(id, market_id, company_id)` et l'ordre de création des tables dans les migrations |
| C09 | Les champs de service order et notification sont déclarés obligatoires dès la création du détail d'attribution | Autoriser leur valeur NULL jusqu'à réception effective des informations, puis valider côté application au passage concerné |
| C10 | Le MCD et le MLD ne décrivent pas encore un cycle indépendant par lot | Ajouter les associations LOT–BPU–QUALIFICATION–VALIDATION–TRANSITION–ATTRIBUTION–EXÉCUTION |

## 5. Contrôle de compatibilité MariaDB 10.4.32

Les constructions utilisées dans les propositions sont compatibles en principe avec MariaDB 10.4.32 sous InnoDB, sous réserve de leur définition exacte :

- types entiers signés/non signés strictement identiques entre colonnes FK et colonnes référencées ;
- clés étrangères composites appuyées par un index dans le même ordre ;
- clé unique explicite sur chaque tuple référencé, par exemple `(id, company_id)` ou `(id, market_id, company_id)` ;
- `DECIMAL` pour les montants et taux, jamais `FLOAT` ;
- contraintes `CHECK` pour les validations simples de ligne ;
- contraintes métier inter-tables (plafond cumulé, unicité d'un lot par marché, une seule version BPU finale, absence de chevauchement de périodes) contrôlées transactionnellement par le service applicatif, car elles ne se résument pas à un `CHECK` de ligne.

Les scripts doivent tenir compte des commits implicites des DDL MariaDB : une migration partiellement exécutée ne doit jamais être relancée aveuglément. Chaque migration devra comporter ses prérequis, contrôles avant/après et procédure d'arrêt en cas d'échec.

## 6. Migration 004 et disponibilité fonctionnelle

État du code versionné :

- Le service documente explicitement que les qualifications, validations et transitions après la phase 5 nécessitent la migration 004.
- Le contrôleur permet la progression séquentielle 1→2→3→4→5 sans exiger la migration 004.
- Si le schéma 004 n'est pas détecté, le contrôleur bloque les qualifications, validations et opérations de phase 6 à 8 avec un message explicite.
- Après ajout de la gestion indépendante des lots, ces vérifications devront aussi contrôler la table des lots et les nouvelles colonnes/contraintes de qualification par lot.

**Règle de déploiement :** sauvegarde complète vérifiée, essai sur copie, inspection du schéma réel, puis autorisation explicite avant toute exécution. Ce rapport ne constitue pas une autorisation.

## 7. Ordre de travail avant mise en service

1. Corriger et consolider le MCD/MLD selon les décisions de la section 2.
2. Réviser la migration 004 pour intégrer le lot par défaut et les résultats indépendants par lot.
3. Adapter `MarketLifecycle` et `public/index.php` au cycle par lot tout en conservant les marchés historiques.
4. Produire les migrations additives suivantes, dans un ordre FK-compatible, pour les organismes, détails, études, documents, checklist, cautions, BPU, périodes, alertes, exécution et clôture.
5. Auditer chaque script SQL et ses contrôles de compatibilité MariaDB 10.4.32.
6. Tester toutes les migrations sur une copie de sauvegarde avant toute demande d'installation.

## 8. Limites de cet audit

Cet audit est statique et porte sur les fichiers versionnés dans GitHub `main`. Il ne confirme ni la version réelle de la base connectée, ni l'état d'installation des migrations, ni les données existantes de XAMPP. Il ne modifie aucune base de données et n'exécute aucun SQL.
