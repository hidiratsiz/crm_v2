<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\View;
use App\Models\Expense;
use App\Models\Job;
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

        $totalIncome = array_sum(array_map(fn ($p) => (float) $p['amount'], $payments));
        $totalExpense = array_sum(array_map(fn ($e) => (float) $e['amount'], $expenses));

        echo View::renderWithLayout('finance/index', [
            'from' => $from,
            'to' => $to,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'net' => $totalIncome - $totalExpense,
            'employeeFinance' => $this->buildEmployeeFinance($payments, $expenses),
            'jobSummary' => $this->buildJobSummary($payments, $expenses),
            'transactions' => $this->buildTransactions($payments, $expenses),
        ]);
    }

    /**
     * Company-wide "kimde ne kadar var": nets every employee's total
     * collected payments against their total logged expenses, across ALL
     * jobs (within the date filter) — not just one job.
     */
    private function buildEmployeeFinance(array $payments, array $expenses): array
    {
        $byEmployee = [];

        foreach ($payments as $payment) {
            $id = $payment['received_by'] ?? null;
            $name = $payment['received_by_name'] ?? 'Bilinmiyor';
            $key = $id ?? 'unknown';
            if (!isset($byEmployee[$key])) {
                $byEmployee[$key] = ['name' => $name, 'received' => 0.0, 'spent' => 0.0];
            }
            $byEmployee[$key]['received'] += (float) $payment['amount'];
        }

        foreach ($expenses as $expense) {
            $id = $expense['created_by'] ?? null;
            $name = $expense['created_by_name'] ?? 'Bilinmiyor';
            $key = $id ?? 'unknown';
            if (!isset($byEmployee[$key])) {
                $byEmployee[$key] = ['name' => $name, 'received' => 0.0, 'spent' => 0.0];
            }
            $byEmployee[$key]['spent'] += (float) $expense['amount'];
        }

        foreach ($byEmployee as &$row) {
            $row['net'] = $row['received'] - $row['spent'];
        }
        unset($row);

        return array_values($byEmployee);
    }

    /**
     * One row per job that has at least one payment/expense in range:
     * customer/project name, income, expense, contract amount, balance.
     */
    private function buildJobSummary(array $payments, array $expenses): array
    {
        $byJob = [];

        foreach ($payments as $payment) {
            $jobId = (int) $payment['job_id'];
            if (!isset($byJob[$jobId])) {
                $byJob[$jobId] = [
                    'job_id' => $jobId,
                    'customer_name' => $payment['customer_name'],
                    'project_name' => $payment['project_name'],
                    'income' => 0.0,
                    'expense' => 0.0,
                ];
            }
            $byJob[$jobId]['income'] += (float) $payment['amount'];
        }

        foreach ($expenses as $expense) {
            $jobId = (int) $expense['job_id'];
            if (!isset($byJob[$jobId])) {
                $byJob[$jobId] = [
                    'job_id' => $jobId,
                    'customer_name' => $expense['customer_name'],
                    'project_name' => $expense['project_name'],
                    'income' => 0.0,
                    'expense' => 0.0,
                ];
            }
            $byJob[$jobId]['expense'] += (float) $expense['amount'];
        }

        $contractAmounts = Job::contractAmountsForJobs(array_keys($byJob));

        foreach ($byJob as $jobId => &$row) {
            $row['contract_amount'] = $contractAmounts[$jobId] ?? null;
            $row['balance'] = $row['contract_amount'] !== null ? ($row['contract_amount'] - $row['income']) : null;
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
    private function buildTransactions(array $payments, array $expenses): array
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

        usort($transactions, fn ($a, $b) => strcmp((string) $b['date'], (string) $a['date']));

        return $transactions;
    }

    private function forbidden(): void
    {
        http_response_code(403);
        echo View::renderWithLayout('errors/403');
    }
}
