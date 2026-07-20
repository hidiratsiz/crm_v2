<?php

namespace App\Models;

use App\Core\Database;

class Estimate
{
    public static function create(array $data): int
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO estimates (project_id, service_module_id, option_number, title, description, amount, status)
             VALUES (:project_id, :service_module_id, :option_number, :title, :description, :amount, :status)'
        );
        $stmt->execute([
            'project_id' => $data['project_id'],
            'service_module_id' => $data['service_module_id'] ?? null,
            'option_number' => $data['option_number'] ?? 1,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'] ?? null,
            'status' => $data['status'] ?? 'draft',
        ]);
        return (int) $db->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM estimates WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function allForProject(int $projectId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT * FROM estimates WHERE project_id = :pid AND deleted_at IS NULL ORDER BY option_number ASC'
        );
        $stmt->execute(['pid' => $projectId]);
        return $stmt->fetchAll();
    }

    public static function update(int $id, array $data): void
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'UPDATE estimates SET title = :title, description = :description, amount = :amount, service_module_id = :service_module_id
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'] ?? null,
            'service_module_id' => $data['service_module_id'] ?? null,
        ]);
    }

    public static function updateStatus(int $id, string $status): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('UPDATE estimates SET status = :status WHERE id = :id');
        $stmt->execute(['id' => $id, 'status' => $status]);
    }

    public static function softDelete(int $id): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('UPDATE estimates SET deleted_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * Next available option_number for a project (used when manually adding
     * a new estimate option so it doesn't collide with existing ones).
     */
    public static function nextOptionNumber(int $projectId): int
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT COALESCE(MAX(option_number), 0) + 1 AS next_num FROM estimates
             WHERE project_id = :pid AND deleted_at IS NULL'
        );
        $stmt->execute(['pid' => $projectId]);
        return (int) $stmt->fetch()['next_num'];
    }
}
