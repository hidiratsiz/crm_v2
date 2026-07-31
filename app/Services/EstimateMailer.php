<?php

namespace App\Services;

use App\Core\Mailer;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\Project;

/**
 * Emails a finished estimate to the customer it belongs to. Separate from
 * JobNotifier (which emails an EMPLOYEE about a job) — this one always
 * targets the customer on file for the estimate's project.
 */
class EstimateMailer
{
    /**
     * @return array{success: bool, message: string}
     */
    public static function sendToCustomer(int $estimateId): array
    {
        $estimate = Estimate::find($estimateId);
        if (!$estimate) {
            return ['success' => false, 'message' => 'Teklif bulunamadi.'];
        }

        $project = Project::find((int) $estimate['project_id']);
        $customer = $project ? Customer::find((int) $project['customer_id']) : null;

        if (!$customer) {
            return ['success' => false, 'message' => 'Bu teklife ait musteri kaydi bulunamadi.'];
        }
        if (empty($customer['email'])) {
            return ['success' => false, 'message' => "{$customer['name']} icin kayitli bir e-posta adresi yok — once musteri kaydina e-posta ekleyin."];
        }

        $lines = [];
        $lines[] = "Merhaba {$customer['name']},";
        $lines[] = '';
        $lines[] = 'Talebiniz uzerine hazirladigimiz teklif asagidadir:';
        $lines[] = '';
        $lines[] = $estimate['title'];
        if (!empty($estimate['description'])) {
            $lines[] = '';
            $lines[] = $estimate['description'];
        }
        if ($estimate['amount'] !== null) {
            $lines[] = '';
            $lines[] = 'Toplam Tutar: $' . number_format((float) $estimate['amount'], 2);
        }
        $lines[] = '';
        $lines[] = 'Sorulariniz icin bize ulasabilirsiniz.';

        $subject = 'Teklifiniz: ' . $estimate['title'];
        $sent = Mailer::send($customer['email'], $customer['name'], $subject, implode("\n", $lines));

        if ($sent) {
            // Sending is what actually moves a draft into the customer's
            // hands, so reflect that in the status — but never downgrade an
            // estimate that's already further along (accepted/rejected),
            // and don't re-flip an already-'sent' one back to itself either
            // (harmless, but updateStatus would still fire a needless write).
            if ($estimate['status'] === 'draft') {
                Estimate::updateStatus($estimateId, 'sent');
            }
            return ['success' => true, 'message' => "Teklif {$customer['email']} adresine gonderildi."];
        }

        return ['success' => false, 'message' => 'E-posta gonderilemedi — hosting mail() ayarlarini kontrol edin.'];
    }
}
