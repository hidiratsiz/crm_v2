<?php

namespace App\Models;

use App\Core\Database;

class Expense
{
    public static function create(array $data): int
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO expenses (job_id, category, description, amount, expense_date, created_by)
             VALUES (:job_id, :category, :description, :amount, :expense_date, :created_by)'
        );
        $stmt->execute([
            'job_id' => $data['job_id'],
            'category' => $data['category'] ?? null,
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'] ?? date('Y-m-d'),
            'created_by' => $data['created_by'] ?? null,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM expenses WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * All expenses for a job, newest first, with the spending employee's
     * name already joined in (created_by_name) so views never need a
     * separate lookup just to show "kim yapti".
     */
    public static function allForJob(int $jobId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT e.*, u.name AS created_by_name
             FROM expenses e
             LEFT JOIN users u ON u.id = e.created_by
             WHERE e.job_id = :job_id AND e.deleted_at IS NULL
             ORDER BY e.expense_date DESC, e.created_at DESC'
        );
        $stmt->execute(['job_id' => $jobId]);
        return $stmt->fetchAll();
    }

    public static function totalForJob(int $jobId): float
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT COALESCE(SUM(amount), 0) AS total FROM expenses WHERE job_id = :job_id AND deleted_at IS NULL'
        );
        $stmt->execute(['job_id' => $jobId]);
        return (float) $stmt->fetch()['total'];
    }

    public static function softDelete(int $id): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('UPDATE expenses SET deleted_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * Every expense company-wide (not scoped to one job), with the spending
     * employee, job, customer and project already joined in — powers the
     * "Finans" overview page. Optionally narrowed to an expense_date range
     * (either bound may be omitted).
     */
    public static function allWithDetails(?string $from = null, ?string $to = null): array
    {
        $db = Database::connection();
        $sql = 'SELECT e.*, u.name AS created_by_name,
                       j.id AS job_id, c.name AS customer_name, pr.name AS project_name
                FROM expenses e
                JOIN jobs j ON j.id = e.job_id
                JOIN projects pr ON pr.id = j.project_id
                JOIN customers c ON c.id = pr.customer_id
                LEFT JOIN users u ON u.id = e.created_by
                WHERE e.deleted_at IS NULL';
        $params = [];
        if ($from !== null && $from !== '') {
            $sql .= ' AND e.expense_date >= :from';
            $params['from'] = $from;
        }
        if ($to !== null && $to !== '') {
            $sql .= ' AND e.expense_date <= :to';
            $params['to'] = $to;
        }
        $sql .= ' ORDER BY e.expense_date DESC, e.created_at DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function totalAll(?string $from = null, ?string $to = null): float
    {
        $db = Database::connection();
        $sql = 'SELECT COALESCE(SUM(amount), 0) AS total FROM expenses WHERE deleted_at IS NULL';
        $params = [];
        if ($from !== null && $from !== '') {
            $sql .= ' AND expense_date >= :from';
            $params['from'] = $from;
        }
        if ($to !== null && $to !== '') {
            $sql .= ' AND expense_date <= :to';
            $params['to'] = $to;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (float) $stmt->fetch()['total'];
    }
}
