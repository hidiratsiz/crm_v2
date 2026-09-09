<?php

namespace App\Services;

use App\Services\Ai\AiProviderFactory;

/**
 * Answers free-form questions about the CRM's data ("David K'nin bakiyesi
 * ne?", "bu hafta hangi isler var?") by sending the AI a compact snapshot
 * of the database plus the user's question. Read-only: never mutates
 * anything, only reads and summarizes.
 */
class AiAnswerer
{
    public static function answer(string $question, array $config): string
    {
        $snapshot = CrmSnapshot::build();

        $dayNamesTr = ['Pazartesi', 'Sali', 'Carsamba', 'Persembe', 'Cuma', 'Cumartesi', 'Pazar'];
        $dayName = $dayNamesTr[((int) date('N')) - 1];

        $systemPrompt = 'Sen JobPro CRM icin bir veri asistanisin. Bir ev hizmetleri '
            . 'isletmesinin (guverte onarimi, boya vb.) sahibine yardim ediyorsun. '
            . 'Bugunun tarihi: ' . date('Y-m-d') . " ({$dayName}).\n\n"
            . "Asagida sirketin GUNCEL verileri JSON formatinda verilmistir. Kullanicinin sorusunu "
            . "SADECE bu verilere dayanarak, Turkce, kisa ve net yanitla:\n"
            . "- Tutarlari $ isaretiyle ve iki ondalikla yaz (orn. $1,250.00).\n"
            . "- Veride olmayan bir bilgi sorulursa tahmin etme, 'kayitlarda bulunamadi' de.\n"
            . "- Hesap gerektiren sorularda (toplam, fark, ortalama) verilerdeki hazir toplamlari "
            . "kullan; yoksa dikkatlice topla ve nasil hesapladigini tek cumleyle belirt.\n"
            . "- Liste sorularini yanitlarken her kaydi ayri satira yaz.\n"
            . "- Yanit duz metin olsun; markdown baslik/yildiz kullanma.\n\n"
            . 'VERILER: ' . json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

        $provider = AiProviderFactory::make($config);

        return trim($provider->complete($systemPrompt, $question));
    }
}
