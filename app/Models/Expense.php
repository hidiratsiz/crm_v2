<?php

namespace App\Models;

use App\Core\Database;

class Expense
{
    public static function create(array $data): int
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO expenses (job_id, category, description, amount, created_by)
             VALUES (:job_id, :category, :description, :amount, :created_by)'
        );
        $stmt->execute([
            'job_id' => $data['job_id'],
            'category' => $data['category'] ?? null,
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'],
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
             ORDER BY e.created_at DESC'
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
}
