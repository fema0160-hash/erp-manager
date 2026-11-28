Référence : erp-manager/docs/SUPPRESSION_MARCHES_DEMO.md

# Suppression définitive de deux marchés de démonstration

## 1. Sauvegarde préalable

- Fichier : `storage/backups/erp_manager_backup_pre_delete_20261128_010315.sql` (24 090 octets).
- Contenu : structure et données complètes de `erp_manager` (avant suppression).
- Vérification : fichier existant, taille cohérente, non vide.
- Empreinte SHA-256 : non calculée (fichier de sauvegarde non présent dans ce dossier).

## 2. Vérifications préalables (lecture seule)

- Références existantes : `M-2026-005` (id 4) et `M-2026-001` (id 8) — confirmées.
- `is_demo = 1` pour les deux marchés — confirmé.
- Correspondance exacte des références — confirmée.
- Dépendances : 1 transition `market_transitions` par marché (market_id 4 et 8). Aucune autre table ne référence `markets` (pas de pièces jointes).
- Aucun autre marché, utilisateur ou entreprise n'a été touché.

## 3. Suppression effectuée

Dans une transaction unique, sans désactivation des contraintes :
1. `DELETE FROM market_transitions WHERE market_id IN (SELECT id FROM markets WHERE reference IN ('M-2026-005','M-2026-001') AND is_demo = 1);` → 2 lignes supprimées.
2. `DELETE FROM markets WHERE reference IN ('M-2026-005','M-2026-001') AND is_demo = 1;` → 2 lignes supprimées.
3. `COMMIT;`

Code de sortie : 0 (succès).

## 4. Vérifications après suppression

- `M-2026-005` et `M-2026-001` : 0 occurrence dans `markets`.
- Transitions des market_id 4 et 8 : 0 occurrence dans `market_transitions`.
- Marchés restants : 8 (contre 10 avant).
- Transitions restantes : 8 (contre 10 avant).
- Utilisateurs : 3 (inchangé).
- Entreprises : 5 (inchangé).
- Application : code HTTP 200 pour GEST, SECR et CHAD ; aucun message d'erreur ; références supprimées absentes de l'affichage.

## 5. Anomalies

Aucune anomalie détectée.

## 6. Conclusion

Suppression définitive réalisée avec succès. Seuls les deux marchés de démonstration `M-2026-005` et `M-2026-001` et leurs transitions associées ont été supprimés. Toutes les autres données sont intactes.
