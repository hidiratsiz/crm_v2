<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\View;
use App\Models\Customer;
use App\Models\Estimate;
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

        echo View::renderWithLayout('projects/show', [
            'project' => $project,
            'customer' => $customer,
            'estimates' => $estimates,
        ]);
    }

    private function forbidden(): void
    {
        http_response_code(403);
        echo View::renderWithLayout('errors/403');
    }
}
