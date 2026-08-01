<?php

namespace App\Models;

use App\Core\Database;

/**
 * Per-job labor/wage costs ("personel gideri"): what the company pays its
 * own employees for working a specific job. Kept separate from Expense
 * (material/fuel/etc. costs) so the finance views can show labor as its
 * own line and true job profitability = income - expenses - labor.
 */
class LaborCost
{
    public static function create(array $data): int
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO labor_costs (job_id, user_id, amount, work_date, note, paid_by, created_by)
             VALUES (:job_id, :user_id, :amount, :work_date, :note, :paid_by, :created_by)'
        );
        $stmt->execute([
            'job_id' => $data['job_id'],
            'user_id' => $data['user_id'] ?? null,
            'amount' => $data['amount'],
            'work_date' => $data['work_date'] ?? date('Y-m-d'),
            'note' => $data['note'] ?? null,
            'paid_by' => $data['paid_by'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM labor_costs WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * All labor costs for a job, newest first, with the employee's name
     * joined in (employee_name).
     */
    public static function allForJob(int $jobId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT lc.*, u.name AS employee_name, payer.name AS paid_by_name
             FROM labor_costs lc
             LEFT JOIN users u ON u.id = lc.user_id
             LEFT JOIN users payer ON payer.id = lc.paid_by
             WHERE lc.job_id = :job_id AND lc.deleted_at IS NULL
             ORDER BY lc.work_date DESC, lc.created_at DESC'
        );
        $stmt->execute(['job_id' => $jobId]);
        return $stmt->fetchAll();
    }

    public static function totalForJob(int $jobId): float
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT COALESCE(SUM(amount), 0) AS total FROM labor_costs WHERE job_id = :job_id AND deleted_at IS NULL'
        );
        $stmt->execute(['job_id' => $jobId]);
        return (float) $stmt->fetch()['total'];
    }

    public static function softDelete(int $id): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('UPDATE labor_costs SET deleted_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * Every labor cost company-wide with employee, job, customer and
     * project joined in — powers the Finans overview page. Optional
     * work_date range.
     */
    public static function allWithDetails(?string $from = null, ?string $to = null): array
    {
        $db = Database::connection();
        $sql = 'SELECT lc.*, u.name AS employee_name, payer.name AS paid_by_name,
                       j.id AS job_id, c.name AS customer_name, pr.name AS project_name
                FROM labor_costs lc
                JOIN jobs j ON j.id = lc.job_id
                JOIN projects pr ON pr.id = j.project_id
                JOIN customers c ON c.id = pr.customer_id
                LEFT JOIN users u ON u.id = lc.user_id
                LEFT JOIN users payer ON payer.id = lc.paid_by
                WHERE lc.deleted_at IS NULL';
        $params = [];
        if ($from !== null && $from !== '') {
            $sql .= ' AND lc.work_date >= :from';
            $params['from'] = $from;
        }
        if ($to !== null && $to !== '') {
            $sql .= ' AND lc.work_date <= :to';
            $params['to'] = $to;
        }
        $sql .= ' ORDER BY lc.work_date DESC, lc.created_at DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
