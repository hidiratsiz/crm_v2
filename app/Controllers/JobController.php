<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\View;
use App\Models\ChecklistItem;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Job;
use App\Models\Project;
use App\Models\User;
use App\Services\JobNotifier;

class JobController extends Controller
{
    private const VALID_STATUSES = ['pending_schedule', 'scheduled', 'in_progress', 'completed', 'cancelled'];

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

        echo View::renderWithLayout('jobs/show', [
            'job' => $job,
            'project' => $project,
            'customer' => $customer,
            'assignedEmployees' => Job::employeesForJob($job['id']),
            'availableEmployees' => User::allActive(),
            'expenses' => Expense::allForJob($job['id']),
            'expenseTotal' => Expense::totalForJob($job['id']),
            'checklist' => ChecklistItem::allForJob($job['id']),
        ]);
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

        if ($amount > 0) {
            Expense::create([
                'job_id' => $job['id'],
                'category' => $this->input('category'),
                'description' => $this->input('description'),
                'amount' => $amount,
                'created_by' => Auth::id(),
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
