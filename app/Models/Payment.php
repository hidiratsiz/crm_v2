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

    /**
     * Every payment company-wide (not scoped to one job), with receiving
     * employee, job, customer and project already joined in — powers the
     * "Finans" overview page. Optionally narrowed to a paid_at date range
     * (either bound may be omitted).
     */
    public static function allWithDetails(?string $from = null, ?string $to = null): array
    {
        $db = Database::connection();
        $sql = 'SELECT p.*, u.name AS received_by_name,
                       j.id AS job_id, c.name AS customer_name, pr.name AS project_name
                FROM payments p
                JOIN jobs j ON j.id = p.job_id
                JOIN projects pr ON pr.id = j.project_id
                JOIN customers c ON c.id = pr.customer_id
                LEFT JOIN users u ON u.id = p.received_by
                WHERE p.deleted_at IS NULL';
        $params = [];
        if ($from !== null && $from !== '') {
            $sql .= ' AND p.paid_at >= :from';
            $params['from'] = $from;
        }
        if ($to !== null && $to !== '') {
            $sql .= ' AND p.paid_at <= :to';
            $params['to'] = $to;
        }
        $sql .= ' ORDER BY p.paid_at DESC, p.created_at DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function totalAll(?string $from = null, ?string $to = null): float
    {
        $db = Database::connection();
        $sql = 'SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE deleted_at IS NULL';
        $params = [];
        if ($from !== null && $from !== '') {
            $sql .= ' AND paid_at >= :from';
            $params['from'] = $from;
        }
        if ($to !== null && $to !== '') {
            $sql .= ' AND paid_at <= :to';
            $params['to'] = $to;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (float) $stmt->fetch()['total'];
    }
}
