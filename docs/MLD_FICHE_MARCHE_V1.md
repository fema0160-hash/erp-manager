Référence : erp-manager/docs/MLD_FICHE_MARCHE_V1.md

# MLD — Modèle logique de données de la fiche marché

- Projet : ERP Manager
- Version : MLD Fiche Marché V1.1 — décisions métier intégrées
- Branche de référence : `main`
- SGBD cible : MariaDB 10.4.32
- Moteur : InnoDB
- Encodage : utf8mb4 / utf8mb4_unicode_ci
- Devise : MAD
- Statut : proposition logique détaillée — aucun SQL exécuté
- MCD associé : `docs/MCD_FICHE_MARCHE_V1.md`

## 1. Conventions

- Toutes les nouvelles tables utilisent InnoDB et utf8mb4.
- Les montants sont stockés en `DECIMAL`, jamais en FLOAT.
- Les dates métier sont `DATE` ; les instants d'opération sont `DATETIME`.
- Les clés primaires auto-incrémentées utilisent `INT UNSIGNED` ou `BIGINT UNSIGNED`.
- Les colonnes `created_at` et `updated_at` sont ajoutées aux tables métier modifiables.
- Les tables métier liées à un marché portent `market_id` et `company_id`.
- Les clés étrangères composites `(market_id, company_id)` pointent vers `markets(id, company_id)`. Il faut donc ajouter une clé UNIQUE correspondante à `markets`.
- Les objets propres à un lot portent `lot_id`, `market_id` et `company_id`, avec une FK composite vers `market_lots(id, market_id, company_id)`.
- Les références aux utilisateurs sont conservées même si le compte est ensuite désactivé ; aucune suppression en cascade d'historique utilisateur.
- Les colonnes de compatibilité historiques `markets.reference`, `status`, `phase`, `amount`, `market_date`, `lifecycle_status` et `lifecycle_phase` sont conservées.
- Les noms ci-dessous sont des noms logiques proposés. Ils ne doivent pas être créés avant validation du modèle et comparaison finale avec le schéma réel installé.

## 2. Tables existantes — conservation et évolutions

### 2.1 Tables existantes réutilisées sans remplacement

| Table | Clé primaire | Rôle |
|---|---|---|
| `companies` | `id INT UNSIGNED` | Sociétés |
| `users` | `id INT UNSIGNED` | Comptes utilisateurs |
| `permissions` | `id INT UNSIGNED` | Permissions |
| `user_sessions` | `id BIGINT UNSIGNED` | Sessions |
| `user_company_restrictions` | `id BIGINT UNSIGNED` | Restrictions entreprise facultatives |
| `user_permission_restrictions` | `id BIGINT UNSIGNED` | Restrictions fonctionnelles facultatives |
| `markets` | `id INT UNSIGNED` | Dossier marché |
| `market_transitions` | `id BIGINT UNSIGNED` | Historique du cycle |
| `market_result_qualifications` | `id BIGINT UNSIGNED` | Résultats proposés, rattachés à un lot par migration additive 005 |
| `market_result_validations` | `id BIGINT UNSIGNED` | Validation/refus des résultats |

Les tables `market_result_qualifications` et `market_result_validations` de la migration 004 restent l'unique mécanisme de qualification et de validation. Ne pas créer de tables de résultat concurrentes.

### 2.2 Évolutions additives de `markets`

Colonnes supplémentaires proposées :

| Colonne | Type | Règle |
|---|---|---|
| `internal_number` | `VARCHAR(40) NULL` au déploiement | Numéro interne automatique ; rempli progressivement pour les anciens dossiers |
| `updated_at` | `DATETIME NULL` | Date de dernière modification, avec stratégie de remplissage des anciens dossiers |
| `official_result_qualification_id` | `BIGINT UNSIGNED NULL` | Pointeur historique de compatibilité ; les résultats nouveaux sont portés par lot |

Index et contraintes :
- `UNIQUE(company_id, internal_number)` ; plusieurs NULL sont tolérés pendant la migration des anciens dossiers.
- `UNIQUE(id, company_id)` pour les clés étrangères composites des nouvelles tables.
- Après attribution d'un numéro à tous les dossiers existants, évaluer si `internal_number` peut devenir NOT NULL dans une migration distincte.
- Ne pas changer la signification ni supprimer `reference` : elle reste conservée pour compatibilité avec l'application existante.
- Le nouveau numéro interne est distinct du numéro d'appel d'offres et du numéro officiel du marché attribué.

## 3. Référentiel des organismes

### 3.1 `market_organizations`

Référentiel des maîtres d'ouvrage, maîtres d'œuvre et autres organismes, séparé par société.

| Colonne | Type | Null | Description |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED PK AI | Non | Identifiant |
| `company_id` | INT UNSIGNED FK | Non | Société propriétaire |
| `organization_type` | VARCHAR(24) | Non | `maitre_ouvrage`, `maitre_oeuvre`, `autre` |
| `name` | VARCHAR(180) | Non | Dénomination |
| `address` | VARCHAR(255) | Oui | Adresse |
| `city` | VARCHAR(100) | Oui | Ville |
| `phone` | VARCHAR(40) | Oui | Téléphone |
| `email` | VARCHAR(190) | Oui | E-mail |
| `contact_name` | VARCHAR(160) | Oui | Contact |
| `notes` | TEXT | Oui | Notes |
| `is_active` | TINYINT(1) | Non | Actif, défaut 1 |
| `created_at` | DATETIME | Non | Création |
| `updated_at` | DATETIME | Non | Modification |

Index :
- PK `id`.
- UNIQUE `(id, company_id)`.
- KEY `(company_id, organization_type, is_active)`.
- KEY `(company_id, name)`.
- FK `company_id → companies.id`, ON DELETE RESTRICT.

### 3.2 `market_details`

Complément 1:1 des informations générales du marché.

| Colonne | Type | Null | Description |
|---|---|---:|---|
| `market_id` | INT UNSIGNED PK/FK | Non | Marché |
| `company_id` | INT UNSIGNED FK | Non | Société, redondance contrôlée |
| `tender_number` | VARCHAR(100) | Non | N° appel d'offres |
| `client_organization_id` | BIGINT UNSIGNED | Non | Maître d'ouvrage |
| `project_manager_organization_id` | BIGINT UNSIGNED | Non | Maître d'œuvre |
| `duration_months` | SMALLINT UNSIGNED | Non | Durée initiale en mois |
| `is_renewable` | TINYINT(1) | Non | Oui/non |
| `renewal_duration_years` | SMALLINT UNSIGNED | Oui | Obligatoire si renouvelable |
| `market_nature` | VARCHAR(20) | Oui | Travaux, fournitures, services |
| `execution_address` | VARCHAR(255) | Oui | Lieu d'exécution |
| `created_at` | DATETIME | Non | Création |
| `updated_at` | DATETIME | Non | Modification |

Index et contraintes :
- PK `market_id`.
- UNIQUE `(market_id, company_id)`.
- FK composite `(market_id, company_id) → markets(id, company_id)`.
- FK composite `(client_organization_id, company_id) → market_organizations(id, company_id)`.
- FK composite `(project_manager_organization_id, company_id) → market_organizations(id, company_id)`.
- CHECK : durée initiale supérieure à zéro ; `is_renewable` dans 0/1 ; si non renouvelable alors durée de renouvellement NULL ; si renouvelable alors durée de renouvellement positive.
- UNIQUE éventuelle `(company_id, tender_number, lot_reference)` à valider selon la règle réelle des appels d'offres et lots. Ne pas imposer avant de confirmer les doublons légitimes.

### 3.3 `market_lots`

Un marché possède au moins un lot. Le lot n° 1 est créé automatiquement lors de la création du marché ; l'utilisateur peut ensuite ajouter des lots indépendants.

| Colonne | Type | Null | Description |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED PK AI | Non | Identifiant du lot |
| `market_id` | INT UNSIGNED | Non | Marché parent |
| `company_id` | INT UNSIGNED | Non | Société propriétaire |
| `lot_number` | SMALLINT UNSIGNED | Non | Numéro séquentiel du lot |
| `lot_reference` | VARCHAR(100) | Oui | Référence officielle |
| `lot_label` | VARCHAR(180) | Non | Désignation |
| `lifecycle_status` | VARCHAR(20) | Non | Statut indépendant du lot |
| `lifecycle_phase` | TINYINT UNSIGNED | Non | Phase indépendante 1 à 8 |
| `awarded_amount` | DECIMAL(15,2) | Oui | Montant TTC attribué au lot, alimenté après validation |
| `created_at` | DATETIME | Non | Création |
| `updated_at` | DATETIME | Non | Modification |

Contraintes : UNIQUE `(market_id, lot_number)`, UNIQUE `(id, market_id, company_id)`, FK `(market_id, company_id)` vers `markets(id, company_id)`, CHECK `lot_number > 0`, CHECK phase 1 à 8 et statuts autorisés. Le lot par défaut est créé dans la même transaction que le marché. Les phases historiques du marché sont copiées vers le lot n° 1 lors de la migration 005.

## 4. Étude, dépôt et attribution

### 4.1 `market_studies`

Données d'étude 1:1.

| Colonne | Type | Null | Description |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED PK AI | Non | Identifiant |
| `market_id` | INT UNSIGNED | Non | Marché |
| `company_id` | INT UNSIGNED | Non | Société |
| `launch_date` | DATE | Non | Date de lancement |
| `offer_deadline_at` | DATETIME | Non | Date/heure limite de remise |
| `opening_at` | DATETIME | Non | Date/heure ouverture des plis |
| `study_validated_at` | DATETIME | Oui | Validation de prise en charge |
| `study_started_at` | DATETIME | Oui | Début étude |
| `study_completed_at` | DATETIME | Oui | Étude terminée le |
| `study_cost` | DECIMAL(15,2) | Non | Charges d'étude DH, défaut 0 |
| `estimated_cost` | DECIMAL(15,2) | Non | Coût estimatif DH |
| `study_manager_user_id` | INT UNSIGNED FK users | Oui | Chargé d'étude |
| `observations` | TEXT | Oui | Observations |
| `created_at` | DATETIME | Non | Création |
| `updated_at` | DATETIME | Non | Modification |

Index :
- UNIQUE `(market_id)`.
- UNIQUE `(id, market_id, company_id)`.
- FK composite `(market_id, company_id) → markets(id, company_id)`.
- FK `study_manager_user_id → users.id`, ON DELETE RESTRICT.
- CHECK montants >= 0.

### 4.2 `market_submissions`

Dépôt de l'offre, 0:1 par marché en V1.

| Colonne | Type | Null | Description |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED PK AI | Non | Identifiant |
| `market_id` | INT UNSIGNED | Non | Marché |
| `company_id` | INT UNSIGNED | Non | Société |
| `deposited_by` | INT UNSIGNED FK users | Non | Chargé de dépôt |
| `deposited_at` | DATETIME | Non | Date/heure réelle du dépôt |
| `observations` | TEXT | Oui | Observations |
| `proof_document_id` | BIGINT UNSIGNED | Oui | Preuve de dépôt |
| `created_at` | DATETIME | Non | Création |
| `updated_at` | DATETIME | Non | Modification |

Index et contraintes :
- UNIQUE `(market_id)`.
- UNIQUE `(id, market_id, company_id)`.
- FK composite vers `markets(id, company_id)`.
- FK `deposited_by → users.id`.
- FK composite `(proof_document_id, market_id, company_id)` vers `stored_documents(id, market_id, company_id)`, à créer après `stored_documents`.

### 4.3 `market_award_details`

Informations propres à l'attribution et au contrat, 0:1 par lot. Ajouter `lot_id BIGINT UNSIGNED NOT NULL` et remplacer l'unicité `(market_id)` par UNIQUE `(lot_id)`. La table doit porter aussi UNIQUE `(id, lot_id, market_id, company_id)` et une FK composite vers `market_lots`. Le numéro officiel, la date de notification et l'ordre de service sont NULL tant qu'ils ne sont pas connus.

| Colonne | Type | Null | Description |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED PK AI | Non | Identifiant |
| `market_id` | INT UNSIGNED | Non | Marché |
| `company_id` | INT UNSIGNED | Non | Société |
| `official_market_number` | VARCHAR(100) | Oui | N° officiel du marché attribué |
| `contract_signed_at` | DATE | Oui | Date de signature |
| `service_order_number` | VARCHAR(100) | Oui | N° ordre de service |
| `service_order_date` | DATE | Oui | Date ordre de service |
| `notification_date` | DATE | Oui | Date de notification |
| `retention_rate_percent` | DECIMAL(5,2) | Non | Instantané du taux de prélèvement (défaut 10 %) |
| `retention_cap_percent` | DECIMAL(5,2) | Non | Instantané du plafond (défaut 7 %) |
| `retention_base_amount_ttc` | DECIMAL(15,2) | Non | Montant TTC initial attribué au lot |
| `retention_cap_amount_ttc` | DECIMAL(15,2) | Non | Plafond monétaire calculé à 7 % du TTC attribué |
| `retention_base_code` | VARCHAR(30) | Non | `awarded_amount_ttc` |
| `created_at` | DATETIME | Non | Création |
| `updated_at` | DATETIME | Non | Modification |

Index et contraintes :
- UNIQUE `(market_id)`.
- UNIQUE `(id, market_id, company_id)`.
- FK composite vers `markets(id, company_id)`.
- CHECK taux entre 0 et 100 ; base et montant >= 0.
- Le montant attribué reste la donnée officielle issue de la qualification validée et du champ existant `markets.awarded_amount`. Toute copie éventuelle dans cette table doit être supprimée de la conception finale pour éviter deux valeurs concurrentes.

## 5. Cautions et garanties

### 5.1 `market_guarantees`

Plusieurs cautions successives de chaque type peuvent être conservées pour un même lot. Ajouter `lot_id BIGINT UNSIGNED NOT NULL`, `sequence_number SMALLINT UNSIGNED NOT NULL` et `replaces_guarantee_id BIGINT UNSIGNED NULL`. L'unicité devient `(lot_id, guarantee_type, sequence_number)` ; aucune contrainte UNIQUE ne doit limiter à une seule caution par type. Chaque caution remplacée reste consultable.

| Colonne | Type | Null | Description |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED PK AI | Non | Identifiant |
| `market_id` | INT UNSIGNED | Non | Marché |
| `company_id` | INT UNSIGNED | Non | Société |
| `guarantee_type` | VARCHAR(24) | Non | `provisoire` ou `definitive` |
| `required_amount` | DECIMAL(15,2) | Non | Montant requis |
| `obtained_amount` | DECIMAL(15,2) | Oui | Montant réellement obtenu |
| `preparation_status` | VARCHAR(20) | Non | `a_preparer`, `preparee`, `obtenue`, `liberee`, `restituee` |
| `reference_number` | VARCHAR(100) | Oui | Référence bancaire |
| `prepared_at` | DATETIME | Oui | Préparation |
| `obtained_at` | DATETIME | Oui | Obtention |
| `expires_at` | DATE | Oui | Expiration |
| `released_at` | DATETIME | Oui | Libération/restitution |
| `document_id` | BIGINT UNSIGNED | Oui | Justificatif |
| `observations` | TEXT | Oui | Notes |
| `created_at` | DATETIME | Non | Création |
| `updated_at` | DATETIME | Non | Modification |

Index et contraintes :
- UNIQUE `(lot_id, guarantee_type, sequence_number)`.
- UNIQUE `(id, market_id, company_id)` et clé composite pour l'auto-référence de remplacement.
- FK composite vers `market_lots(id, market_id, company_id)`.
- FK composite du document vers `stored_documents`.
- CHECK montants >= 0.
- Le taux de retenue de garantie n'est pas stocké dans cette table : il appartient à `market_award_details` et aux paramètres.

Une table d'événements de caution est à prévoir si le besoin de tracer séparément chaque changement d'état est confirmé ; à défaut, l'audit applicatif doit historiser chaque changement.

## 6. Paramètres, modèles documentaires et numérotation

### 6.1 `market_settings`

| Colonne | Type | Null | Description |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED PK AI | Non | Identifiant |
| `company_id` | INT UNSIGNED FK companies | Non | Société |
| `setting_key` | VARCHAR(100) | Non | Clé unique par société |
| `setting_value` | TEXT | Oui | Valeur stockée |
| `value_type` | VARCHAR(20) | Non | `string`, `integer`, `decimal`, `boolean` |
| `updated_by` | INT UNSIGNED FK users | Non | Auteur |
| `updated_at` | DATETIME | Non | Modification |

Index : UNIQUE `(company_id, setting_key)`, FK company et user.

Clés initiales proposées :
- `retention_rate_percent` : taux de prélèvement, valeur initiale 10.00 ;
- `retention_cap_percent` : plafond cumulé, valeur initiale 7.00 ;
- `retention_base_code` : `situation_ttc` pour le prélèvement et `awarded_amount_ttc` pour le plafond ;
- `renewal_alert_days` : délais d'alerte par défaut, si la table de règles n'est pas utilisée ;
- `market_number_prefix` : préfixe de numéro interne ;
- `market_number_padding` : longueur du compteur.

Les paramètres fortement structurés (modèles, lignes documentaires et règles d'alerte) restent dans leurs propres tables et ne sont pas encodés dans une valeur JSON.

### 6.2 `market_setting_history`

| Colonne | Type | Null | Description |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED PK AI | Non | Identifiant |
| `company_id` | INT UNSIGNED FK | Non | Société |
| `setting_key` | VARCHAR(100) | Non | Clé |
| `old_value` | TEXT | Oui | Ancienne valeur |
| `new_value` | TEXT | Oui | Nouvelle valeur |
| `changed_by` | INT UNSIGNED FK users | Non | Auteur |
| `change_reason` | VARCHAR(255) | Oui | Motif |
| `changed_at` | DATETIME | Non | Date |

Index `(company_id, setting_key, changed_at)`.

### 6.3 `market_number_sequences`

| Colonne | Type | Null | Description |
|---|---|---:|---|
| `id` | INT UNSIGNED PK AI | Non | Identifiant |
| `company_id` | INT UNSIGNED FK | Non | Société |
| `sequence_year` | SMALLINT UNSIGNED | Non | Année |
| `prefix` | VARCHAR(20) | Non | Préfixe |
| `next_number` | BIGINT UNSIGNED | Non | Prochain numéro |
| `padding_length` | TINYINT UNSIGNED | Non | Zéros de remplissage |
| `updated_at` | DATETIME | Non | Modification |

UNIQUE `(company_id, sequence_year)`. Le compteur est incrémenté sous transaction et verrouillage `SELECT ... FOR UPDATE`.

### 6.4 `market_document_templates`

| Colonne | Type | Null | Description |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED PK AI | Non | Identifiant |
| `company_id` | INT UNSIGNED FK | Non | Société |
| `template_name` | VARCHAR(160) | Non | Nom |
| `template_version` | INT UNSIGNED | Non | Version |
| `is_default` | TINYINT(1) | Non | Modèle par défaut |
| `is_active` | TINYINT(1) | Non | Actif |
| `created_by` | INT UNSIGNED FK users | Non | Auteur |
| `created_at` | DATETIME | Non | Création |
| `updated_at` | DATETIME | Non | Modification |

Index UNIQUE `(id, company_id)`, KEY `(company_id, is_active, is_default)`. La règle « un seul modèle par défaut actif par société » est contrôlée transactionnellement par l'application.

### 6.5 `market_document_template_items`

| Colonne | Type | Null | Description |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED PK AI | Non | Identifiant |
| `template_id` | BIGINT UNSIGNED | Non | Modèle |
| `company_id` | INT UNSIGNED | Non | Société |
| `designation` | VARCHAR(180) | Non | Libellé affiché |
| `sort_order` | SMALLINT UNSIGNED | Non | Ordre |
| `is_required` | TINYINT(1) | Non | Obligatoire |
| `is_active` | TINYINT(1) | Non | Actif |
| `created_at` | DATETIME | Non | Création |
| `updated_at` | DATETIME | Non | Modification |

Index UNIQUE `(template_id, sort_order)`, UNIQUE `(id, template_id, company_id)`. FK composite `(template_id, company_id)` vers `market_document_templates(id, company_id)`.

## 7. Fichiers et tableau de contrôle des documents

### 7.1 `stored_documents`

Métadonnées privées des fichiers liés à un marché.

| Colonne | Type | Null | Description |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED PK AI | Non | Identifiant |
| `market_id` | INT UNSIGNED | Non | Marché propriétaire |
| `company_id` | INT UNSIGNED | Non | Société |
| `storage_key` | VARCHAR(255) | Non | Clé/chemin privé généré |
| `original_name` | VARCHAR(255) | Non | Nom d'origine |
| `mime_type` | VARCHAR(120) | Non | Type MIME détecté |
| `extension` | VARCHAR(12) | Non | Extension normalisée |
| `size_bytes` | BIGINT UNSIGNED | Non | Taille |
| `sha256` | CHAR(64) | Non | Empreinte |
| `uploaded_by` | INT UNSIGNED FK users | Non | Auteur |
| `uploaded_at` | DATETIME | Non | Date |
| `is_deleted` | TINYINT(1) | Non | Corbeille logique |
| `deleted_at` | DATETIME | Oui | Date de mise en corbeille |

Index UNIQUE `(id, market_id, company_id)`, UNIQUE `storage_key`, KEY `(market_id, uploaded_at)`, FK composite vers markets. Le fichier physique est hors du webroot. Contrôle de type réel, taille, nom de stockage et autorisation de téléchargement obligatoires.

### 7.2 `market_document_checklist_items`

Copie figée des lignes du modèle dans le dossier. Exactement trois colonnes sont présentées dans l'interface : désignation, case, date. Aucun numéro attribué.

| Colonne | Type | Null | Description |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED PK AI | Non | Identifiant |
| `market_id` | INT UNSIGNED | Non | Marché |
| `company_id` | INT UNSIGNED | Non | Société |
| `source_template_id` | BIGINT UNSIGNED | Oui | Modèle d'origine, référence historique non bloquante |
| `source_template_item_id` | BIGINT UNSIGNED | Oui | Ligne d'origine, conservée comme valeur historique |
| `designation_snapshot` | VARCHAR(180) | Non | Copie du libellé |
| `sort_order_snapshot` | SMALLINT UNSIGNED | Non | Ordre figé |
| `is_required_snapshot` | TINYINT(1) | Non | Obligatoire au moment de la copie |
| `is_checked` | TINYINT(1) | Non | Case cochée |
| `checked_at` | DATETIME | Oui | Date/heure du dernier clic de coche |
| `checked_by` | INT UNSIGNED FK users | Oui | Dernier utilisateur ayant coché |
| `document_id` | BIGINT UNSIGNED | Oui | Justificatif facultatif |
| `created_at` | DATETIME | Non | Création |

Index UNIQUE `(market_id, sort_order_snapshot)`, UNIQUE `(id, market_id, company_id)`, FK composite vers markets et stored_documents. La désignation est une copie : aucune FK active vers la ligne du modèle ne doit permettre de modifier rétroactivement le dossier.

La date est remplie automatiquement par le serveur au clic, dans le fuseau `Africa/Casablanca`. En cas de décochage, `is_checked=0` et `checked_at=NULL` pour l'état courant ; l'événement précédent reste dans la table d'historique.

### 7.3 `market_document_checklist_events`

| Colonne | Type | Null | Description |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED PK AI | Non | Identifiant |
| `checklist_item_id` | BIGINT UNSIGNED | Non | Ligne de checklist |
| `market_id` | INT UNSIGNED | Non | Marché |
| `company_id` | INT UNSIGNED | Non | Société |
| `event_code` | VARCHAR(12) | Non | `checked` ou `unchecked` |
| `event_at` | DATETIME | Non | Instant serveur |
| `user_id` | INT UNSIGNED FK users | Non | Auteur |
| `reason` | VARCHAR(255) | Oui | Motif de correction |

FK composite `(checklist_item_id, market_id, company_id)` vers checklist items. Index `(checklist_item_id, event_at)` et `(market_id, event_at)`. Les événements sont append-only.

Le tableau reste modifiable pendant les phases 3 et 4. À la confirmation du dépôt et au passage en phase 5, les modifications sont refusées côté serveur. Une correction exceptionnelle ultérieure exige un motif et un événement supplémentaire ; aucune ancienne ligne d'historique n'est modifiée.

## 8. BPU — lignes et importations

### 8.1 `market_bpu_versions`

Chaque version BPU appartient à un lot. Ajouter `lot_id BIGINT UNSIGNED NOT NULL` et une FK composite vers `market_lots`; remplacer les unicités de version et de version finale par une portée par lot.

| Colonne | Type | Null | Description |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED PK AI | Non | Identifiant |
| `market_id` | INT UNSIGNED | Non | Marché |
| `company_id` | INT UNSIGNED | Non | Société |
| `version_number` | SMALLINT UNSIGNED | Non | Numéro de version |
| `version_status` | VARCHAR(16) | Non | `brouillon`, `importee`, `finale`, `archivee` |
| `source_document_id` | BIGINT UNSIGNED | Oui | Fichier Excel/PDF source |
| `total_ht` | DECIMAL(15,2) | Non | Total HT |
| `total_vat` | DECIMAL(15,2) | Non | Total TVA |
| `total_ttc` | DECIMAL(15,2) | Non | Total TTC |
| `version_note` | VARCHAR(255) | Oui | Motif |
| `created_by` | INT UNSIGNED FK users | Non | Auteur |
| `created_at` | DATETIME | Non | Création |
| `finalized_at` | DATETIME | Oui | Finalisation |

Index UNIQUE `(market_id, version_number)`, UNIQUE `(id, market_id, company_id)`, FK composite vers markets et stored_documents. Le passage à finale doit être contrôlé par transaction ; une seule version finale active par marché, règle applicative.

### 8.2 `market_bpu_items`

| Colonne | Type | Null | Description |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED PK AI | Non | Identifiant |
| `bpu_version_id` | BIGINT UNSIGNED | Non | Version BPU |
| `market_id` | INT UNSIGNED | Non | Marché |
| `company_id` | INT UNSIGNED | Non | Société |
| `line_number` | INT UNSIGNED | Non | Numéro de ligne |
| `item_code` | VARCHAR(60) | Oui | Code |
| `designation` | TEXT | Non | Désignation |
| `unit` | VARCHAR(30) | Oui | Unité |
| `quantity` | DECIMAL(15,3) | Non | Quantité |
| `unit_price_ht` | DECIMAL(15,4) | Non | Prix unitaire HT |
| `vat_rate_percent` | DECIMAL(5,2) | Non | Taux TVA de cette ligne |
| `line_total_ht` | DECIMAL(15,2) | Non | Quantité × prix HT |
| `line_total_vat` | DECIMAL(15,2) | Non | TVA |
| `line_total_ttc` | DECIMAL(15,2) | Non | TTC |
| `source_row_number` | INT UNSIGNED | Oui | Ligne du fichier importé |

Index UNIQUE `(bpu_version_id, line_number)`, FK composite `(bpu_version_id, market_id, company_id)` vers `market_bpu_versions(id, market_id, company_id)`. CHECK quantité, prix, TVA et montants >= 0 ; taux TVA <= 100. Calcul serveur : HT = quantité × prix unitaire HT ; TVA = HT × taux TVA / 100 ; TTC = HT + TVA. Arrondi monétaire à deux décimales selon la règle d'arrondi retenue dans le service métier. Les montants ne sont jamais acceptés aveuglément depuis le navigateur.

### 8.3 `market_bpu_imports`

| Colonne | Type | Null | Description |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED PK AI | Non | Identifiant |
| `market_id` | INT UNSIGNED | Non | Marché |
| `company_id` | INT UNSIGNED | Non | Société |
| `bpu_version_id` | BIGINT UNSIGNED | Non | Version créée |
| `source_document_id` | BIGINT UNSIGNED | Non | Fichier importé |
| `import_status` | VARCHAR(20) | Non | `en_cours`, `termine`, `partiel`, `rejete` |
| `rows_total` | INT UNSIGNED | Non | Lignes détectées |
| `rows_imported` | INT UNSIGNED | Non | Lignes intégrées |
| `rows_rejected` | INT UNSIGNED | Non | Lignes rejetées |
| `error_report_document_id` | BIGINT UNSIGNED | Oui | Rapport d'erreurs |
| `imported_by` | INT UNSIGNED FK users | Non | Auteur |
| `imported_at` | DATETIME | Non | Date |

Index `(market_id, imported_at)`, FK composites vers version et documents. Un import ne remplace jamais silencieusement une version antérieure.

## 9. Contrat, renouvellements et alertes

### 9.1 `market_contract_periods`

Chaque lot possède une période initiale et zéro ou plusieurs renouvellements. Ajouter `lot_id BIGINT UNSIGNED NOT NULL` et une FK composite vers `market_lots`; l'unicité de période est portée par lot.

| Colonne | Type | Null | Description |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED PK AI | Non | Identifiant |
| `market_id` | INT UNSIGNED | Non | Marché |
| `company_id` | INT UNSIGNED | Non | Société |
| `period_number` | SMALLINT UNSIGNED | Non | Ordre chronologique |
| `period_type` | VARCHAR(12) | Non | `initiale` ou `renouvellement` |
| `start_date` | DATE | Non | Début |
| `end_date` | DATE | Non | Fin |
| `duration_months` | SMALLINT UNSIGNED | Non | Durée réelle |
| `decision_date` | DATE | Oui | Décision de renouvellement |
| `period_status` | VARCHAR(16) | Non | `prevue`, `active`, `terminee`, `annulee` |
| `notes` | TEXT | Oui | Observations |
| `document_id` | BIGINT UNSIGNED | Oui | Justificatif |
| `created_by` | INT UNSIGNED FK users | Non | Auteur |
| `created_at` | DATETIME | Non | Création |

Index UNIQUE `(market_id, period_number)`, UNIQUE `(id, market_id, company_id)`, FK composites vers markets et documents. CHECK `end_date >= start_date`, durée > 0. L'application contrôle qu'une seule période initiale existe et que les périodes successives ne se chevauchent pas sans justification.

### 9.2 `market_alert_rules`

| Colonne | Type | Null | Description |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED PK AI | Non | Identifiant |
| `company_id` | INT UNSIGNED FK | Non | Société |
| `alert_type` | VARCHAR(30) | Non | Type d'échéance |
| `days_before` | SMALLINT UNSIGNED | Non | Délai avant échéance |
| `is_active` | TINYINT(1) | Non | Activation |
| `recipient_user_id` | INT UNSIGNED FK users | Oui | Destinataire par défaut |
| `created_by` | INT UNSIGNED FK users | Non | Auteur |
| `updated_at` | DATETIME | Non | Modification |

UNIQUE `(company_id, alert_type, days_before)`. Types V1 proposés : `renouvellement`, `fin_contrat`, `expiration_caution`, `remise_offres`.

### 9.3 `market_alerts`

| Colonne | Type | Null | Description |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED PK AI | Non | Identifiant |
| `company_id` | INT UNSIGNED FK | Non | Société |
| `market_id` | INT UNSIGNED | Non | Marché |
| `contract_period_id` | BIGINT UNSIGNED | Oui | Période concernée |
| `guarantee_id` | BIGINT UNSIGNED | Oui | Caution concernée |
| `rule_id` | BIGINT UNSIGNED FK | Non | Règle |
| `recipient_user_id` | INT UNSIGNED FK users | Oui | Destinataire |
| `deduplication_key` | CHAR(64) | Non | Clé déterministe anti-doublon |
| `scheduled_for` | DATETIME | Non | Date prévue |
| `alert_status` | VARCHAR(16) | Non | `pending`, `sent`, `acknowledged`, `cancelled` |
| `sent_at` | DATETIME | Oui | Envoi |
| `acknowledged_at` | DATETIME | Oui | Acquittement |
| `created_at` | DATETIME | Non | Création |

UNIQUE `deduplication_key`, KEY `(company_id, alert_status, scheduled_for)`. FK composite vers marché et FK vers période/caution avec cohérence du même marché. L'envoi effectif dépendra d'un traitement planifié ; aucune promesse d'envoi automatique avant mise en place de ce traitement.

## 10. Exécution, travaux et clôture

### 10.1 `market_execution_events`

Les événements d'exécution sont rattachés à un lot via `lot_id BIGINT UNSIGNED NOT NULL` et une FK composite vers `market_lots`.

| Colonne | Type | Null | Description |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED PK AI | Non | Identifiant |
| `market_id` | INT UNSIGNED | Non | Marché |
| `company_id` | INT UNSIGNED | Non | Société |
| `event_type` | VARCHAR(24) | Non | `demarrage`, `avancement`, `arret`, `reprise`, `observation`, `visite` |
| `event_date` | DATETIME | Non | Date de l'événement |
| `progress_percent` | DECIMAL(5,2) | Oui | Avancement physique |
| `work_amount` | DECIMAL(15,2) | Oui | Montant exécuté |
| `description` | TEXT | Non | Compte rendu |
| `document_id` | BIGINT UNSIGNED | Oui | Rapport ou photo |
| `created_by` | INT UNSIGNED FK users | Non | Auteur |
| `created_at` | DATETIME | Non | Création |

KEY `(market_id, event_date)`, FK composites vers marché et document. CHECK avancement entre 0 et 100, montant >= 0.

### 10.2 `market_work_situations`

Chaque situation est rattachée à un lot. Ajouter `lot_id BIGINT UNSIGNED NOT NULL` et une FK composite vers `market_lots`. Les montants HT, TVA et TTC sont enregistrés ; le TTC validé constitue la base du prélèvement de retenue.

| Colonne | Type | Null | Description |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED PK AI | Non | Identifiant |
| `market_id` | INT UNSIGNED | Non | Marché |
| `company_id` | INT UNSIGNED | Non | Société |
| `situation_number` | VARCHAR(40) | Non | N° situation |
| `period_start` | DATE | Non | Début période |
| `period_end` | DATE | Non | Fin période |
| `amount_ht` | DECIMAL(15,2) | Non | Montant HT |
| `amount_vat` | DECIMAL(15,2) | Non | TVA |
| `amount_ttc` | DECIMAL(15,2) | Non | TTC |
| `situation_status` | VARCHAR(20) | Non | `brouillon`, `soumise`, `validee`, `payee`, `rejetee` |
| `submitted_at` | DATETIME | Oui | Soumission |
| `approved_at` | DATETIME | Oui | Validation |
| `document_id` | BIGINT UNSIGNED | Oui | Décompte |
| `created_by` | INT UNSIGNED FK users | Non | Auteur |
| `created_at` | DATETIME | Non | Création |

UNIQUE `(market_id, situation_number)`, KEY `(market_id, period_start, period_end)`, FK composites vers marché et document.

### 10.3 `market_amendments`

Chaque avenant est rattaché au lot concerné via `lot_id` et une FK composite vers `market_lots`.

| Colonne | Type | Null | Description |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED PK AI | Non | Identifiant |
| `market_id` | INT UNSIGNED | Non | Marché |
| `company_id` | INT UNSIGNED | Non | Société |
| `amendment_number` | VARCHAR(60) | Non | Référence avenant |
| `amendment_date` | DATE | Non | Date |
| `reason` | TEXT | Non | Motif |
| `amount_delta` | DECIMAL(15,2) | Oui | Variation financière signée |
| `new_end_date` | DATE | Oui | Nouvelle date contractuelle |
| `document_id` | BIGINT UNSIGNED | Oui | Avenant signé |
| `created_by` | INT UNSIGNED FK users | Non | Auteur |
| `created_at` | DATETIME | Non | Création |

UNIQUE `(market_id, amendment_number)`, FK composites vers marché et document.

### 10.4 `market_receptions`

Chaque réception est rattachée au lot concerné via `lot_id` et une FK composite vers `market_lots`.

| Colonne | Type | Null | Description |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED PK AI | Non | Identifiant |
| `market_id` | INT UNSIGNED | Non | Marché |
| `company_id` | INT UNSIGNED | Non | Société |
| `reception_type` | VARCHAR(16) | Non | `provisoire`, `definitive` |
| `reception_date` | DATE | Non | Date de réception |
| `reservations` | TEXT | Oui | Réserves |
| `reservations_lifted_at` | DATE | Oui | Levée des réserves |
| `document_id` | BIGINT UNSIGNED | Oui | PV |
| `created_by` | INT UNSIGNED FK users | Non | Auteur |
| `created_at` | DATETIME | Non | Création |

KEY `(market_id, reception_type, reception_date)`, FK composites vers marché et document. Plusieurs réceptions peuvent être enregistrées ; la règle de cohérence provisoire/définitive est contrôlée par le service métier.

### 10.5 `market_closures`

La clôture est gérée par lot. Ajouter `lot_id` et porter l'unicité de clôture sur ce lot.

| Colonne | Type | Null | Description |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED PK AI | Non | Identifiant |
| `market_id` | INT UNSIGNED | Non | Marché |
| `company_id` | INT UNSIGNED | Non | Société |
| `tracking_end_date` | DATE | Non | Fin du suivi |
| `final_settlement_amount` | DECIMAL(15,2) | Oui | Décompte définitif |
| `retention_released_amount` | DECIMAL(15,2) | Oui | Retenue libérée |
| `guarantees_returned` | TINYINT(1) | Non | Confirmation restitution |
| `missing_documents_count` | SMALLINT UNSIGNED | Non | Nombre de pièces manquantes |
| `exception_reason` | TEXT | Oui | Justification des éléments non disponibles |
| `closure_notes` | TEXT | Oui | Observations finales |
| `closed_by` | INT UNSIGNED FK users | Non | Responsable |
| `closed_at` | DATETIME | Non | Date de clôture |

UNIQUE `(market_id)`, FK composite vers marché. CHECK montants >= 0. Les réserves et PV détaillés sont stockés dans `market_receptions`, pas dans un texte unique.

## 11. Synthèse des nouvelles tables

| Domaine | Tables proposées |
|---|---|
| Organismes et données générales | `market_organizations`, `market_details` |
| Étude, dépôt et attribution | `market_studies`, `market_submissions`, `market_award_details` |
| Cautions | `market_guarantees` |
| Paramètres et numérotation | `market_settings`, `market_setting_history`, `market_number_sequences` |
| Modèle documentaire | `market_document_templates`, `market_document_template_items` |
| Documents et checklist | `stored_documents`, `market_document_checklist_items`, `market_document_checklist_events` |
| Lots | `market_lots` |
| BPU | `market_bpu_versions`, `market_bpu_items`, `market_bpu_imports` |
| Renouvellements et alertes | `market_contract_periods`, `market_alert_rules`, `market_alerts` |
| Exécution et clôture | `market_execution_events`, `market_work_situations`, `market_amendments`, `market_receptions`, `market_closures` |

Total proposé : 26 nouvelles tables métier (les 25 tables précédemment listées, plus `market_lots`) et les colonnes additives nécessaires à la compatibilité, aux lots et aux instantanés de calcul. Le nombre exact de colonnes sera arrêté dans les migrations.

## 12. Ordre de création recommandé des futures migrations

1. Tables de référentiel, paramètres, numérotation et organismes.
2. Extensions de marché, détails, étude, dépôt et attribution.
3. Documents privés, modèles et checklist.
4. Cautions et retenues de garantie.
5. BPU, lignes, versions et imports.
6. Périodes contractuelles, renouvellements et alertes.
7. Exécution, situations, avenants, réceptions et clôture.
8. Index finaux, contraintes, contrôles de cohérence et audit.

Les clés étrangères vers `stored_documents` doivent être ajoutées après la création de cette table. Les tables de checklist doivent être créées après les tables de modèle et de documents. L'ordre définitif des instructions SQL sera établi dans les scripts de migration après validation.

## 13. Contrôles de cohérence obligatoires

- Toutes les tables de domaine possèdent `company_id` et une FK composite garantissant que le marché appartient à cette société.
- Toute donnée propre à un lot possède `lot_id`, `market_id`, `company_id` et une FK composite vers le même lot et le même marché.
- Un lot n° 1 est créé automatiquement ; un marché ne peut pas exister sans au moins un lot après activation du nouveau service.
- Les organismes référencés dans `market_details` appartiennent à la même société.
- Les fichiers référencés appartiennent au même marché et à la même société.
- Un numéro de dossier interne est unique dans sa société.
- Le compteur de numérotation est atomique et protégé contre les accès concurrents.
- Un seul détail, étude, dépôt, détail d'attribution et dossier de clôture par marché en V1.
- Les résultats officiels ne sont pas dupliqués : qualification/validation existantes restent la source officielle.
- Les versions BPU sont conservées ; les lignes d'une version finale sont verrouillées.
- Les lignes de checklist sont des instantanés indépendants du modèle.
- Les événements de checklist sont append-only.
- Le taux de prélèvement (10 % par défaut) et le plafond cumulé (7 % par défaut) sont configurables par société puis figés par lot.
- Chaque situation validée prélève 10 % de son montant TTC, dans la limite du solde disponible avant le plafond de 7 % du montant TTC initial attribué au lot.
- Les événements de retenue sont append-only ; le cumul ne peut pas dépasser le plafond.
- Les changements de paramètres ne recalculent pas l'historique.
- Les périodes de contrat ne se chevauchent pas sans dérogation explicite.
- Les alertes ont une clé anti-doublon.
- Les suppressions en cascade ne doivent jamais effacer l'historique contractuel, les pièces ou les résultats.
- Les fichiers sont privés et téléchargés via un contrôleur autorisé.
- Les contrôles CSRF, authentification, permissions et isolation sont côté serveur.

## 14. Décisions métier consolidées et points encore ouverts

Décisions confirmées :
1. Lot unique créé automatiquement par défaut ; lots supplémentaires indépendants possibles.
2. Résultats, validations, phases, BPU, cautions, périodes contractuelles et suivi d'exécution indépendants par lot.
3. BPU calculé HT, TVA et TTC par ligne ; taux TVA conservé sur chaque ligne et montants calculés côté serveur.
4. Retenue : 10 % du TTC de chaque situation validée, plafonnée à 7 % du montant TTC initial attribué au lot ; dernier prélèvement limité au solde du plafond.
5. Taux et plafond modifiables dans les paramètres propres à chaque société ; instantanés historiques conservés.
6. Cautions successives conservées sans écrasement.
7. Paramètres communs au groupe reportés à une version ultérieure.

Points ouverts :
- Règles de renouvellement partiel ou complet.
- Notifications internes ou e-mail/SMS.
- Formats, tailles maximales et durée de conservation des documents.

## 15. Sécurité et déploiement

Ce MLD n'est pas un script SQL et n'est pas exécutable.

Avant toute migration :
- obtenir le schéma réel de la base et vérifier si les migrations 001 à 004 ont déjà été installées ;
- produire une matrice de compatibilité entre le schéma réel et le dépôt Git ;
- sauvegarder et tester sur une copie ;
- préparer des migrations additives compatibles MariaDB 10.4.32 ;
- contrôler chaque FK, index, contrainte et règle d'isolation ;
- obtenir l'autorisation explicite de l'utilisateur avant toute exécution.

Ne jamais modifier `config/database.local.php`, ne jamais rejouer aveuglément les migrations existantes et ne jamais supprimer ou renommer les colonnes historiques dans une migration non approuvée.
