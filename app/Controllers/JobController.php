<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\View;
use App\Models\ChecklistItem;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\Expense;
use App\Models\Job;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use App\Services\JobNotifier;

class JobController extends Controller
{
    private const VALID_STATUSES = ['pending_schedule', 'scheduled', 'in_progress', 'completed', 'cancelled'];
    private const VALID_PAYMENT_METHODS = ['cash', 'card', 'bank_transfer', 'check'];

    public function index(): void
    {
        // Roles with broad visibility (Admin, Office Staff, Estimator, Read-only)
        // see every job. An Employee-role account (which only has
        // dashboard.view) still gets this page, but scoped to just the
        // jobs assigned to them — so "kimde hangi is var" works for
        // everyone, not just managers.
        if (Auth::can('customers.view')) {
            $jobs = Job::allWithDetails();
            $scopeLabel = 'Tum Isler';
        } else {
            $jobs = Job::allForEmployee((int) Auth::id());
            $scopeLabel = 'Benim Islerim';
        }

        echo View::renderWithLayout('jobs/index', ['jobs' => $jobs, 'scopeLabel' => $scopeLabel]);
    }

    public function show(): void
    {
        if (!Auth::can('customers.view')) {
            $this->forbidden();
            return;
        }

        $job = $this->loadJobOr404();
        if (!$job) {
            return;
        }

        $project = Project::find((int) $job['project_id']);
        $customer = $project ? Customer::find((int) $project['customer_id']) : null;
        $estimate = !empty($job['estimate_id']) ? Estimate::find((int) $job['estimate_id']) : null;

        $expenses = Expense::allForJob($job['id']);
        $payments = Payment::allForJob($job['id']);
        $expenseTotal = Expense::totalForJob($job['id']);
        $paymentTotal = Payment::totalForJob($job['id']);
        $contractAmount = $estimate['amount'] ?? null;

        echo View::renderWithLayout('jobs/show', [
            'job' => $job,
            'project' => $project,
            'customer' => $customer,
            'assignedEmployees' => Job::employeesForJob($job['id']),
            'availableEmployees' => User::allActive(),
            'expenses' => $expenses,
            'expenseTotal' => $expenseTotal,
            'payments' => $payments,
            'paymentTotal' => $paymentTotal,
            'contractAmount' => $contractAmount,
            'balanceDue' => $contractAmount !== null ? ((float) $contractAmount - $paymentTotal) : null,
            'employeeFinance' => $this->buildEmployeeFinance($payments, $expenses),
            'checklist' => ChecklistItem::allForJob($job['id']),
        ]);
    }

    /**
     * "Kimde ne kadar kaldi": for each employee who received a payment
     * and/or logged an expense on this job, nets what they've collected
     * against what they've spent — the cash they're currently holding (or
     * owe back) for this specific job.
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

    public function updateStartDate(): void
    {
        if (!Auth::can('customers.edit')) {
            $this->forbidden();
            return;
        }

        $job = $this->loadJobOr404();
        if (!$job) {
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            $this->redirect('/jobs/show?id=' . $job['id']);
            return;
        }

        $startDate = trim((string) $this->input('start_date'));
        $startTime = trim((string) $this->input('start_time'));
        $durationRaw = trim((string) $this->input('duration_hours'));
        $duration = $durationRaw !== '' ? (float) preg_replace('/[^0-9.]/', '', $durationRaw) : null;

        Job::updateStartDate(
            (int) $job['id'],
            $startDate !== '' ? $startDate : null,
            $startTime !== '' ? $startTime : null,
            $duration
        );

        $this->redirect('/jobs/show?id=' . $job['id']);
    }

    public function updateStatus(): void
    {
        if (!Auth::can('customers.edit')) {
            $this->forbidden();
            return;
        }

        $job = $this->loadJobOr404();
        if (!$job) {
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            $this->redirect('/jobs/show?id=' . $job['id']);
            return;
        }

        $status = (string) $this->input('status');
        if (in_array($status, self::VALID_STATUSES, true)) {
            Job::updateStatus((int) $job['id'], $status);
        }

        $this->redirect('/jobs/show?id=' . $job['id']);
    }

    /**
     * Assigns an employee to the job and immediately emails them the
     * customer's name, address, and job details.
     */
    public function assignEmployee(): void
    {
        if (!Auth::can('customers.edit')) {
            $this->forbidden();
            return;
        }

        $job = $this->loadJobOr404();
        if (!$job) {
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            $this->redirect('/jobs/show?id=' . $job['id']);
            return;
        }

        $userId = (int) $this->input('user_id');
        if ($userId > 0) {
            Job::assignEmployee((int) $job['id'], $userId);
            JobNotifier::notifyEmployeeAssigned((int) $job['id'], $userId);
        }

        $this->redirect('/jobs/show?id=' . $job['id']);
    }

    public function unassignEmployee(): void
    {
        if (!Auth::can('customers.edit')) {
            $this->forbidden();
            return;
        }

        $job = $this->loadJobOr404();
        if (!$job) {
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            $this->redirect('/jobs/show?id=' . $job['id']);
            return;
        }

        Job::unassignEmployee((int) $job['id'], (int) $this->input('user_id'));
        $this->redirect('/jobs/show?id=' . $job['id']);
    }

    // ---- Expenses ----

    public function addExpense(): void
    {
        if (!Auth::can('customers.edit')) {
            $this->forbidden();
            return;
        }

        $job = $this->loadJobOr404();
        if (!$job) {
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            $this->redirect('/jobs/show?id=' . $job['id']);
            return;
        }

        $amountRaw = (string) $this->input('amount');
        $amount = (float) preg_replace('/[^0-9.\-]/', '', $amountRaw);

        // "Kim yapti": defaults to whoever is submitting the form, but can
        // be pointed at a different employee (e.g. office staff logging an
        // expense a field employee reported by phone) via the select.
        $performedBy = (int) $this->input('performed_by');

        if ($amount > 0) {
            Expense::create([
                'job_id' => $job['id'],
                'category' => $this->input('category'),
                'description' => $this->input('description'),
                'amount' => $amount,
                'created_by' => $performedBy > 0 ? $performedBy : Auth::id(),
            ]);
        }

        $this->redirect('/jobs/show?id=' . $job['id']);
    }

    public function deleteExpense(): void
    {
        if (!Auth::can('customers.delete')) {
            $this->forbidden();
            return;
        }

        $expense = Expense::find((int) $this->input('id'));
        if (!$expense) {
            http_response_code(404);
            echo View::renderWithLayout('errors/404');
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            $this->redirect('/jobs/show?id=' . $expense['job_id']);
            return;
        }

        Expense::softDelete((int) $expense['id']);
        $this->redirect('/jobs/show?id=' . $expense['job_id']);
    }

    // ---- Payments ----

    public function addPayment(): void
    {
        if (!Auth::can('customers.edit')) {
            $this->forbidden();
            return;
        }

        $job = $this->loadJobOr404();
        if (!$job) {
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            $this->redirect('/jobs/show?id=' . $job['id']);
            return;
        }

        $amountRaw = (string) $this->input('amount');
        $amount = (float) preg_replace('/[^0-9.\-]/', '', $amountRaw);

        $method = (string) $this->input('method');
        if (!in_array($method, self::VALID_PAYMENT_METHODS, true)) {
            $method = 'cash';
        }

        $receivedBy = (int) $this->input('received_by');

        if ($amount > 0) {
            Payment::create([
                'job_id' => $job['id'],
                'amount' => $amount,
                'method' => $method,
                'note' => $this->input('note'),
                'received_by' => $receivedBy > 0 ? $receivedBy : Auth::id(),
            ]);
        }

        $this->redirect('/jobs/show?id=' . $job['id']);
    }

    public function deletePayment(): void
    {
        if (!Auth::can('customers.delete')) {
            $this->forbidden();
            return;
        }

        $payment = Payment::find((int) $this->input('id'));
        if (!$payment) {
            http_response_code(404);
            echo View::renderWithLayout('errors/404');
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            $this->redirect('/jobs/show?id=' . $payment['job_id']);
            return;
        }

        Payment::softDelete((int) $payment['id']);
        $this->redirect('/jobs/show?id=' . $payment['job_id']);
    }

    // ---- Checklist ----

    public function addChecklistItem(): void
    {
        if (!Auth::can('customers.edit')) {
            $this->forbidden();
            return;
        }

        $job = $this->loadJobOr404();
        if (!$job) {
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            $this->redirect('/jobs/show?id=' . $job['id']);
            return;
        }

        $description = trim((string) $this->input('description'));
        if ($description !== '') {
            ChecklistItem::create((int) $job['id'], $description);
        }

        $this->redirect('/jobs/show?id=' . $job['id']);
    }

    public function toggleChecklistItem(): void
    {
        if (!Auth::can('customers.edit')) {
            $this->forbidden();
            return;
        }

        $item = ChecklistItem::find((int) $this->input('id'));
        if (!$item) {
            http_response_code(404);
            echo View::renderWithLayout('errors/404');
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            $this->redirect('/jobs/show?id=' . $item['job_id']);
            return;
        }

        ChecklistItem::toggle((int) $item['id']);
        $this->redirect('/jobs/show?id=' . $item['job_id']);
    }

    public function deleteChecklistItem(): void
    {
        if (!Auth::can('customers.edit')) {
            $this->forbidden();
            return;
        }

        $item = ChecklistItem::find((int) $this->input('id'));
        if (!$item) {
            http_response_code(404);
            echo View::renderWithLayout('errors/404');
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            $this->redirect('/jobs/show?id=' . $item['job_id']);
            return;
        }

        ChecklistItem::delete((int) $item['id']);
        $this->redirect('/jobs/show?id=' . $item['job_id']);
    }

    // ---- Helpers ----

    private function loadJobOr404(): ?array
    {
        $id = (int) $this->input('id');
        $job = Job::find($id);

        if (!$job) {
            http_response_code(404);
            echo View::renderWithLayout('errors/404');
            return null;
        }

        return $job;
    }

    private function forbidden(): void
    {
        http_response_code(403);
        echo View::renderWithLayout('errors/403');
    }
}
