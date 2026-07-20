<?php

namespace App\Models;

use App\Core\Database;

class ChecklistItem
{
    public static function create(int $jobId, string $description): int
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM job_checklist_items WHERE job_id = :job_id'
        );
        $stmt->execute(['job_id' => $jobId]);
        $nextOrder = (int) $stmt->fetch()['next_order'];

        $stmt = $db->prepare(
            'INSERT INTO job_checklist_items (job_id, description, sort_order) VALUES (:job_id, :description, :sort_order)'
        );
        $stmt->execute(['job_id' => $jobId, 'description' => $description, 'sort_order' => $nextOrder]);
        return (int) $db->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM job_checklist_items WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function allForJob(int $jobId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT * FROM job_checklist_items WHERE job_id = :job_id ORDER BY sort_order ASC'
        );
        $stmt->execute(['job_id' => $jobId]);
        return $stmt->fetchAll();
    }

    public static function toggle(int $id): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('UPDATE job_checklist_items SET is_done = NOT is_done WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function delete(int $id): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('DELETE FROM job_checklist_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
