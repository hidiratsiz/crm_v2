<?php

namespace App\Models;

use App\Core\Database;

class Project
{
    public static function create(array $data): int
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO projects (customer_id, name, status, notes, raw_input, ai_summary)
             VALUES (:customer_id, :name, :status, :notes, :raw_input, :ai_summary)'
        );
        $stmt->execute([
            'customer_id' => $data['customer_id'],
            'name' => $data['name'],
            'status' => $data['status'] ?? 'lead',
            'notes' => $data['notes'] ?? null,
            'raw_input' => $data['raw_input'] ?? null,
            'ai_summary' => $data['ai_summary'] ?? null,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM projects WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function allForCustomer(int $customerId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT * FROM projects WHERE customer_id = :cid AND deleted_at IS NULL ORDER BY created_at DESC'
        );
        $stmt->execute(['cid' => $customerId]);
        return $stmt->fetchAll();
    }

    /**
     * All projects with their customer's name attached, newest first —
     * used by the global "Projeler" list page and the dashboard's
     * "recent activity" widget.
     */
    public static function allWithCustomer(int $limit = 0): array
    {
        $db = Database::connection();
        $sql = 'SELECT p.*, c.name AS customer_name
                FROM projects p
                JOIN customers c ON c.id = p.customer_id
                WHERE p.deleted_at IS NULL
                ORDER BY p.created_at DESC';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit;
        }
        return $db->query($sql)->fetchAll();
    }
}
