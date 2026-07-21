<?php

namespace App\Models;

use App\Core\Database;

/**
 * Site-visit / inspection appointments for a project. These exist to cover
 * the gap between "a lead came in" and "there's an accepted estimate/job":
 * you often schedule a look-at-the-deck visit before any pricing exists, so
 * these are attached to a project, never to a job.
 */
class Appointment
{
    public static function create(array $data): int
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO appointments (project_id, title, scheduled_date, scheduled_time, notes, status)
             VALUES (:project_id, :title, :scheduled_date, :scheduled_time, :notes, :status)'
        );
        $stmt->execute([
            'project_id' => $data['project_id'],
            'title' => $data['title'] ?? null,
            'scheduled_date' => $data['scheduled_date'],
            'scheduled_time' => $data['scheduled_time'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? 'scheduled',
        ]);
        return (int) $db->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM appointments WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function allForProject(int $projectId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT * FROM appointments WHERE project_id = :pid AND deleted_at IS NULL
             ORDER BY scheduled_date ASC, scheduled_time ASC'
        );
        $stmt->execute(['pid' => $projectId]);
        return $stmt->fetchAll();
    }

    /**
     * Appointments with a scheduled_date falling in the given month, with
     * customer/project names attached — powers the calendar view, alongside
     * (but visually distinct from) jobs.
     */
    public static function allForMonth(int $year, int $month): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            "SELECT a.*, c.name AS customer_name, p.name AS project_name
             FROM appointments a
             JOIN projects p ON p.id = a.project_id
             JOIN customers c ON c.id = p.customer_id
             WHERE a.deleted_at IS NULL
               AND YEAR(a.scheduled_date) = :year AND MONTH(a.scheduled_date) = :month
             ORDER BY a.scheduled_date ASC, a.scheduled_time ASC"
        );
        $stmt->execute(['year' => $year, 'month' => $month]);
        return $stmt->fetchAll();
    }

    public static function updateStatus(int $id, string $status): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('UPDATE appointments SET status = :status WHERE id = :id');
        $stmt->execute(['id' => $id, 'status' => $status]);
    }

    public static function softDelete(int $id): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('UPDATE appointments SET deleted_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
