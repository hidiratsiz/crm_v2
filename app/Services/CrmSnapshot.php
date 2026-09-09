<?php

namespace App\Services;

use App\Core\Database;

/**
 * Builds a compact JSON-ready snapshot of the whole CRM state (customers,
 * projects, estimates, jobs with finance totals, appointments, employee
 * balances) for the AI assistant to answer free-form questions from.
 *
 * Deliberately capped (recent-first limits) so the prompt stays small even
 * as data grows; totals/aggregates are precomputed here so the model reads
 * numbers instead of trying to do arithmetic across rows.
 */
class CrmSnapshot
{
    private const MAX_CUSTOMERS = 100;
    private const MAX_TRANSACTIONS = 60;

    public static function build(): array
    {
        $db = Database::connection();

        $customers = $db->query(
            'SELECT id, name, phone, email, address FROM customers
             WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT ' . self::MAX_CUSTOMERS
        )->fetchAll();

        $projects = $db->query(
            'SELECT p.id, p.customer_id, c.name AS customer_name, p.name, p.status
             FROM projects p JOIN customers c ON c.id = p.customer_id
             WHERE p.deleted_at IS NULL ORDER BY p.created_at DESC LIMIT ' . self::MAX_CUSTOMERS
        )->fetchAll();

        $estimates = $db->query(
            'SELECT e.id, e.project_id, e.title, e.amount, e.status
             FROM estimates e WHERE e.deleted_at IS NULL ORDER BY e.created_at DESC LIMIT ' . self::MAX_CUSTOMERS
        )->fetchAll();

        // Jobs with per-job finance totals and assigned employees in one pass
        $jobs = $db->query(
            "SELECT j.id, j.project_id, c.name AS customer_name, p.name AS project_name,
                    j.status, j.start_date, j.end_date, j.start_time, j.end_time,
                    e.amount AS contract_amount,
                    (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE job_id = j.id AND deleted_at IS NULL) AS payment_total,
                    (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE job_id = j.id AND deleted_at IS NULL) AS expense_total,
                    (SELECT COALESCE(SUM(amount), 0) FROM labor_costs WHERE job_id = j.id AND deleted_at IS NULL) AS labor_total,
                    (SELECT GROUP_CONCAT(u.name SEPARATOR ', ') FROM job_employees je JOIN users u ON u.id = je.user_id WHERE je.job_id = j.id) AS employees
             FROM jobs j
             JOIN projects p ON p.id = j.project_id
             JOIN customers c ON c.id = p.customer_id
             LEFT JOIN estimates e ON e.id = j.estimate_id
             WHERE j.deleted_at IS NULL
             ORDER BY j.created_at DESC LIMIT " . self::MAX_CUSTOMERS
        )->fetchAll();

        foreach ($jobs as &$job) {
            $job['net_profit'] = (float) $job['payment_total'] - (float) $job['expense_total'] - (float) $job['labor_total'];
            $job['balance_due'] = $job['contract_amount'] !== null
                ? (float) $job['contract_amount'] - (float) $job['payment_total']
                : null;
        }
        unset($job);

        $appointments = $db->query(
            "SELECT a.scheduled_date, a.scheduled_time, a.status, a.notes, c.name AS customer_name
             FROM appointments a
             JOIN projects p ON p.id = a.project_id
             JOIN customers c ON c.id = p.customer_id
             WHERE a.deleted_at IS NULL
             ORDER BY a.scheduled_date DESC LIMIT 30"
        )->fetchAll();

        // Employee cash balances (kimde ne kadar var) — payments received
        // minus expenses made minus labor they paid out.
        $employeeBalances = $db->query(
            "SELECT u.name,
                    COALESCE((SELECT SUM(amount) FROM payments WHERE received_by = u.id AND deleted_at IS NULL), 0) AS received,
                    COALESCE((SELECT SUM(amount) FROM expenses WHERE created_by = u.id AND deleted_at IS NULL), 0)
                    + COALESCE((SELECT SUM(amount) FROM labor_costs WHERE paid_by = u.id AND deleted_at IS NULL), 0) AS spent
             FROM users u WHERE u.deleted_at IS NULL AND u.is_active = 1"
        )->fetchAll();

        foreach ($employeeBalances as &$balance) {
            $balance['holding'] = (float) $balance['received'] - (float) $balance['spent'];
        }
        unset($balance);

        // Recent cash movements, all three kinds interleaved
        $transactions = $db->query(
            "(SELECT 'odeme_gelir' AS type, p.paid_at AS date, p.amount, c.name AS customer_name, u.name AS who, p.method AS detail
              FROM payments p
              JOIN jobs j ON j.id = p.job_id JOIN projects pr ON pr.id = j.project_id JOIN customers c ON c.id = pr.customer_id
              LEFT JOIN users u ON u.id = p.received_by
              WHERE p.deleted_at IS NULL)
             UNION ALL
             (SELECT 'gider' AS type, e.expense_date AS date, e.amount, c.name AS customer_name, u.name AS who, CONCAT(COALESCE(e.category,''), ' ', COALESCE(e.description,'')) AS detail
              FROM expenses e
              JOIN jobs j ON j.id = e.job_id JOIN projects pr ON pr.id = j.project_id JOIN customers c ON c.id = pr.customer_id
              LEFT JOIN users u ON u.id = e.created_by
              WHERE e.deleted_at IS NULL)
             UNION ALL
             (SELECT 'personel_gideri' AS type, lc.work_date AS date, lc.amount, c.name AS customer_name, u.name AS who, CONCAT('calisan: ', COALESCE(emp.name,'-')) AS detail
              FROM labor_costs lc
              JOIN jobs j ON j.id = lc.job_id JOIN projects pr ON pr.id = j.project_id JOIN customers c ON c.id = pr.customer_id
              LEFT JOIN users u ON u.id = lc.paid_by
              LEFT JOIN users emp ON emp.id = lc.user_id
              WHERE lc.deleted_at IS NULL)
             ORDER BY date DESC LIMIT " . self::MAX_TRANSACTIONS
        )->fetchAll();

        $totals = [
            'toplam_gelir' => array_sum(array_map(fn ($j) => (float) $j['payment_total'], $jobs)),
            'toplam_gider' => array_sum(array_map(fn ($j) => (float) $j['expense_total'], $jobs)),
            'toplam_personel_gideri' => array_sum(array_map(fn ($j) => (float) $j['labor_total'], $jobs)),
        ];
        $totals['net_kar'] = $totals['toplam_gelir'] - $totals['toplam_gider'] - $totals['toplam_personel_gideri'];

        return [
            'sirket_ozeti' => $totals,
            'musteriler' => $customers,
            'projeler' => $projects,
            'teklifler' => $estimates,
            'isler' => $jobs,
            'randevular' => $appointments,
            'calisan_kasa_bakiyeleri' => $employeeBalances,
            'son_islemler' => $transactions,
        ];
    }
}
