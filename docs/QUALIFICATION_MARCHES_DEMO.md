Référence : erp-manager/docs/QUALIFICATION_MARCHES_DEMO.md

# Qualification des marchés de démonstration

Statut : qualifications proposées, non exécutées. Aucune donnée n'a été modifiée.

Note chronologique : ce rapport décrit des qualifications proposées avant la suppression des deux marchés de démonstration. Ces marchés ont ensuite été supprimés définitivement (voir `SUPPRESSION_MARCHES_DEMO.md`).

## 1. Identification des marchés

- `M-2026-005` (id 4) : `is_demo = 1` — marché de démonstration confirmé.
- `M-2026-001` (id 8) : `is_demo = 1` — marché de démonstration confirmé.

## 2. Vérification des règles métier et champs obligatoires

### M-2026-005 — cible `attribue`
- Champs requis par la contrainte `chk_markets_decision_data` :
  - `awarded_amount` NOT NULL ✓ (valeur fictive : 1 250 000,00 MAD)
  - `decision_date` NOT NULL ✓ (valeur fictive : 2026-10-15)
- Transition attendue : `a_qualifier` (phase 5) → `attribue` (phase 6), avec enregistrement dans `market_transitions`.

### M-2026-001 — cible `infructueux`
- Champs requis par la contrainte `chk_markets_decision_data` :
  - `decision_reason` NOT NULL et non vide ✓ (valeur fictive : « Aucune offre conforme aux exigences du dossier. »)
  - `decision_date` recommandée : 2026-09-20 (fictive).
- Transition attendue : `a_qualifier` (phase 5) → `infructueux` (phase 5, issue définitive), avec enregistrement dans `market_transitions`.

Les valeurs proposées satisfont les contraintes SQL CHECK.

## 3. Mécanismes applicatifs disponibles

- Aucun endpoint de qualification n'est implémenté à ce stade (pas de route `marche_qualifier`, pas de formulaire de décision, pas d'écriture dans `market_transitions`).
- Les contrôles `markets.update` sont préparés mais non activés.
- Par conséquent, aucune qualification n'a été effectuée : **aucune donnée n'a été modifiée**, conformément à la règle interdisant de contourner les règles par du SQL direct.

## 4. Valeurs à appliquer (en attente)

### M-2026-005
- `lifecycle_status` : `attribue`
- `lifecycle_phase` : 6
- `decision_date` : 2026-10-15
- `awarded_amount` : 1250000.00
- `decision_observations` : « Résultat fictif de démonstration — attribution validée à titre de test. »

### M-2026-001
- `lifecycle_status` : `infructueux`
- `lifecycle_phase` : 5
- `decision_date` : 2026-09-20
- `decision_reason` : « Aucune offre conforme aux exigences du dossier. »
- `decision_observations` : « Résultat fictif de démonstration — marché déclaré infructueux à titre de test. »

## 5. Opérations restant à effectuer

Pour appliquer ces qualifications de manière conforme, il faut soit :
1. Implémenter les mécanismes applicatifs réservés à GEST (endpoint de qualification, formulaire de décision, écriture dans `market_transitions`), puis qualifier via l'interface ; soit
2. Obtenir une autorisation spécifique pour une exécution SQL contrôlée (mise à jour de `markets` + insertion dans `market_transitions`), avec sauvegarde préalable.

Aucune de ces deux options n'a été exécutée. Les deux marchés restent `a_qualifier`.

## 6. Confirmation

- Aucune donnée modifiée.
- Aucune transition créée.
- Aucun autre marché touché.
- Aucune configuration modifiée.
- Les résultats sont identifiés comme fictifs dans les observations prévues.
