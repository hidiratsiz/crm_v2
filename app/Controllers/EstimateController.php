<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Estimate;
use App\Models\Project;

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

        echo View::renderWithLayout('estimates/create', ['project' => $project, 'error' => null]);
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
            echo View::renderWithLayout('estimates/create', ['project' => $project, 'error' => 'Oturum suresi doldu, tekrar deneyin.']);
            return;
        }

        $title = trim((string) $this->input('title'));
        if ($title === '') {
            echo View::renderWithLayout('estimates/create', ['project' => $project, 'error' => 'Teklif basligi zorunludur.']);
            return;
        }

        Estimate::create([
            'project_id' => $projectId,
            'option_number' => Estimate::nextOptionNumber($projectId),
            'title' => $title,
            'description' => $this->input('description'),
            'amount' => $this->normalizeAmountInput($this->input('amount')),
        ]);

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

        echo View::renderWithLayout('estimates/edit', ['estimate' => $estimate, 'error' => null]);
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
            echo View::renderWithLayout('estimates/edit', ['estimate' => $estimate, 'error' => 'Oturum suresi doldu, tekrar deneyin.']);
            return;
        }

        $title = trim((string) $this->input('title'));
        if ($title === '') {
            echo View::renderWithLayout('estimates/edit', ['estimate' => $estimate, 'error' => 'Teklif basligi zorunludur.']);
            return;
        }

        Estimate::update($id, [
            'title' => $title,
            'description' => $this->input('description'),
            'amount' => $this->normalizeAmountInput($this->input('amount')),
        ]);

        $this->redirect('/projects/show?id=' . $estimate['project_id']);
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
