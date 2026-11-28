<?php
declare(strict_types=1);

/**
 * Service central des règles du cycle de vie des marchés.
 * lifecycle_status/lifecycle_phase sont la référence métier.
 * status/phase restent synchronisés pour compatibilité historique.
 *
 * Les méthodes de progression, qualification et validation exigent la migration 004.
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

    public static function statusCodes(): array { return array_keys(self::STATUSES); }
    public static function statusLabels(): array { return self::STATUSES; }
    public static function phaseLabels(): array { return self::PHASES; }

    public static function initialState(): array
    {
        return ['status' => 'en_cours', 'phase' => 1, 'legacy_status' => 'en_cours', 'legacy_phase' => 1];
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
            1 => 1, 2 => 3, 3 => 2, 4 => 4, 5, 6, 7 => 5, 8 => 6,
            default => throw new InvalidArgumentException('Phase de cycle de vie invalide.'),
        };
    }

    public static function createInitial(PDO $pdo, array $market, int $userId): int
    {
        $reference = trim((string) ($market['reference'] ?? ''));
        $object = trim((string) ($market['object'] ?? ''));
        $companyId = (int) ($market['company_id'] ?? 0);
        $amount = (string) ($market['amount'] ?? '');
        $marketDate = (string) ($market['market_date'] ?? '');
        $date = DateTime::createFromFormat('!Y-m-d', $marketDate);

        if ($reference === '' || strlen($reference) > 40 || $object === '' || strlen($object) > 10000
            || $companyId < 1 || $userId < 1 || !preg_match('/^\d{1,13}(\.\d{1,2})?$/', $amount)
            || !is_numeric($amount) || (float) $amount < 0
            || !$date || $date->format('Y-m-d') !== $marketDate) {
            throw new InvalidArgumentException('Les données du marché sont invalides.');
        }

        $state = self::initialState();
        $pdo->beginTransaction();
        try {
            $insert = $pdo->prepare(
                'INSERT INTO markets
                    (reference, object, company_id, status, phase, lifecycle_status, lifecycle_phase,
                     amount, market_date, is_demo)
                 VALUES (:reference, :object, :company_id, :legacy_status, :legacy_phase,
                         :lifecycle_status, :lifecycle_phase, :amount, :market_date, 0)'
            );
            $insert->execute([
                'reference' => $reference, 'object' => $object, 'company_id' => $companyId,
                'legacy_status' => $state['legacy_status'], 'legacy_phase' => $state['legacy_phase'],
                'lifecycle_status' => $state['status'], 'lifecycle_phase' => $state['phase'],
                'amount' => $amount, 'market_date' => $marketDate,
            ]);
            $marketId = (int) $pdo->lastInsertId();
            self::insertHistory($pdo, $marketId, 'creation', null, null, 'en_cours', 1, $userId, 'Création initiale du marché.');
            $pdo->commit();
            return $marketId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    /**
     * Progression normale : 1→2→3→4→5, puis 6→7→8.
     * La clôture exige une confirmation explicite et une note de complétude.
     */
    public static function advancePhase(
        PDO $pdo,
        int $marketId,
        int $userId,
        string $reason = '',
        bool $closureConfirmed = false
    ): void {
        self::transaction($pdo, function () use ($pdo, $marketId, $userId, $reason, $closureConfirmed): void {
            $market = self::lockMarket($pdo, $marketId);
            $phase = (int) $market['lifecycle_phase'];
            $status = (string) $market['lifecycle_status'];

            if (in_array($phase, [1, 2, 3, 4], true)) {
                if ($status !== 'en_cours') {
                    throw new DomainException('Seul un marché en cours peut avancer dans les phases 1 à 5.');
                }
                $nextPhase = $phase + 1;
                $action = 'phase';
            } elseif ($phase === 6 && $status === 'attribue') {
                if (mb_strlen(trim($reason)) < 5) {
                    throw new InvalidArgumentException('Indiquez les conditions de démarrage avant le passage en exécution.');
                }
                $nextPhase = 7;
                $action = 'demarrage';
            } elseif ($phase === 7 && $status === 'attribue') {
                if (!$closureConfirmed || mb_strlen(trim($reason)) < 10) {
                    throw new InvalidArgumentException('Confirmez la complétude du dossier et décrivez les pièces contrôlées (10 caractères minimum).');
                }
                $nextPhase = 8;
                $action = 'cloture';
                $status = 'cloture';
            } else {
                throw new DomainException('Cette transition n’est pas autorisée pour le statut et la phase actuels.');
            }

            $legacyStatus = self::legacyStatus($status);
            $legacyPhase = self::legacyPhase($nextPhase);
            $update = $pdo->prepare(
                'UPDATE markets SET lifecycle_status = :status, lifecycle_phase = :phase,
                    status = :legacy_status, phase = :legacy_phase WHERE id = :id'
            );
            $update->execute([
                'status' => $status, 'phase' => $nextPhase, 'legacy_status' => $legacyStatus,
                'legacy_phase' => $legacyPhase, 'id' => $marketId,
            ]);
            self::insertHistory(
                $pdo, $marketId, $action, (string) $market['lifecycle_status'], (int) $market['lifecycle_phase'],
                $status, $nextPhase, $userId, trim($reason) !== '' ? trim($reason) : 'Passage à la phase suivante.'
            );
        });
    }

    /** Enregistre une proposition de résultat sans modifier le résultat officiel. */
    public static function qualifyResult(PDO $pdo, int $marketId, int $userId, array $data): int
    {
        return self::transaction($pdo, function () use ($pdo, $marketId, $userId, $data): int {
            $market = self::lockMarket($pdo, $marketId);
            if ((int) $market['lifecycle_phase'] !== 5
                || !in_array((string) $market['lifecycle_status'], ['en_cours', 'a_qualifier'], true)) {
                throw new DomainException('La qualification est possible uniquement en phase 5, avant toute décision officielle.');
            }

            $pending = $pdo->prepare(
                'SELECT q.id FROM market_result_qualifications q
                 LEFT JOIN market_result_validations v ON v.qualification_id = q.id
                 WHERE q.market_id = :market_id AND v.id IS NULL LIMIT 1'
            );
            $pending->execute(['market_id' => $marketId]);
            if ($pending->fetchColumn()) {
                throw new DomainException('Une qualification attend déjà sa validation pour ce marché.');
            }

            $result = (string) ($data['result_code'] ?? '');
            if (!in_array($result, ['attribue', 'perdu', 'annule', 'infructueux'], true)) {
                throw new InvalidArgumentException('Sélectionnez un résultat valide.');
            }
            $decisionDate = trim((string) ($data['decision_date'] ?? ''));
            $decisionReason = trim((string) ($data['decision_reason'] ?? ''));
            $winner = trim((string) ($data['winner_company_name'] ?? ''));
            $winnerAmount = self::money($data['winner_amount'] ?? null);
            $observations = trim((string) ($data['decision_observations'] ?? ''));
            $awardedAmount = self::money($data['awarded_amount'] ?? null);

            if ($result === 'attribue') {
                self::assertDate($decisionDate, 'La date de décision est obligatoire.');
                if ($awardedAmount === null) throw new InvalidArgumentException('Le montant attribué est obligatoire et doit être positif ou nul.');
            } elseif ($result === 'perdu') {
                if ($winner === '' || mb_strlen($winner) > 180 || $winnerAmount === null || $observations === '') {
                    throw new InvalidArgumentException('Pour un marché perdu, indiquez le concurrent, son montant et les observations.');
                }
            } elseif ($decisionReason === '') {
                throw new InvalidArgumentException('Le motif est obligatoire pour un marché annulé ou infructueux.');
            }

            $insert = $pdo->prepare(
                'INSERT INTO market_result_qualifications
                    (market_id, result_code, decision_date, decision_reason, winner_company_name,
                     winner_amount, decision_observations, awarded_amount, qualified_by)
                 VALUES (:market_id, :result_code, :decision_date, :decision_reason, :winner,
                         :winner_amount, :observations, :awarded_amount, :qualified_by)'
            );
            $insert->execute([
                'market_id' => $marketId, 'result_code' => $result,
                'decision_date' => $decisionDate !== '' ? $decisionDate : null,
                'decision_reason' => $decisionReason !== '' ? $decisionReason : null,
                'winner' => $winner !== '' ? $winner : null, 'winner_amount' => $winnerAmount,
                'observations' => $observations !== '' ? $observations : null,
                'awarded_amount' => $awardedAmount, 'qualified_by' => $userId,
            ]);
            $qualificationId = (int) $pdo->lastInsertId();
            self::insertHistory(
                $pdo, $marketId, 'qualification', (string) $market['lifecycle_status'],
                (int) $market['lifecycle_phase'], (string) $market['lifecycle_status'],
                (int) $market['lifecycle_phase'], $userId, 'Résultat proposé : ' . $result . '.', $qualificationId
            );
            return $qualificationId;
        });
    }

    /** Valide ou refuse une qualification ; une décision validée devient officielle atomiquement. */
    public static function validateResult(
        PDO $pdo,
        int $marketId,
        int $qualificationId,
        int $userId,
        string $decision,
        string $reason = ''
    ): void {
        self::transaction($pdo, function () use ($pdo, $marketId, $qualificationId, $userId, $decision, $reason): void {
            $market = self::lockMarket($pdo, $marketId);
            if ((int) $market['lifecycle_phase'] !== 5
                || !in_array((string) $market['lifecycle_status'], ['en_cours', 'a_qualifier'], true)) {
                throw new DomainException('Le marché ne se trouve plus en attente de résultat.');
            }

            $q = $pdo->prepare(
                'SELECT q.* FROM market_result_qualifications q
                 WHERE q.id = :qualification_id AND q.market_id = :market_id FOR UPDATE'
            );
            $q->execute(['qualification_id' => $qualificationId, 'market_id' => $marketId]);
            $qualification = $q->fetch();
            if (!$qualification) throw new DomainException('Cette qualification est introuvable pour ce marché.');

            $already = $pdo->prepare('SELECT id FROM market_result_validations WHERE qualification_id = :id LIMIT 1');
            $already->execute(['id' => $qualificationId]);
            if ($already->fetchColumn()) throw new DomainException('Cette qualification a déjà été traitée.');

            if (!in_array($decision, ['validee', 'refusee'], true)) {
                throw new InvalidArgumentException('Décision de validation invalide.');
            }
            if ($decision === 'refusee' && trim($reason) === '') {
                throw new InvalidArgumentException('Le motif du refus est obligatoire.');
            }

            $validation = $pdo->prepare(
                'INSERT INTO market_result_validations
                    (qualification_id, validation_decision, validated_by, validation_reason)
                 VALUES (:qualification_id, :decision, :user_id, :reason)'
            );
            $validation->execute([
                'qualification_id' => $qualificationId, 'decision' => $decision,
                'user_id' => $userId, 'reason' => trim($reason) !== '' ? trim($reason) : null,
            ]);

            $oldStatus = (string) $market['lifecycle_status'];
            $oldPhase = (int) $market['lifecycle_phase'];
            if ($decision === 'refusee') {
                self::insertHistory(
                    $pdo, $marketId, 'validation_refusee', $oldStatus, $oldPhase, $oldStatus, $oldPhase,
                    $userId, trim($reason), $qualificationId
                );
                return;
            }

            $result = (string) $qualification['result_code'];
            $newStatus = $result;
            $newPhase = $result === 'attribue' ? 6 : 5;
            $update = $pdo->prepare(
                'UPDATE markets SET lifecycle_status = :status, lifecycle_phase = :phase,
                    status = :legacy_status, phase = :legacy_phase,
                    decision_date = :decision_date, decision_reason = :decision_reason,
                    winner_company_name = :winner, winner_amount = :winner_amount,
                    decision_observations = :observations, awarded_amount = :awarded_amount,
                    official_result_qualification_id = :qualification_id
                 WHERE id = :id'
            );
            $update->execute([
                'status' => $newStatus, 'phase' => $newPhase,
                'legacy_status' => self::legacyStatus($newStatus), 'legacy_phase' => self::legacyPhase($newPhase),
                'decision_date' => $qualification['decision_date'],
                'decision_reason' => $qualification['decision_reason'],
                'winner' => $qualification['winner_company_name'],
                'winner_amount' => $qualification['winner_amount'],
                'observations' => $qualification['decision_observations'],
                'awarded_amount' => $qualification['awarded_amount'],
                'qualification_id' => $qualificationId, 'id' => $marketId,
            ]);
            self::insertHistory(
                $pdo, $marketId, 'decision', $oldStatus, $oldPhase, $newStatus, $newPhase,
                $userId, 'Résultat validé : ' . $result . '.', $qualificationId
            );
        });
    }

    /** Réouverture exceptionnelle GEST : retour contrôlé en phase 5, sans effacer l'historique. */
    public static function reopen(PDO $pdo, int $marketId, int $userId, string $role, string $reason): void
    {
        if ($role !== 'GEST') throw new DomainException('La réouverture est réservée à GEST.');
        if (mb_strlen(trim($reason)) < 10) throw new InvalidArgumentException('Le motif de réouverture doit contenir au moins 10 caractères.');

        self::transaction($pdo, function () use ($pdo, $marketId, $userId, $reason): void {
            $market = self::lockMarket($pdo, $marketId);
            $status = (string) $market['lifecycle_status'];
            $phase = (int) $market['lifecycle_phase'];
            if (!in_array($status, ['attribue', 'perdu', 'annule', 'infructueux', 'cloture'], true)
                && $phase !== 8) {
                throw new DomainException('Ce marché ne nécessite pas de réouverture.');
            }
            $update = $pdo->prepare(
                'UPDATE markets SET lifecycle_status = \'en_cours\', lifecycle_phase = 5,
                    status = \'en_cours\', phase = 5, official_result_qualification_id = NULL,
                    decision_date = NULL, decision_reason = NULL, winner_company_name = NULL,
                    winner_amount = NULL, decision_observations = NULL, awarded_amount = NULL
                 WHERE id = :id'
            );
            $update->execute(['id' => $marketId]);
            self::insertHistory($pdo, $marketId, 'reopen', $status, $phase, 'en_cours', 5, $userId, trim($reason));
        });
    }

    public static function findMarket(PDO $pdo, int $marketId): ?array
    {
        $q = $pdo->prepare(
            'SELECT m.*, c.name AS company_name FROM markets m
             INNER JOIN companies c ON c.id = m.company_id WHERE m.id = :id LIMIT 1'
        );
        $q->execute(['id' => $marketId]);
        return $q->fetch() ?: null;
    }

    public static function history(PDO $pdo, int $marketId): array
    {
        $q = $pdo->prepare(
            'SELECT t.*, u.display_name AS actor_name, q.result_code
             FROM market_transitions t
             LEFT JOIN users u ON u.id = t.changed_by
             LEFT JOIN market_result_qualifications q ON q.id = t.qualification_id
             WHERE t.market_id = :market_id ORDER BY t.changed_at DESC, t.id DESC'
        );
        $q->execute(['market_id' => $marketId]);
        return $q->fetchAll();
    }

    public static function qualifications(PDO $pdo, int $marketId): array
    {
        $q = $pdo->prepare(
            'SELECT q.*, qu.display_name AS qualified_by_name,
                    v.validation_decision, v.validation_reason, v.validated_at,
                    vu.display_name AS validated_by_name
             FROM market_result_qualifications q
             INNER JOIN users qu ON qu.id = q.qualified_by
             LEFT JOIN market_result_validations v ON v.qualification_id = q.id
             LEFT JOIN users vu ON vu.id = v.validated_by
             WHERE q.market_id = :market_id ORDER BY q.qualified_at DESC, q.id DESC'
        );
        $q->execute(['market_id' => $marketId]);
        return $q->fetchAll();
    }

    private static function lockMarket(PDO $pdo, int $marketId): array
    {
        $q = $pdo->prepare('SELECT id, company_id, lifecycle_status, lifecycle_phase FROM markets WHERE id = :id FOR UPDATE');
        $q->execute(['id' => $marketId]);
        $market = $q->fetch();
        if (!$market) throw new DomainException('Marché introuvable.');
        return $market;
    }

    private static function insertHistory(
        PDO $pdo, int $marketId, string $action, ?string $fromStatus, ?int $fromPhase,
        string $toStatus, int $toPhase, int $userId, string $reason, ?int $qualificationId = null
    ): void {
        if ($qualificationId === null) {
            // Conserve la création et les transitions simples compatibles avec le schéma 003.
            $q = $pdo->prepare(
                'INSERT INTO market_transitions
                    (market_id, action_type, from_status, from_phase, to_status, to_phase, changed_by, reason)
                 VALUES (:market_id, :action, :from_status, :from_phase, :to_status, :to_phase, :user_id, :reason)'
            );
            $q->execute([
                'market_id' => $marketId, 'action' => $action, 'from_status' => $fromStatus,
                'from_phase' => $fromPhase, 'to_status' => $toStatus, 'to_phase' => $toPhase,
                'user_id' => $userId, 'reason' => $reason !== '' ? $reason : null,
            ]);
            return;
        }
        $q = $pdo->prepare(
            'INSERT INTO market_transitions
                (market_id, action_type, from_status, from_phase, to_status, to_phase,
                 changed_by, reason, qualification_id)
             VALUES (:market_id, :action, :from_status, :from_phase, :to_status, :to_phase,
                     :user_id, :reason, :qualification_id)'
        );
        $q->execute([
            'market_id' => $marketId, 'action' => $action, 'from_status' => $fromStatus,
            'from_phase' => $fromPhase, 'to_status' => $toStatus, 'to_phase' => $toPhase,
            'user_id' => $userId, 'reason' => $reason !== '' ? $reason : null,
            'qualification_id' => $qualificationId,
        ]);
    }

    private static function transaction(PDO $pdo, callable $callback): mixed
    {
        $pdo->beginTransaction();
        try {
            $result = $callback();
            $pdo->commit();
            return $result;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    private static function money(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') return null;
        $raw = str_replace(',', '.', trim((string) $value));
        if (!preg_match('/^\d{1,13}(\.\d{1,2})?$/', $raw)) {
            throw new InvalidArgumentException('Un montant saisi est invalide.');
        }
        return $raw;
    }

    private static function assertDate(string $value, string $message): void
    {
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) throw new InvalidArgumentException($message);
    }
}
