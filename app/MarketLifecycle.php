<?php
declare(strict_types=1);

/**
 * Service central des règles du cycle de vie des marchés.
 * lifecycle_status/lifecycle_phase sont la référence métier.
 * status/phase restent synchronisés pour compatibilité historique.
 */
final class MarketLifecycle
{
    private const STATUSES = [
        'en_cours' => 'En cours',
        'attribue' => 'Attribué',
        'perdu' => 'Perdu',
        'annule' => 'Annulé',
        'infructueux' => 'Infructueux',
        'cloture' => 'Clôturé / archivé',
        'a_qualifier' => 'À qualifier',
    ];

    private const PHASES = [
        1 => 'Préparation',
        2 => 'Étude et BPU',
        3 => 'Préparation de la soumission',
        4 => 'Soumis',
        5 => 'Attente du résultat',
        6 => 'Préparation du marché attribué',
        7 => 'Exécution / suivi du chantier',
        8 => 'Clôturé / archivé',
    ];

    public static function statusCodes(): array
    {
        return array_keys(self::STATUSES);
    }

    public static function statusLabels(): array
    {
        return self::STATUSES;
    }

    public static function phaseLabels(): array
    {
        return self::PHASES;
    }

    public static function initialState(): array
    {
        return [
            'status' => 'en_cours',
            'phase' => 1,
            'legacy_status' => 'en_cours',
            'legacy_phase' => 1,
        ];
    }

    /** Correspondance vers l'ancien CHECK status limité à cinq valeurs. */
    public static function legacyStatus(string $status): string
    {
        return match ($status) {
            'en_cours' => 'en_cours',
            'attribue' => 'gagne',
            'perdu', 'annule', 'infructueux' => 'perdu_annule',
            'cloture' => 'cloture',
            'a_qualifier' => 'en_attente',
            default => throw new InvalidArgumentException('Statut de cycle de vie inconnu.'),
        };
    }

    /** Correspondance vers l'ancien CHECK phase limité à 1–6. */
    public static function legacyPhase(int $phase): int
    {
        return match ($phase) {
            1 => 1,
            2 => 3,
            3 => 2,
            4 => 4,
            5, 6, 7 => 5,
            8 => 6,
            default => throw new InvalidArgumentException('Phase de cycle de vie invalide.'),
        };
    }

    /**
     * Crée le marché et son événement initial dans une transaction unique.
     * Les colonnes normalisées et historiques sont écrites ensemble.
     */
    public static function createInitial(PDO $pdo, array $market, int $userId): int
    {
        $reference = trim((string) ($market['reference'] ?? ''));
        $object = trim((string) ($market['object'] ?? ''));
        $companyId = (int) ($market['company_id'] ?? 0);
        $amount = (string) ($market['amount'] ?? '');
        $marketDate = (string) ($market['market_date'] ?? '');
        $date = DateTime::createFromFormat('!Y-m-d', $marketDate);

        if ($reference === '' || strlen($reference) > 40
            || $object === '' || strlen($object) > 10000
            || $companyId < 1 || $userId < 1
            || !preg_match('/^\d{1,13}(\.\d{1,2})?$/', $amount)
            || !is_numeric($amount) || (float) $amount < 0
            || !$date || $date->format('Y-m-d') !== $marketDate) {
            throw new InvalidArgumentException('Les données du marché sont invalides.');
        }

        $state = self::initialState();
        $pdo->beginTransaction();

        try {
            $insert = $pdo->prepare(
                'INSERT INTO markets
                    (reference, object, company_id, status, phase,
                     lifecycle_status, lifecycle_phase, amount, market_date, is_demo)
                 VALUES
                    (:reference, :object, :company_id, :legacy_status, :legacy_phase,
                     :lifecycle_status, :lifecycle_phase, :amount, :market_date, 0)'
            );
            $insert->execute([
                'reference' => $reference,
                'object' => $object,
                'company_id' => $companyId,
                'legacy_status' => $state['legacy_status'],
                'legacy_phase' => $state['legacy_phase'],
                'lifecycle_status' => $state['status'],
                'lifecycle_phase' => $state['phase'],
                'amount' => $amount,
                'market_date' => $marketDate,
            ]);

            $marketId = (int) $pdo->lastInsertId();
            $history = $pdo->prepare(
                'INSERT INTO market_transitions
                    (market_id, action_type, from_status, from_phase,
                     to_status, to_phase, changed_by, reason)
                 VALUES
                    (:market_id, :action_type, NULL, NULL,
                     :to_status, :to_phase, :changed_by, :reason)'
            );
            $history->execute([
                'market_id' => $marketId,
                'action_type' => 'creation',
                'to_status' => $state['status'],
                'to_phase' => $state['phase'],
                'changed_by' => $userId,
                'reason' => 'Création initiale du marché.',
            ]);

            $pdo->commit();
            return $marketId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}
