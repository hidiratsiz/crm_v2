<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\View;
use App\Models\Expense;
use App\Models\Job;
use App\Models\LaborCost;
use App\Models\Payment;

/**
 * Company-wide "Finans" overview: every payment and expense across every
 * job in one place, with an optional date-range filter — the aggregate
 * version of the per-job finance card on jobs/show.
 */
class FinanceController extends Controller
{
    public function index(): void
    {
        if (!Auth::can('customers.view')) {
            $this->forbidden();
            return;
        }

        $from = trim((string) $this->input('from'));
        $to = trim((string) $this->input('to'));
        $from = $from !== '' ? $from : null;
        $to = $to !== '' ? $to : null;

        $payments = Payment::allWithDetails($from, $to);
        $expenses = Expense::allWithDetails($from, $to);
        $laborCosts = LaborCost::allWithDetails($from, $to);

        $totalIncome = array_sum(array_map(fn ($p) => (float) $p['amount'], $payments));
        $totalExpense = array_sum(array_map(fn ($e) => (float) $e['amount'], $expenses));
        $totalLabor = array_sum(array_map(fn ($l) => (float) $l['amount'], $laborCosts));

        $employeeFinance = $this->buildEmployeeFinance($payments, $expenses, $laborCosts);

        echo View::renderWithLayout('finance/index', [
            'from' => $from,
            'to' => $to,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'totalLabor' => $totalLabor,
            'net' => $totalIncome - $totalExpense - $totalLabor,
            'employeeFinance' => $employeeFinance,
            'settlements' => $this->buildSettlements($employeeFinance),
            'jobSummary' => $this->buildJobSummary($payments, $expenses, $laborCosts),
            'transactions' => $this->buildTransactions($payments, $expenses, $laborCosts),
        ]);
    }

    /**
     * Company-wide "kimde ne kadar var": nets every employee's total
     * collected payments against their total logged expenses AND the labor
     * payments they made to others, across ALL jobs (within the date
     * filter) — not just one job.
     */
    private function buildEmployeeFinance(array $payments, array $expenses, array $laborCosts = []): array
    {
        $byEmployee = [];

        $ensureRow = static function (array &$byEmployee, $id, string $name): string {
            $key = (string) ($id ?? 'unknown');
            if (!isset($byEmployee[$key])) {
                $byEmployee[$key] = ['name' => $name, 'received' => 0.0, 'spent' => 0.0, 'labor_paid' => 0.0, 'jobs' => []];
            }
            return $key;
        };

        // Per-job breakdown under each employee — powers the expandable
        // "hangi projeden kac para" detail rows on the Finans page.
        $ensureJob = static function (array &$byEmployee, string $key, array $item): int {
            $jobId = (int) $item['job_id'];
            if (!isset($byEmployee[$key]['jobs'][$jobId])) {
                $byEmployee[$key]['jobs'][$jobId] = [
                    'job_id' => $jobId,
                    'customer_name' => $item['customer_name'] ?? '-',
                    'project_name' => $item['project_name'] ?? '',
                    'received' => 0.0,
                    'spent' => 0.0,
                    'labor_paid' => 0.0,
                ];
            }
            return $jobId;
        };

        foreach ($payments as $payment) {
            $key = $ensureRow($byEmployee, $payment['received_by'] ?? null, $payment['received_by_name'] ?? 'Bilinmiyor');
            $byEmployee[$key]['received'] += (float) $payment['amount'];
            $jobId = $ensureJob($byEmployee, $key, $payment);
            $byEmployee[$key]['jobs'][$jobId]['received'] += (float) $payment['amount'];
        }

        foreach ($expenses as $expense) {
            $key = $ensureRow($byEmployee, $expense['created_by'] ?? null, $expense['created_by_name'] ?? 'Bilinmiyor');
            $byEmployee[$key]['spent'] += (float) $expense['amount'];
            $jobId = $ensureJob($byEmployee, $key, $expense);
            $byEmployee[$key]['jobs'][$jobId]['spent'] += (float) $expense['amount'];
        }

        // Labor payments count against the PAYER's held cash: if Alex
        // collected $1200 from the customer and paid a crew member $300 in
        // cash, Alex is now holding $900, not $1200.
        foreach ($laborCosts as $laborCost) {
            $key = $ensureRow($byEmployee, $laborCost['paid_by'] ?? null, $laborCost['paid_by_name'] ?? 'Bilinmiyor');
            $byEmployee[$key]['labor_paid'] += (float) $laborCost['amount'];
            $jobId = $ensureJob($byEmployee, $key, $laborCost);
            $byEmployee[$key]['jobs'][$jobId]['labor_paid'] += (float) $laborCost['amount'];
        }

        foreach ($byEmployee as &$row) {
            $row['net'] = $row['received'] - $row['spent'] - $row['labor_paid'];
            foreach ($row['jobs'] as &$jobRow) {
                $jobRow['net'] = $jobRow['received'] - $jobRow['spent'] - $jobRow['labor_paid'];
            }
            unset($jobRow);
            $row['jobs'] = array_values($row['jobs']);
        }
        unset($row);

        return array_values($byEmployee);
    }

    /**
     * "Kim kime borclu": nets the per-employee balances against each other.
     * Positive net = that person is holding company money; negative net =
     * the company owes them (they spent out of pocket). Greedy matching
     * pairs holders with creditors ("Alex -> System Admin: $408.73"), and
     * whatever remains is settled against the company cash box ("Kasa").
     */
    private function buildSettlements(array $employeeFinance): array
    {
        $holders = [];   // net > 0: owes money (to creditors/company)
        $creditors = []; // net < 0: is owed money
        foreach ($employeeFinance as $row) {
            $net = round((float) $row['net'], 2);
            if ($net > 0.009) {
                $holders[] = ['name' => $row['name'], 'amount' => $net];
            } elseif ($net < -0.009) {
                $creditors[] = ['name' => $row['name'], 'amount' => -$net];
            }
        }

        // Biggest balances first so the settlement list stays short.
        usort($holders, fn ($a, $b) => $b['amount'] <=> $a['amount']);
        usort($creditors, fn ($a, $b) => $b['amount'] <=> $a['amount']);

        $settlements = [];
        $h = 0;
        $c = 0;
        while ($h < count($holders) && $c < count($creditors)) {
            $transfer = min($holders[$h]['amount'], $creditors[$c]['amount']);
            $settlements[] = ['from' => $holders[$h]['name'], 'to' => $creditors[$c]['name'], 'amount' => $transfer];
            $holders[$h]['amount'] -= $transfer;
            $creditors[$c]['amount'] -= $transfer;
            if ($holders[$h]['amount'] < 0.009) {
                $h++;
            }
            if ($creditors[$c]['amount'] < 0.009) {
                $c++;
            }
        }
        for (; $h < count($holders); $h++) {
            if ($holders[$h]['amount'] > 0.009) {
                $settlements[] = ['from' => $holders[$h]['name'], 'to' => 'Sirket Kasasi', 'amount' => $holders[$h]['amount']];
            }
        }
        for (; $c < count($creditors); $c++) {
            if ($creditors[$c]['amount'] > 0.009) {
                $settlements[] = ['from' => 'Sirket Kasasi', 'to' => $creditors[$c]['name'], 'amount' => $creditors[$c]['amount']];
            }
        }

        return $settlements;
    }

    /**
     * One row per job that has at least one payment/expense in range:
     * customer/project name, income, expense, contract amount, balance.
     */
    private function buildJobSummary(array $payments, array $expenses, array $laborCosts): array
    {
        $byJob = [];

        $ensureRow = static function (array &$byJob, array $item): int {
            $jobId = (int) $item['job_id'];
            if (!isset($byJob[$jobId])) {
                $byJob[$jobId] = [
                    'job_id' => $jobId,
                    'customer_name' => $item['customer_name'],
                    'project_name' => $item['project_name'],
                    'income' => 0.0,
                    'expense' => 0.0,
                    'labor' => 0.0,
                ];
            }
            return $jobId;
        };

        foreach ($payments as $payment) {
            $jobId = $ensureRow($byJob, $payment);
            $byJob[$jobId]['income'] += (float) $payment['amount'];
        }

        foreach ($expenses as $expense) {
            $jobId = $ensureRow($byJob, $expense);
            $byJob[$jobId]['expense'] += (float) $expense['amount'];
        }

        foreach ($laborCosts as $laborCost) {
            $jobId = $ensureRow($byJob, $laborCost);
            $byJob[$jobId]['labor'] += (float) $laborCost['amount'];
        }

        $contractAmounts = Job::contractAmountsForJobs(array_keys($byJob));

        foreach ($byJob as $jobId => &$row) {
            $row['contract_amount'] = $contractAmounts[$jobId] ?? null;
            $row['balance'] = $row['contract_amount'] !== null ? ($row['contract_amount'] - $row['income']) : null;
            $row['net'] = $row['income'] - $row['expense'] - $row['labor'];
        }
        unset($row);

        // Highest income first — the jobs most worth a manager's attention.
        usort($byJob, fn ($a, $b) => $b['income'] <=> $a['income']);

        return array_values($byJob);
    }

    /**
     * Payments and expenses merged into one chronological list (newest
     * first) so the whole company's cash movement can be scanned in order,
     * not as two separate tables.
     */
    private function buildTransactions(array $payments, array $expenses, array $laborCosts): array
    {
        $paymentMethodLabels = ['cash' => 'Nakit', 'card' => 'Kredi Karti', 'bank_transfer' => 'Havale/EFT', 'check' => 'Cek'];
        $transactions = [];

        foreach ($payments as $payment) {
            $transactions[] = [
                'type' => 'income',
                'date' => $payment['paid_at'],
                'amount' => (float) $payment['amount'],
                'job_id' => $payment['job_id'],
                'customer_name' => $payment['customer_name'],
                'project_name' => $payment['project_name'],
                'who' => $payment['received_by_name'],
                'label' => $paymentMethodLabels[$payment['method']] ?? $payment['method'],
                'note' => $payment['note'],
            ];
        }

        foreach ($expenses as $expense) {
            $transactions[] = [
                'type' => 'expense',
                'date' => $expense['expense_date'],
                'amount' => (float) $expense['amount'],
                'job_id' => $expense['job_id'],
                'customer_name' => $expense['customer_name'],
                'project_name' => $expense['project_name'],
                'who' => $expense['created_by_name'],
                'label' => $expense['category'] ?: 'Gider',
                'note' => $expense['description'],
            ];
        }

        foreach ($laborCosts as $laborCost) {
            $transactions[] = [
                'type' => 'labor',
                'date' => $laborCost['work_date'],
                'amount' => (float) $laborCost['amount'],
                'job_id' => $laborCost['job_id'],
                'customer_name' => $laborCost['customer_name'],
                'project_name' => $laborCost['project_name'],
                'who' => $laborCost['paid_by_name'],
                'label' => 'Personel: ' . ($laborCost['employee_name'] ?? '-'),
                'note' => $laborCost['note'],
            ];
        }

        usort($transactions, fn ($a, $b) => strcmp((string) $b['date'], (string) $a['date']));

        return $transactions;
    }

    private function forbidden(): void
    {
        http_response_code(403);
        echo View::renderWithLayout('errors/403');
    }
}
