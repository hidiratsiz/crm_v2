<?php

namespace App\Models;

use App\Core\Database;

class Job
{
    public static function create(array $data): int
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO jobs (project_id, estimate_id, status, start_date, notes)
             VALUES (:project_id, :estimate_id, :status, :start_date, :notes)'
        );
        $stmt->execute([
            'project_id' => $data['project_id'],
            'estimate_id' => $data['estimate_id'] ?? null,
            'status' => $data['status'] ?? 'pending_schedule',
            'start_date' => $data['start_date'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM jobs WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Finds the job that was created from a given estimate, if any — used to
     * prevent converting the same accepted estimate into a job twice.
     */
    public static function findByEstimateId(int $estimateId): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM jobs WHERE estimate_id = :eid AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['eid' => $estimateId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function allForProject(int $projectId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM jobs WHERE project_id = :pid AND deleted_at IS NULL ORDER BY created_at DESC');
        $stmt->execute(['pid' => $projectId]);
        return $stmt->fetchAll();
    }

    /**
     * All jobs with customer/project name and a comma-joined list of
     * assigned employee names — powers the global "Isler" list page so you
     * can see "kimde hangi is var" (who has which job) at a glance.
     */
    public static function allWithDetails(int $limit = 0): array
    {
        $db = Database::connection();
        $sql = "SELECT j.*, c.id AS customer_id, c.name AS customer_name, p.name AS project_name,
                    GROUP_CONCAT(u.name SEPARATOR ', ') AS employee_names
             FROM jobs j
             JOIN projects p ON p.id = j.project_id
             JOIN customers c ON c.id = p.customer_id
             LEFT JOIN job_employees je ON je.job_id = j.id
             LEFT JOIN users u ON u.id = je.user_id
             WHERE j.deleted_at IS NULL
             GROUP BY j.id
             ORDER BY j.created_at DESC";
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit;
        }
        return $db->query($sql)->fetchAll();
    }

    /**
     * Jobs assigned to a specific employee — used so an Employee-role user
     * can see their own jobs even without permission to see everyone's.
     */
    public static function allForEmployee(int $userId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            "SELECT j.*, c.name AS customer_name, p.name AS project_name
             FROM jobs j
             JOIN projects p ON p.id = j.project_id
             JOIN customers c ON c.id = p.customer_id
             JOIN job_employees je ON je.job_id = j.id
             WHERE j.deleted_at IS NULL AND je.user_id = :uid
             ORDER BY j.created_at DESC"
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * A single job by ID with customer/project name joined in — same row
     * shape as findByCustomerNameLike(), used when a Quick Capture command
     * targets a job directly by number ("3 nolu ise gider ekle") instead
     * of by customer name.
     */
    public static function findWithDetails(int $id): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT j.*, c.id AS customer_id, c.name AS customer_name, p.name AS project_name
             FROM jobs j
             JOIN projects p ON p.id = j.project_id
             JOIN customers c ON c.id = p.customer_id
             WHERE j.id = :id AND j.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Finds jobs belonging to customers whose name matches (loosely, via
     * LIKE), most recent first. Used to resolve voice/text commands like
     * "Jane'in isine Alex'i ata" to an actual job record.
     *
     * Returns ALL matches so the caller can tell "not found" (empty),
     * "unique" (one distinct customer — safe to use the newest job), and
     * "ambiguous" (more than one distinct customer matched the name) apart
     * — never silently guessing which Jane was meant.
     */
    public static function findByCustomerNameLike(string $customerName): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT j.*, c.id AS customer_id, c.name AS customer_name, p.name AS project_name
             FROM jobs j
             JOIN projects p ON p.id = j.project_id
             JOIN customers c ON c.id = p.customer_id
             WHERE j.deleted_at IS NULL AND c.name LIKE :name
             ORDER BY j.created_at DESC'
        );
        $stmt->execute(['name' => '%' . $customerName . '%']);
        return $stmt->fetchAll();
    }

    public static function updateStatus(int $id, string $status): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('UPDATE jobs SET status = :status WHERE id = :id');
        $stmt->execute(['id' => $id, 'status' => $status]);
    }

    public static function updateStartDate(int $id, ?string $startDate, ?string $startTime = null, ?float $durationHours = null): void
    {
        $db = Database::connection();
        // Setting a start date automatically moves a pending job to "scheduled"
        $stmt = $db->prepare(
            "UPDATE jobs SET start_date = :start_date, start_time = :start_time, duration_hours = :duration_hours,
             status = IF(status = 'pending_schedule' AND :start_date2 IS NOT NULL, 'scheduled', status)
             WHERE id = :id"
        );
        $stmt->execute([
            'id' => $id,
            'start_date' => $startDate,
            'start_time' => $startTime,
            'duration_hours' => $durationHours,
            'start_date2' => $startDate,
        ]);
    }

    /**
     * Jobs with a start_date falling in the given month, with customer/
     * project names and assigned employees — powers the calendar view.
     */
    public static function allForMonth(int $year, int $month): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            "SELECT j.*, c.name AS customer_name, p.name AS project_name,
                    GROUP_CONCAT(u.name SEPARATOR ', ') AS employee_names
             FROM jobs j
             JOIN projects p ON p.id = j.project_id
             JOIN customers c ON c.id = p.customer_id
             LEFT JOIN job_employees je ON je.job_id = j.id
             LEFT JOIN users u ON u.id = je.user_id
             WHERE j.deleted_at IS NULL
               AND YEAR(j.start_date) = :year AND MONTH(j.start_date) = :month
             GROUP BY j.id
             ORDER BY j.start_date ASC, j.start_time ASC"
        );
        $stmt->execute(['year' => $year, 'month' => $month]);
        return $stmt->fetchAll();
    }

    // ---- Employee assignment ----

    public static function assignEmployee(int $jobId, int $userId): void
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT IGNORE INTO job_employees (job_id, user_id) VALUES (:job_id, :user_id)'
        );
        $stmt->execute(['job_id' => $jobId, 'user_id' => $userId]);
    }

    public static function unassignEmployee(int $jobId, int $userId): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('DELETE FROM job_employees WHERE job_id = :job_id AND user_id = :user_id');
        $stmt->execute(['job_id' => $jobId, 'user_id' => $userId]);
    }

    public static function markEmployeeNotified(int $jobId, int $userId): void
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'UPDATE job_employees SET notified_at = NOW() WHERE job_id = :job_id AND user_id = :user_id'
        );
        $stmt->execute(['job_id' => $jobId, 'user_id' => $userId]);
    }

    public static function employeesForJob(int $jobId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT u.id, u.name, u.email, je.assigned_at, je.notified_at
             FROM job_employees je
             JOIN users u ON u.id = je.user_id
             WHERE je.job_id = :job_id
             ORDER BY je.assigned_at ASC'
        );
        $stmt->execute(['job_id' => $jobId]);
        return $stmt->fetchAll();
    }

    /**
     * Maps job_id => contract amount (the linked estimate's amount, or null
     * if the job has no linked estimate/amount) for a set of job ids.
     * Powers the company-wide Finans page's per-job balance column without
     * needing a separate Estimate::find() call per job.
     */
    public static function contractAmountsForJobs(array $jobIds): array
    {
        $jobIds = array_values(array_unique(array_map('intval', $jobIds)));
        if (empty($jobIds)) {
            return [];
        }

        $db = Database::connection();
        $placeholders = implode(',', array_fill(0, count($jobIds), '?'));
        $stmt = $db->prepare(
            "SELECT j.id AS job_id, e.amount
             FROM jobs j
             LEFT JOIN estimates e ON e.id = j.estimate_id
             WHERE j.id IN ({$placeholders})"
        );
        $stmt->execute($jobIds);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(int) $row['job_id']] = $row['amount'] !== null ? (float) $row['amount'] : null;
        }
        return $result;
    }
}
