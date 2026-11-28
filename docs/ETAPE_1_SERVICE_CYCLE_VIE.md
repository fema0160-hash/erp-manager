Référence : erp-manager/docs/ETAPE_1_SERVICE_CYCLE_VIE.md

# Étape 1 — Service central et cohérence des statuts

## 1. Périmètre réalisé

Modifications publiées directement sur la branche `main` du dépôt `fema0160-hash/erp-manager`.

- Ajout de `app/MarketLifecycle.php`.
- Raccordement de la création des marchés au service central.
- Utilisation de `lifecycle_status` et `lifecycle_phase` comme références de lecture dans la liste des marchés et le tableau de bord.
- Mise à jour des libellés des sept statuts et des huit phases.
- Mise à jour des filtres du tableau de bord, dont le raccourci « Attribués ».

## 2. Service central

Le service `MarketLifecycle` centralise :

- les sept codes et libellés de statut ;
- les huit libellés de phase ;
- l'état initial unique : `en_cours`, phase 1 ;
- les correspondances de compatibilité vers les anciennes colonnes `status` et `phase` ;
- la création transactionnelle d'un marché et de son événement initial dans `market_transitions`.

## 3. Cohérence de la création

Une nouvelle création écrit désormais dans la même opération :

- les colonnes historiques `status` et `phase` ;
- les colonnes normalisées `lifecycle_status` et `lifecycle_phase` ;
- une ligne d'historique `action_type = creation`, avec l'utilisateur et la date gérée par la base.

Si l'insertion de l'historique échoue, la création du marché est annulée par rollback.

## 4. Compatibilité temporaire

Les anciennes colonnes restent renseignées pour satisfaire les contraintes historiques et éviter de casser les consommateurs non encore migrés.

Correspondances prévues par le service :

| Statut normalisé | Ancien statut |
|---|---|
| en_cours | en_cours |
| attribue | gagne |
| perdu / annule / infructueux | perdu_annule |
| cloture | cloture |
| a_qualifier | en_attente |

Les phases normalisées 6 et 7 sont représentées par l'ancienne phase 5 ; la phase normalisée 8 correspond à l'ancienne phase 6. Cette compatibilité devra être retirée seulement après migration de tous les consommateurs et validation dédiée.

## 5. Fichiers modifiés

- `app/MarketLifecycle.php` — nouveau service.
- `public/index.php` — chargement du service, création transactionnelle, lecture et filtre sur les colonnes normalisées.
- `resources/views/markets.php` — libellés centralisés.
- `public/assets/js/dashboard.js` — sept statuts et huit phases.
- `resources/views/dashboard.php` — affichage huit phases et raccourci « Attribués ».

## 6. Vérifications et limites

Contrôles statiques effectués après publication : présence du service, utilisation des colonnes normalisées dans les requêtes, présence des sept statuts et huit phases dans le tableau de bord, présence de l'historique initial dans la transaction.

**Non effectués :** exécution PHP, tests navigateur, tests SQL ou tests d'intégration sur XAMPP. Les vérifications statiques ne remplacent pas ces essais.

## 7. Prérequis et sécurité

- La migration 003 doit déjà être installée sur la base utilisée : elle crée les colonnes `lifecycle_*` et la table `market_transitions`.
- Aucune migration SQL n'a été exécutée ni modifiée.
- Aucune donnée de base n'a été consultée ou modifiée par ces changements de code.
- Aucun endpoint de transition, de décision, de clôture ou de réouverture n'est encore implémenté.
- Les prochaines transitions devront être ajoutées au même service et écrites dans une transaction avec leur historique.

## 8. Conclusion

L'étape 1 pose le socle métier et corrige la divergence à la création et à la lecture des statuts/phases. Le cycle de vie complet n'est pas encore opérationnel : l'étape suivante devra implémenter les transitions autorisées et leurs contrôles serveur, puis les formulaires de décision.
