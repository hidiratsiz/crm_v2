<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Models\Job;
use App\Models\Project;

class DashboardController extends Controller
{
    public function index(): void
    {
        $recentProjects = Project::allWithCustomer(5);
        $recentJobs = Job::allWithDetails(5);

        echo View::renderWithLayout('dashboard/index', [
            'recentProjects' => $recentProjects,
            'recentJobs' => $recentJobs,
        ]);
    }
}
