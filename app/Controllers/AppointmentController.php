<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Appointment;
use App\Models\Project;

class AppointmentController extends Controller
{
    private const VALID_STATUSES = ['scheduled', 'completed', 'cancelled'];

    /**
     * Manual "escape hatch" for adding a site-visit/inspection appointment
     * from a project page — mirrors what Quick Capture creates automatically
     * when a note mentions one, for whenever you'd rather not dictate it.
     */
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
            $this->redirect('/projects/show?id=' . $projectId);
            return;
        }

        $scheduledDate = trim((string) $this->input('scheduled_date'));
        if ($scheduledDate === '') {
            // No dedicated error view for this mini-form — just bounce back
            // to the project page, same as other silently-ignored bad input
            // in this controller family (e.g. an invalid status value).
            $this->redirect('/projects/show?id=' . $projectId);
            return;
        }

        Appointment::create([
            'project_id' => $projectId,
            'title' => trim((string) $this->input('title')) ?: 'Inceleme Randevusu',
            'scheduled_date' => $scheduledDate,
            'scheduled_time' => $this->input('scheduled_time') ?: null,
            'notes' => $this->input('notes') ?: null,
        ]);

        $this->redirect('/projects/show?id=' . $projectId);
    }

    public function updateStatus(): void
    {
        if (!Auth::can('customers.edit')) {
            $this->forbidden();
            return;
        }

        $id = (int) $this->input('id');
        $appointment = Appointment::find($id);

        if (!$appointment) {
            http_response_code(404);
            echo View::renderWithLayout('errors/404');
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            $this->redirect('/projects/show?id=' . $appointment['project_id']);
            return;
        }

        $status = (string) $this->input('status');
        if (in_array($status, self::VALID_STATUSES, true)) {
            Appointment::updateStatus($id, $status);
        }

        $this->redirect('/projects/show?id=' . $appointment['project_id']);
    }

    public function delete(): void
    {
        if (!Auth::can('customers.delete')) {
            $this->forbidden();
            return;
        }

        $id = (int) $this->input('id');
        $appointment = Appointment::find($id);

        if (!$appointment) {
            http_response_code(404);
            echo View::renderWithLayout('errors/404');
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            $this->redirect('/projects/show?id=' . $appointment['project_id']);
            return;
        }

        Appointment::softDelete($id);
        $this->redirect('/projects/show?id=' . $appointment['project_id']);
    }

    private function forbidden(): void
    {
        http_response_code(403);
        echo View::renderWithLayout('errors/403');
    }
}
