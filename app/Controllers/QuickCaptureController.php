<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Appointment;
use App\Models\ChecklistItem;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\Expense;
use App\Models\Job;
use App\Models\Project;
use App\Models\User;
use App\Services\AiIntakeParser;
use App\Services\EstimateMailer;
use App\Services\JobNotifier;
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

        // Command mode: the message wasn't a new customer/job, it's an
        // instruction about an EXISTING job (assign employee, add expense,
        // change status, etc). Handle it separately and stop here.
        if ($parsed['intent'] !== 'new_capture') {
            $this->handleCommand($parsed);
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

        // Only create a project when the note actually describes a job OR a
        // site-visit appointment — a pure "add customer" message just
        // creates/updates the customer record and stops here. An appointment
        // needs a project to attach to even if no estimate/price was given
        // yet (e.g. "bugun 14:30'da incelemeye gidiyoruz" with no scope/price
        // decided), so it counts as job content on its own.
        $projectId = null;
        $estimateCount = 0;
        $appointmentCreated = null;

        if (!empty($parsed['estimates']) || !empty($parsed['appointment_date'])) {
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

            if (!empty($parsed['appointment_date'])) {
                $appointmentId = Appointment::create([
                    'project_id' => $projectId,
                    'title' => 'Inceleme Randevusu',
                    'scheduled_date' => $parsed['appointment_date'],
                    'scheduled_time' => $parsed['appointment_time'] ?? null,
                    'notes' => $parsed['appointment_notes'] ?? null,
                ]);
                $appointmentCreated = Appointment::find($appointmentId);
            }
        }

        echo View::renderWithLayout('quick-capture/result', [
            'parsed' => $parsed,
            'customerId' => $customerId,
            'projectId' => $projectId,
            'isNewCustomer' => $isNewCustomer,
            'customerUpdated' => $customerUpdated,
            'estimateCount' => $estimateCount,
            'appointmentCreated' => $appointmentCreated,
        ]);
    }

    // ---- Command mode: actions on EXISTING jobs, resolved by name ----

    private function handleCommand(array $parsed): void
    {
        $customerName = trim((string) ($parsed['target_customer_name'] ?? ''));
        if ($customerName === '') {
            $this->renderCommandResult(false, 'Hangi musterinin/isin kastedildigi metinden anlasilamadi. Lutfen musteri adini belirterek tekrar deneyin.');
            return;
        }

        // Estimate-level commands resolve via Project/Estimate, never via
        // Job — at the "teklifi duzenle" / "teklifi gonder" stage a Job may
        // not exist yet (that only appears once an estimate is accepted and
        // converted), so they need a separate resolution path.
        if (in_array($parsed['intent'], ['update_estimate', 'send_estimate'], true)) {
            $this->handleEstimateCommand($customerName, $parsed);
            return;
        }

        $matches = Job::findByCustomerNameLike($customerName);
        if (empty($matches)) {
            // Give a precise reason: does the customer even exist, or do
            // they exist but simply have no job (estimate not yet accepted
            // + converted) yet?
            $customerMatches = Customer::findByNameLike($customerName);

            if (empty($customerMatches)) {
                $this->renderCommandResult(false, "\"{$customerName}\" adinda bir musteri bulunamadi.");
                return;
            }

            if (count($customerMatches) > 1) {
                $names = array_column($customerMatches, 'name');
                $this->renderCommandResult(false, "\"{$customerName}\" adiyla birden fazla musteri eslesti (" . implode(', ', $names) . "). Lutfen daha spesifik bir isim girin.");
                return;
            }

            $this->renderCommandResult(false, "{$customerMatches[0]['name']} musteri olarak bulundu, ama henuz ise donusturulmus bir isi yok. Once ilgili teklifi \"Kabul Edildi\" yapip \"Ise Donustur\" ile bir is olusturun, sonra bu komutu tekrar deneyin.");
            return;
        }

        $distinctCustomerIds = array_unique(array_column($matches, 'customer_id'));
        if (count($distinctCustomerIds) > 1) {
            $names = array_values(array_unique(array_column($matches, 'customer_name')));
            $this->renderCommandResult(false, "\"{$customerName}\" adiyla birden fazla musteri eslesti (" . implode(', ', $names) . "). Lutfen daha spesifik bir isim girin.");
            return;
        }

        // Most recent job for this (uniquely identified) customer.
        $job = $matches[0];

        switch ($parsed['intent']) {
            case 'assign_employee':
                $this->handleAssignEmployee($job, $parsed);
                break;
            case 'unassign_employee':
                $this->handleUnassignEmployee($job, $parsed);
                break;
            case 'add_expense':
                $this->handleAddExpense($job, $parsed);
                break;
            case 'add_checklist_item':
                $this->handleAddChecklistItem($job, $parsed);
                break;
            case 'set_start_date':
                $this->handleSetStartDate($job, $parsed);
                break;
            case 'change_job_status':
                $this->handleChangeStatus($job, $parsed);
                break;
        }
    }

    /**
     * Resolves "David K teklifini duzenle" / "David K'ye teklifi gonder" to
     * the customer's most recent project, then dispatches to the right
     * estimate-level action. A separate resolution path from handleCommand's
     * Job-based one, since neither of these commands requires a Job to
     * already exist.
     */
    private function handleEstimateCommand(string $customerName, array $parsed): void
    {
        $customerMatches = Customer::findByNameLike($customerName);
        if (empty($customerMatches)) {
            $this->renderCommandResult(false, "\"{$customerName}\" adinda bir musteri bulunamadi.");
            return;
        }
        if (count($customerMatches) > 1) {
            $names = array_column($customerMatches, 'name');
            $this->renderCommandResult(false, "\"{$customerName}\" adiyla birden fazla musteri eslesti (" . implode(', ', $names) . "). Lutfen daha spesifik bir isim girin.");
            return;
        }

        $customer = Customer::find((int) $customerMatches[0]['id']);
        $project = $customer ? Project::mostRecentForCustomer((int) $customer['id']) : null;
        if (!$project) {
            $this->renderCommandResult(false, "{$customerMatches[0]['name']} musteri olarak bulundu, ama henuz bir proje/teklifi yok.");
            return;
        }

        if ($parsed['intent'] === 'update_estimate') {
            $this->handleUpdateEstimate($project, $parsed);
            return;
        }

        if ($parsed['intent'] === 'send_estimate') {
            $this->handleSendEstimate($project);
            return;
        }
    }

    private function handleUpdateEstimate(array $project, array $parsed): void
    {
        $estimate = Estimate::mostRecentDraftForProject((int) $project['id']);
        if (!$estimate) {
            $this->renderCommandResult(
                false,
                "\"{$project['name']}\" projesinde guncellenecek bir TASLAK teklif bulunamadi (belki zaten gonderilmis/kabul edilmis) — teklif sayfasindan elle duzenleyebilirsiniz.",
                null,
                $project
            );
            return;
        }

        $description = trim((string) ($parsed['estimate_description'] ?? ''));
        $amount = $parsed['estimate_amount'] ?? null;

        if ($description === '' && $amount === null) {
            $this->renderCommandResult(false, 'Guncellenecek is kapsami veya tutar metinden anlasilamadi.', null, $project);
            return;
        }

        Estimate::update((int) $estimate['id'], [
            'title' => $estimate['title'],
            'description' => $description !== '' ? $description : $estimate['description'],
            'amount' => $amount ?? $estimate['amount'],
            'service_module_id' => $estimate['service_module_id'],
        ]);

        $message = "\"{$estimate['title']}\" teklifi guncellendi.";
        if ($amount !== null) {
            $message .= ' Tutar: $' . number_format($amount, 2) . '.';
        }

        $this->renderCommandResult(true, $message, null, $project);
    }

    private function handleSendEstimate(array $project): void
    {
        $estimate = Estimate::mostRecentForProject((int) $project['id']);
        if (!$estimate) {
            $this->renderCommandResult(false, "\"{$project['name']}\" projesinde gonderilecek bir teklif bulunamadi.", null, $project);
            return;
        }

        $result = EstimateMailer::sendToCustomer((int) $estimate['id']);
        $this->renderCommandResult($result['success'], $result['message'], null, $project);
    }

    private function handleAssignEmployee(array $job, array $parsed): void
    {
        $employeeName = trim((string) ($parsed['employee_name'] ?? ''));
        if ($employeeName === '') {
            $this->renderCommandResult(false, 'Hangi calisanin atanacagi metinden anlasilamadi.', $job);
            return;
        }

        $employees = User::findByNameLike($employeeName);
        if (empty($employees)) {
            $this->renderCommandResult(false, "\"{$employeeName}\" adinda aktif bir kullanici bulunamadi.", $job);
            return;
        }
        if (count($employees) > 1) {
            $names = array_column($employees, 'name');
            $this->renderCommandResult(false, "\"{$employeeName}\" adiyla birden fazla kullanici eslesti (" . implode(', ', $names) . "). Lutfen tam adi yazin.", $job);
            return;
        }

        $employee = $employees[0];
        Job::assignEmployee((int) $job['id'], (int) $employee['id']);
        $notified = JobNotifier::notifyEmployeeAssigned((int) $job['id'], (int) $employee['id']);

        $message = "{$employee['name']}, {$job['customer_name']} musterisinin isine atandi.";
        $message .= $notified
            ? ' Musteri bilgileri ve is detaylari kendisine e-posta ile gonderildi.'
            : ' (Not: bildirim e-postasi gonderilemedi — calisanin e-posta adresini kontrol edin.)';

        $this->renderCommandResult(true, $message, $job);
    }

    private function handleUnassignEmployee(array $job, array $parsed): void
    {
        $employeeName = trim((string) ($parsed['employee_name'] ?? ''));
        if ($employeeName === '') {
            $this->renderCommandResult(false, 'Hangi calisanin kaldirilacagi metinden anlasilamadi.', $job);
            return;
        }

        $employees = User::findByNameLike($employeeName);
        if (empty($employees)) {
            $this->renderCommandResult(false, "\"{$employeeName}\" adinda aktif bir kullanici bulunamadi.", $job);
            return;
        }
        if (count($employees) > 1) {
            $names = array_column($employees, 'name');
            $this->renderCommandResult(false, "\"{$employeeName}\" adiyla birden fazla kullanici eslesti (" . implode(', ', $names) . "). Lutfen tam adi yazin.", $job);
            return;
        }

        Job::unassignEmployee((int) $job['id'], (int) $employees[0]['id']);
        $this->renderCommandResult(true, "{$employees[0]['name']}, {$job['customer_name']} isinden kaldirildi.", $job);
    }

    private function handleAddExpense(array $job, array $parsed): void
    {
        $amount = $parsed['expense_amount'] ?? null;
        if (empty($amount) || $amount <= 0) {
            $this->renderCommandResult(false, 'Gider tutari metinden anlasilamadi. Lutfen tutari acikca belirtin (orn. "200 dolar").', $job);
            return;
        }

        Expense::create([
            'job_id' => $job['id'],
            'category' => $parsed['expense_category'] ?? null,
            'description' => $parsed['expense_description'] ?? null,
            'amount' => $amount,
            'created_by' => Auth::id(),
        ]);

        $message = "{$job['customer_name']} isine \${$this->formatAmount($amount)} tutarinda gider eklendi.";
        $this->renderCommandResult(true, $message, $job);
    }

    private function handleAddChecklistItem(array $job, array $parsed): void
    {
        $description = trim((string) ($parsed['checklist_description'] ?? ''));
        if ($description === '') {
            $this->renderCommandResult(false, 'Eklenecek adim metinden anlasilamadi.', $job);
            return;
        }

        ChecklistItem::create((int) $job['id'], $description);
        $this->renderCommandResult(true, "\"{$description}\" adimi {$job['customer_name']} isine eklendi.", $job);
    }

    private function handleSetStartDate(array $job, array $parsed): void
    {
        $startDate = $parsed['start_date'] ?? null;
        if (empty($startDate)) {
            $this->renderCommandResult(false, 'Baslangic tarihi metinden anlasilamadi. Lutfen net bir tarih belirtin (orn. "1 Agustos").', $job);
            return;
        }

        $startTime = $parsed['start_time'] ?? null;
        $duration = $parsed['duration_hours'] ?? null;

        Job::updateStartDate((int) $job['id'], $startDate, $startTime, $duration);

        $message = "{$job['customer_name']} isinin baslangic tarihi {$startDate} olarak ayarlandi.";
        if ($startTime) {
            $message .= " Saat: {$startTime}.";
        }
        if ($duration) {
            $message .= " Tahmini sure: {$duration} saat.";
        }

        $this->renderCommandResult(true, $message, $job);
    }

    private function handleChangeStatus(array $job, array $parsed): void
    {
        $status = $parsed['new_status'] ?? null;
        $labels = [
            'pending_schedule' => 'Baslangic Bekleniyor',
            'scheduled' => 'Planlandi',
            'in_progress' => 'Devam Ediyor',
            'completed' => 'Tamamlandi',
            'cancelled' => 'Iptal',
        ];

        if (empty($status) || !isset($labels[$status])) {
            $this->renderCommandResult(false, 'Hangi duruma gecirilecegi metinden anlasilamadi.', $job);
            return;
        }

        Job::updateStatus((int) $job['id'], $status);
        $this->renderCommandResult(true, "{$job['customer_name']} isinin durumu \"{$labels[$status]}\" olarak guncellendi.", $job);
    }

    private function renderCommandResult(bool $success, string $message, ?array $job = null, ?array $project = null): void
    {
        echo View::renderWithLayout('quick-capture/command-result', [
            'success' => $success,
            'message' => $message,
            'job' => $job,
            'project' => $project,
        ]);
    }

    private function formatAmount(float $amount): string
    {
        return number_format($amount, 2);
    }

    private function forbidden(): void
    {
        http_response_code(403);
        echo View::renderWithLayout('errors/403');
    }
}
