<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Estimate;
use App\Models\EstimateFieldValue;
use App\Models\Job;
use App\Models\Project;
use App\Models\ServiceModule;
use App\Models\ServiceModuleField;
use App\Services\EstimateMailer;
use App\Services\PricingEngine;

class EstimateController extends Controller
{
    private const VALID_STATUSES = ['draft', 'sent', 'accepted', 'rejected'];

    public function showCreate(): void
    {
        if (!Auth::can('customers.create')) {
            $this->forbidden();
            return;
        }

        $projectId = (int) $this->input('project_id');
        $project = Project::find($projectId);

        if (!$project) {
            http_response_code(404);
            echo View::renderWithLayout('errors/404');
            return;
        }

        echo View::renderWithLayout('estimates/create', [
            'project' => $project,
            'serviceModules' => ServiceModule::allActive(),
            'error' => null,
        ]);
    }

    public function store(): void
    {
        if (!Auth::can('customers.create')) {
            $this->forbidden();
            return;
        }

        $projectId = (int) $this->input('project_id');
        $project = Project::find($projectId);

        if (!$project) {
            http_response_code(404);
            echo View::renderWithLayout('errors/404');
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            echo View::renderWithLayout('estimates/create', [
                'project' => $project,
                'serviceModules' => ServiceModule::allActive(),
                'error' => 'Oturum suresi doldu, tekrar deneyin.',
            ]);
            return;
        }

        $title = trim((string) $this->input('title'));
        if ($title === '') {
            echo View::renderWithLayout('estimates/create', [
                'project' => $project,
                'serviceModules' => ServiceModule::allActive(),
                'error' => 'Teklif basligi zorunludur.',
            ]);
            return;
        }

        $serviceModuleId = (int) $this->input('service_module_id');
        $amount = $this->normalizeAmountInput($this->input('amount'));
        $fieldRows = [];

        // If a service module was selected, recompute the price SERVER-SIDE
        // from the submitted field values — the live AJAX preview is only
        // ever a convenience for the user, never trusted as the final price.
        if ($serviceModuleId > 0) {
            $fields = ServiceModuleField::allForModule($serviceModuleId);
            $values = [];
            foreach ($fields as $field) {
                $values[$field['field_key']] = $this->input('field_' . $field['id']);
            }
            $result = PricingEngine::calculate($fields, $values);
            $amount = $result['total'];
            $fieldRows = $result['lines'];
        }

        $estimateId = Estimate::create([
            'project_id' => $projectId,
            'service_module_id' => $serviceModuleId > 0 ? $serviceModuleId : null,
            'option_number' => Estimate::nextOptionNumber($projectId),
            'title' => $title,
            'description' => $this->input('description'),
            'amount' => $amount,
        ]);

        foreach ($fieldRows as $line) {
            $value = $line['value'];
            $storedValue = is_bool($value) ? ($value ? '1' : '0') : (is_scalar($value) ? (string) $value : null);
            EstimateFieldValue::create($estimateId, $line['field_id'], $storedValue, $line['price']);
        }

        $this->redirect('/projects/show?id=' . $projectId);
    }

    public function showEdit(): void
    {
        if (!Auth::can('customers.edit')) {
            $this->forbidden();
            return;
        }

        $id = (int) $this->input('id');
        $estimate = Estimate::find($id);

        if (!$estimate) {
            http_response_code(404);
            echo View::renderWithLayout('errors/404');
            return;
        }

        echo View::renderWithLayout('estimates/edit', $this->editViewData($estimate, null));
    }

    public function update(): void
    {
        if (!Auth::can('customers.edit')) {
            $this->forbidden();
            return;
        }

        $id = (int) $this->input('id');
        $estimate = Estimate::find($id);

        if (!$estimate) {
            http_response_code(404);
            echo View::renderWithLayout('errors/404');
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            echo View::renderWithLayout('estimates/edit', $this->editViewData($estimate, 'Oturum suresi doldu, tekrar deneyin.'));
            return;
        }

        $title = trim((string) $this->input('title'));
        if ($title === '') {
            echo View::renderWithLayout('estimates/edit', $this->editViewData($estimate, 'Teklif basligi zorunludur.'));
            return;
        }

        $serviceModuleId = (int) $this->input('service_module_id');
        $amount = $this->normalizeAmountInput($this->input('amount'));
        $fieldRows = [];

        // Same rule as store(): if a service module is selected, the price is
        // ALWAYS recomputed server-side from the submitted field values, the
        // live AJAX preview is never trusted as the final amount.
        if ($serviceModuleId > 0) {
            $fields = ServiceModuleField::allForModule($serviceModuleId);
            $values = [];
            foreach ($fields as $field) {
                $values[$field['field_key']] = $this->input('field_' . $field['id']);
            }
            $result = PricingEngine::calculate($fields, $values);
            $amount = $result['total'];
            $fieldRows = $result['lines'];
        }

        Estimate::update($id, [
            'title' => $title,
            'description' => $this->input('description'),
            'amount' => $amount,
            'service_module_id' => $serviceModuleId > 0 ? $serviceModuleId : null,
        ]);

        // Field values are always fully replaced: whether the user kept the
        // same service, switched to a different one, or dropped back to
        // manual (in which case there's simply nothing left to insert).
        EstimateFieldValue::deleteForEstimate($id);
        foreach ($fieldRows as $line) {
            $value = $line['value'];
            $storedValue = is_bool($value) ? ($value ? '1' : '0') : (is_scalar($value) ? (string) $value : null);
            EstimateFieldValue::create($id, $line['field_id'], $storedValue, $line['price']);
        }

        $this->redirect('/projects/show?id=' . $estimate['project_id']);
    }

    /**
     * Builds the view data for the edit form: active service modules (plus
     * the estimate's current module even if it was since deactivated, so
     * editing doesn't silently drop it from the dropdown), and a field_id
     * => stored value map so the dynamic fields can be pre-filled the same
     * way the create form fills them in live.
     */
    private function editViewData(array $estimate, ?string $error): array
    {
        $serviceModules = ServiceModule::allActive();

        $currentModuleId = (int) ($estimate['service_module_id'] ?? 0);
        if ($currentModuleId > 0 && !in_array($currentModuleId, array_column($serviceModules, 'id'), true)) {
            $currentModule = ServiceModule::find($currentModuleId);
            if ($currentModule) {
                $currentModule['name'] .= ' (pasif)';
                array_unshift($serviceModules, $currentModule);
            }
        }

        $existingValues = [];
        foreach (EstimateFieldValue::allForEstimate((int) $estimate['id']) as $row) {
            $existingValues[(int) $row['service_module_field_id']] = $row['value'];
        }

        return [
            'estimate' => $estimate,
            'serviceModules' => $serviceModules,
            'existingValues' => $existingValues,
            'error' => $error,
        ];
    }

    public function updateStatus(): void
    {
        if (!Auth::can('customers.edit')) {
            $this->forbidden();
            return;
        }

        $id = (int) $this->input('id');
        $estimate = Estimate::find($id);

        if (!$estimate) {
            http_response_code(404);
            echo View::renderWithLayout('errors/404');
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            $this->redirect('/projects/show?id=' . $estimate['project_id']);
            return;
        }

        $status = (string) $this->input('status');
        if (in_array($status, self::VALID_STATUSES, true)) {
            Estimate::updateStatus($id, $status);
        }

        $this->redirect('/projects/show?id=' . $estimate['project_id']);
    }

    public function delete(): void
    {
        if (!Auth::can('customers.delete')) {
            $this->forbidden();
            return;
        }

        $id = (int) $this->input('id');
        $estimate = Estimate::find($id);

        if (!$estimate) {
            http_response_code(404);
            echo View::renderWithLayout('errors/404');
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            $this->redirect('/projects/show?id=' . $estimate['project_id']);
            return;
        }

        Estimate::softDelete($id);
        $this->redirect('/projects/show?id=' . $estimate['project_id']);
    }

    /**
     * Converts an ACCEPTED estimate into a job. Refuses if the estimate
     * isn't accepted, or if it was already converted before (one job per
     * estimate — prevents accidental duplicate jobs from double-clicks).
     */
    public function convertToJob(): void
    {
        if (!Auth::can('customers.edit')) {
            $this->forbidden();
            return;
        }

        $id = (int) $this->input('id');
        $estimate = Estimate::find($id);

        if (!$estimate) {
            http_response_code(404);
            echo View::renderWithLayout('errors/404');
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            $this->redirect('/projects/show?id=' . $estimate['project_id']);
            return;
        }

        if ($estimate['status'] !== 'accepted') {
            // Only accepted estimates can become jobs.
            $this->redirect('/projects/show?id=' . $estimate['project_id']);
            return;
        }

        $existingJob = Job::findByEstimateId($id);
        if ($existingJob) {
            // Already converted — just go straight to the existing job.
            $this->redirect('/jobs/show?id=' . $existingJob['id']);
            return;
        }

        $jobId = Job::create([
            'project_id' => $estimate['project_id'],
            'estimate_id' => $id,
            'status' => 'pending_schedule',
            'notes' => $estimate['description'],
        ]);

        $this->redirect('/jobs/show?id=' . $jobId);
    }

    /**
     * Emails the estimate to the customer on file for its project. This is
     * the manual "button" path — the same action is also reachable via a
     * Quick Capture voice command ("David K'ye teklifi gonder"), which goes
     * through EstimateMailer directly from QuickCaptureController.
     */
    public function sendToCustomer(): void
    {
        if (!Auth::can('customers.edit')) {
            $this->forbidden();
            return;
        }

        $id = (int) $this->input('id');
        $estimate = Estimate::find($id);

        if (!$estimate) {
            http_response_code(404);
            echo View::renderWithLayout('errors/404');
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            $this->redirect('/projects/show?id=' . $estimate['project_id']);
            return;
        }

        $result = EstimateMailer::sendToCustomer($id);
        $this->redirect('/projects/show?id=' . $estimate['project_id'] . '&mail_sent=' . ($result['success'] ? '1' : '0'));
    }

    private function normalizeAmountInput(?string $amount): ?float
    {
        if ($amount === null || trim($amount) === '') {
            return null;
        }
        $cleaned = preg_replace('/[^0-9.\-]/', '', $amount);
        return $cleaned === '' ? null : (float) $cleaned;
    }

    private function forbidden(): void
    {
        http_response_code(403);
        echo View::renderWithLayout('errors/403');
    }
}
