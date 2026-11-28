Référence : erp-manager/docs/EXAMEN_MARCHES_A_QUALIFIER.md

# Examen documentaire des marchés à qualifier

## 1. Portée et méthode

Audit strictement en lecture seule. Aucune donnée SQL, aucun statut, aucune phase, aucune transition et aucun fichier n'ont été modifiés. Les informations proviennent de la base `erp_manager` (tables `markets`, `companies`, `market_transitions`) et du dépôt applicatif. Aucun document d'attribution, de notification ou d'annulation n'a été trouvé dans l'application (aucun module de gestion documentaire n'existe).

## 2. Tableau comparatif

| Élément | M-2026-005 | M-2026-001 |
|---|---|---|
| ID | 4 | 8 |
| Société | ATLAS INFRA & SERVICES (code ATLAS_INFRA, id 4) | ATLAS INFRA & SERVICES (code ATLAS_INFRA, id 4) |
| Objet | Réhabilitation infrastructure routière régionale | Maintenance réseaux et génie urbain |
| Statut ancien | `gagne` | `perdu_annule` |
| Phase ancienne | 5 (Attribution / Verdict) | 5 (Attribution / Verdict) |
| Statut actuel | `a_qualifier` | `a_qualifier` |
| Phase actuelle | 5 (Attente du résultat) | 5 (Attente du résultat) |
| Montant estimatif | 1 640 000,00 MAD | 540 000,00 MAD |
| Date du marché | 2026-11-14 | 2026-10-28 |
| Maître d'ouvrage | Non renseigné dans la base | Non renseigné dans la base |
| Numéro d'appel d'offres | Non renseigné dans la base | Non renseigné dans la base |
| Date de décision | NULL | NULL |
| Montant attribué | NULL | NULL |
| Entreprise gagnante (concurrent) | NULL | NULL |
| Motif d'annulation / d'infructuosité | NULL | NULL |
| Observations | NULL | NULL |
| Données de démonstration | Oui (`is_demo = 1`) | Oui (`is_demo = 1`) |
| Transition enregistrée | `migration` (NULL → `a_qualifier` / phase 5) | `migration` (NULL → `a_qualifier` / phase 5) |

## 3. Fiche détaillée — M-2026-005

### 3.1 Informations retrouvées (faits vérifiés)
- Référence : `M-2026-005`.
- Société : ATLAS INFRA & SERVICES.
- Objet : Réhabilitation infrastructure routière régionale.
- Montant estimatif : 1 640 000,00 MAD.
- Date du marché : 2026-11-14.
- Ancien statut : `gagne` (libellé historique « Attribué »).
- Ancienne phase : 5 (Attribution / Verdict).
- Nouveau statut : `a_qualifier` (phase 5).
- Données de démonstration (`is_demo = 1`).
- Transition de migration enregistrée le 2026-11-28, motif : « À qualifier par GEST : l'ancien statut gagné ne fournit pas à lui seul la date de décision et le montant attribué obligatoires. »

### 3.2 Documents disponibles
Aucun document associé n'a été trouvé dans l'application (pas de module de gestion documentaire, pas de table d'attachments). Les pièces attendues (avis d'attribution, procès-verbal d'ouverture des plis, lettre de notification, etc.) ne sont pas présentes dans le dépôt.

### 3.3 Éléments contradictoires
- L'ancien statut `gagne` suggère une attribution, mais aucune date de décision, aucun montant attribué ni aucune pièce justificative n'est disponible.
- Le marché est marqué `is_demo = 1` : il s'agit de données de démonstration, ce qui affaiblit la valeur probante de l'ancien statut.

### 3.4 Informations manquantes
- Maître d'ouvrage.
- Numéro d'appel d'offres.
- Date de décision d'attribution.
- Montant attribué.
- Pièces justificatives (avis d'attribution, notification, PV).

### 3.5 Situation la plus probable (hypothèse, non vérifiée)
Attribution possible mais non étayée par les documents disponibles. L'ancien statut `gagne` seul ne suffit pas à conclure.

### 3.6 Statut proposé
`reste a_qualifier` (en l'absence de justificatifs).

### 3.7 Justificatifs nécessaires avant validation
- Avis d'attribution ou lettre de notification.
- Procès-verbal d'ouverture des plis.
- Date de décision et montant attribué.
- Confirmation du maître d'ouvrage et du numéro d'appel d'offres.

## 4. Fiche détaillée — M-2026-001

### 4.1 Informations retrouvées (faits vérifiés)
- Référence : `M-2026-001`.
- Société : ATLAS INFRA & SERVICES.
- Objet : Maintenance réseaux et génie urbain.
- Montant estimatif : 540 000,00 MAD.
- Date du marché : 2026-10-28.
- Ancien statut : `perdu_annule` (libellé historique « Perdu / annulé »).
- Ancienne phase : 5 (Attribution / Verdict).
- Nouveau statut : `a_qualifier` (phase 5).
- Données de démonstration (`is_demo = 1`).
- Transition de migration enregistrée le 2026-11-28, motif : « À qualifier par GEST : l'ancien statut ne distingue pas perdu, annulé et infructueux. »

### 4.2 Documents disponibles
Aucun document associé n'a été trouvé dans l'application.

### 4.3 Éléments contradictoires
- L'ancien statut `perdu_annule` regroupe trois issues distinctes (perdu, annulé, infructueux) sans permettre de les différencier.
- Le marché est marqué `is_demo = 1` : données de démonstration.

### 4.4 Informations manquantes
- Maître d'ouvrage.
- Numéro d'appel d'offres.
- Issue réelle : perdu, annulé ou infructueux.
- Si perdu : entreprise gagnante concurrente, montant retenu, observations.
- Si annulé ou infructueux : motif.
- Dates pertinentes (ouverture des plis, décision).

### 4.5 Situation la plus probable (hypothèse, non vérifiée)
Aucune issue ne peut être déterminée à partir des données disponibles. Le statut `perdu_annule` est ambigu par construction.

### 4.6 Statut proposé
`reste a_qualifier`.

### 4.7 Justificatifs nécessaires avant validation
- Résultat de l'appel d'offres (attribution à un concurrent ou déclaration d'infructuosité).
- Décision d'annulation le cas échéant.
- Correspondances avec le maître d'ouvrage.
- Rapports d'étude et observations internes.

## 5. Questions à l'utilisateur

Pour M-2026-005 :
1. Un avis d'attribution ou une lettre de notification existe-t-il ? Si oui, quelle est la date de décision et le montant attribué ?
2. Quel est le maître d'ouvrage et le numéro d'appel d'offres ?
3. Le marché est-il réellement attribué, ou s'agit-il d'une donnée de démonstration à ignorer ?

Pour M-2026-001 :
4. L'appel d'offres a-t-il été perdu (au profit de quel concurrent et à quel montant), annulé ou déclaré infructueux ?
5. Existe-t-il une décision d'annulation ou une déclaration d'infructuosité ?
6. Quel est le maître d'ouvrage et le numéro d'appel d'offres ?

Aucune décision métier n'a été prise. Aucune donnée n'a été modifiée. Les deux marchés restent `a_qualifier`.
