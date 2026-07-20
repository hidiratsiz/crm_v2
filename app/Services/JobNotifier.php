<?php

namespace App\Services;

use App\Core\Mailer;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Project;

class JobNotifier
{
    /**
     * Emails the assigned employee the customer's name, address, and job
     * details, then marks them as notified. Returns true only if the email
     * was actually accepted for delivery by the mail transport.
     */
    public static function notifyEmployeeAssigned(int $jobId, int $userId): bool
    {
        $job = Job::find($jobId);
        if (!$job) {
            return false;
        }

        $project = Project::find((int) $job['project_id']);
        $customer = $project ? Customer::find((int) $project['customer_id']) : null;

        $employee = null;
        foreach (Job::employeesForJob($jobId) as $assigned) {
            if ((int) $assigned['id'] === $userId) {
                $employee = $assigned;
                break;
            }
        }

        if (!$employee || empty($employee['email'])) {
            return false;
        }

        $lines = [];
        $lines[] = "Merhaba {$employee['name']},";
        $lines[] = '';
        $lines[] = 'Size yeni bir is atandi:';
        $lines[] = '';
        if ($customer) {
            $lines[] = 'Musteri: ' . $customer['name'];
            if (!empty($customer['phone'])) {
                $lines[] = 'Telefon: ' . $customer['phone'];
            }
            if (!empty($customer['address'])) {
                $lines[] = 'Adres: ' . $customer['address'];
            }
        }
        if ($project) {
            $lines[] = '';
            $lines[] = 'Is: ' . $project['name'];
            if (!empty($project['notes'])) {
                $lines[] = 'Detaylar: ' . $project['notes'];
            }
        }
        if (!empty($job['start_date'])) {
            $lines[] = '';
            $lines[] = 'Baslama Tarihi: ' . $job['start_date'];
        }

        $sent = Mailer::send(
            $employee['email'],
            $employee['name'],
            'Yeni Is Atamasi: ' . ($project['name'] ?? ''),
            implode("\n", $lines)
        );

        if ($sent) {
            Job::markEmployeeNotified($jobId, $userId);
        }

        return $sent;
    }
}
