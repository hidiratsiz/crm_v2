<?php

namespace App\Models;

use App\Core\Database;

/**
 * Payments received from the customer against a job's contract amount
 * ("teklif tutari" — the accepted estimate's amount). Together with
 * Expense, this is the "Proje Finansmani" (project finance) picture: how
 * much has come in, how much has gone out, who handled each, and what's
 * left.
 */
class Payment
{
    public static function create(array $data): int
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO payments (job_id, amount, method, note, received_by, paid_at)
             VALUES (:job_id, :amount, :method, :note, :received_by, :paid_at)'
        );
        $stmt->execute([
            'job_id' => $data['job_id'],
            'amount' => $data['amount'],
            'method' => $data['method'] ?? 'cash',
            'note' => $data['note'] ?? null,
            'received_by' => $data['received_by'] ?? null,
            'paid_at' => $data['paid_at'] ?? date('Y-m-d'),
        ]);
        return (int) $db->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM payments WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * All payments for a job, newest first, with the receiving employee's
     * name already joined in (received_by_name) so views never need a
     * separate lookup just to show "kim aldi".
     */
    public static function allForJob(int $jobId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT p.*, u.name AS received_by_name
             FROM payments p
             LEFT JOIN users u ON u.id = p.received_by
             WHERE p.job_id = :job_id AND p.deleted_at IS NULL
             ORDER BY p.paid_at DESC, p.created_at DESC'
        );
        $stmt->execute(['job_id' => $jobId]);
        return $stmt->fetchAll();
    }

    public static function totalForJob(int $jobId): float
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE job_id = :job_id AND deleted_at IS NULL'
        );
        $stmt->execute(['job_id' => $jobId]);
        return (float) $stmt->fetch()['total'];
    }

    public static function softDelete(int $id): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('UPDATE payments SET deleted_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
