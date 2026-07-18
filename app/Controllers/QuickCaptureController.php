<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\Project;
use App\Services\AiIntakeParser;
use Throwable;

class QuickCaptureController extends Controller
{
    public function showForm(): void
    {
        if (!Auth::can('customers.create')) {
            $this->forbidden();
            return;
        }

        echo View::renderWithLayout('quick-capture/index', ['error' => null, 'raw_text' => '']);
    }

    public function process(): void
    {
        if (!Auth::can('customers.create')) {
            $this->forbidden();
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            echo View::renderWithLayout('quick-capture/index', [
                'error' => 'Oturum suresi doldu, lutfen tekrar deneyin.',
                'raw_text' => '',
            ]);
            return;
        }

        $rawText = trim((string) $this->input('raw_text'));

        if ($rawText === '') {
            echo View::renderWithLayout('quick-capture/index', [
                'error' => 'Lutfen bir not girin veya sesli yazdirin.',
                'raw_text' => '',
            ]);
            return;
        }

        $config = require APP_ROOT . '/config/config.php';

        try {
            $parsed = AiIntakeParser::parse($rawText, $config);
        } catch (Throwable $e) {
            echo View::renderWithLayout('quick-capture/index', [
                'error' => 'AI isleme hatasi: ' . $e->getMessage(),
                'raw_text' => $rawText,
            ]);
            return;
        }

        // Match an existing customer — phone first (most reliable), then
        // fall back to exact name match if no phone was given/found.
        $customer = null;
        if (!empty($parsed['phone'])) {
            $customer = Customer::findByPhone($parsed['phone']);
        }
        if (!$customer && !empty($parsed['customer_name'])) {
            $customer = Customer::findByName($parsed['customer_name']);
        }

        $isNewCustomer = false;
        $customerUpdated = false;

        if ($customer) {
            $customerId = (int) $customer['id'];

            // Merge: fill in any currently-EMPTY fields with newly provided
            // info. Never overwrite a field that already has a value — a
            // bad AI guess should never clobber good existing data.
            $mergedPhone = !empty($customer['phone']) ? $customer['phone'] : ($parsed['phone'] ?? null);
            $mergedEmail = !empty($customer['email']) ? $customer['email'] : ($parsed['email'] ?? null);
            $mergedAddress = !empty($customer['address']) ? $customer['address'] : ($parsed['address'] ?? null);

            if ($mergedPhone !== $customer['phone'] || $mergedEmail !== $customer['email'] || $mergedAddress !== $customer['address']) {
                Customer::update($customerId, [
                    'name' => $customer['name'],
                    'phone' => $mergedPhone,
                    'email' => $mergedEmail,
                    'address' => $mergedAddress,
                    'notes' => $customer['notes'],
                ]);
                $customerUpdated = true;
            }
        } else {
            $isNewCustomer = true;
            $customerId = Customer::create([
                'name' => $parsed['customer_name'] ?: 'Isimsiz Musteri',
                'phone' => $parsed['phone'] ?? null,
                'email' => $parsed['email'] ?? null,
                'address' => $parsed['address'] ?? null,
                'notes' => $parsed['city'] ? ('Sehir: ' . $parsed['city']) : null,
            ], Auth::id());
        }

        // Only create a project/estimates when the note actually describes
        // a job — a pure "add customer" message just creates/updates the
        // customer record and stops here.
        $projectId = null;
        $estimateCount = 0;

        if (!empty($parsed['estimates'])) {
            $projectId = Project::create([
                'customer_id' => $customerId,
                'name' => $parsed['project_title'] ?: ($parsed['service_type'] ?: 'Yeni Is'),
                'status' => 'lead',
                'notes' => $parsed['details'] ?? null,
                'raw_input' => $rawText,
                'ai_summary' => $parsed['service_type'] ?? null,
            ]);

            $optionNumber = 1;
            foreach ($parsed['estimates'] as $estimate) {
                Estimate::create([
                    'project_id' => $projectId,
                    'option_number' => $optionNumber,
                    'title' => $estimate['title'] ?? ('Teklif ' . $optionNumber),
                    'description' => $estimate['description'] ?? null,
                    'amount' => $estimate['amount'] ?? null,
                ]);
                $optionNumber++;
                $estimateCount++;
            }
        }

        echo View::renderWithLayout('quick-capture/result', [
            'parsed' => $parsed,
            'customerId' => $customerId,
            'projectId' => $projectId,
            'isNewCustomer' => $isNewCustomer,
            'customerUpdated' => $customerUpdated,
            'estimateCount' => $estimateCount,
        ]);
    }

    private function forbidden(): void
    {
        http_response_code(403);
        echo View::renderWithLayout('errors/403');
    }
}
