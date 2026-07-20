<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\View;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\EstimateFieldValue;
use App\Models\Job;
use App\Models\Project;

class ProjectController extends Controller
{
    public function index(): void
    {
        if (!Auth::can('customers.view')) {
            $this->forbidden();
            return;
        }

        $projects = Project::allWithCustomer();

        echo View::renderWithLayout('projects/index', ['projects' => $projects]);
    }

    public function show(): void
    {
        if (!Auth::can('customers.view')) {
            $this->forbidden();
            return;
        }

        $id = (int) $this->input('id');
        $project = Project::find($id);

        if (!$project) {
            http_response_code(404);
            echo View::renderWithLayout('errors/404');
            return;
        }

        $customer = Customer::find((int) $project['customer_id']);
        $estimates = Estimate::allForProject($id);

        // For each accepted estimate, check whether it's already been
        // converted to a job so the view can show "Isi Goruntule" instead
        // of "Ise Donustur" once conversion has happened. Also fetch the
        // priced field breakdown for estimates created via a service module.
        $jobsByEstimate = [];
        $fieldValuesByEstimate = [];
        foreach ($estimates as $estimate) {
            $job = Job::findByEstimateId((int) $estimate['id']);
            if ($job) {
                $jobsByEstimate[$estimate['id']] = $job;
            }
            if (!empty($estimate['service_module_id'])) {
                $fieldValuesByEstimate[$estimate['id']] = EstimateFieldValue::allForEstimate((int) $estimate['id']);
            }
        }

        echo View::renderWithLayout('projects/show', [
            'project' => $project,
            'customer' => $customer,
            'estimates' => $estimates,
            'jobsByEstimate' => $jobsByEstimate,
            'fieldValuesByEstimate' => $fieldValuesByEstimate,
        ]);
    }

    private function forbidden(): void
    {
        http_response_code(403);
        echo View::renderWithLayout('errors/403');
    }
}
