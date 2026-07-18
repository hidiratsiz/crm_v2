<?php

namespace App\Services;

use App\Services\Ai\AiProviderFactory;
use RuntimeException;

/**
 * Turns a free-text (typed or voice-dictated) phone note into structured
 * data: customer info, a project/job summary, and one or more estimate
 * ("teklif") options. Provider-agnostic — works with whichever AI provider
 * is configured in config/config.php (ai.provider).
 */
class AiIntakeParser
{
    private const SYSTEM_PROMPT = <<<PROMPT
Sen bir ev hizmetleri isletmesi (boya, tadilat, guverte onarimi, elektrik,
tesisat vb.) icin bir CRM asistanisin. Kullanici iki turden biri bir metin
girecek:

(A) Sadece musteri iletisim bilgisi (isim, adres, telefon, e-posta) — herhangi
    bir is/hizmet tanimlamadan. Ornek: "Yeni musteri ekle: Ahmet Yilmaz,
    05551234567, ahmet@example.com, Kadikoy Istanbul"

(B) Bir telefon gorusmesi sonrasi is/teklif notu — musteri bilgisiyle birlikte
    hangi hizmetin istendigini de anlatir.

ONEMLI AYRIM ORNEGI:
- "Yeni musteri ekle: Ahmet Yilmaz, 05551234567, Kadikoy Istanbul" -> senaryo A
  (sadece isim/telefon/adres var, herhangi bir is/tamir/boya/onarim FIILI
  ANLATILMIYOR). estimates: []
- "63 Grand Valley Bulvari. Musteri adi Leah. Guverte onarimi ve boyama
  istiyor. 3 direk degisecek..." -> senaryo B. Burada bir ADRES OLMASI
  (senaryo A ile karistirilmamali) is tanimini GECERSIZ KILMAZ — metinde
  "onarim", "boyama", "degisecek", "zimparalanacak" gibi bir HIZMET/IS
  FIILI geciyorsa bu HER ZAMAN senaryo B'dir, adres/isim de ayrica
  gecmis olsa bile. service_type, project_title, details doldurulmali ve
  estimates dizisi EN AZ 1 eleman icermelidir.
- Kisacasi: metinde ne yapilacagini anlatan (onarim, tamir, boya, montaj,
  degisim, kurulum vb.) herhangi bir FIIL/HIZMET ifadesi varsa, bu HER
  ZAMAN senaryo B'dir — adres veya musteri bilgisi de ayrica verilmis
  olmasi bunu senaryo A yapmaz.

Metni oku ve SADECE gecerli JSON olarak don — baska hicbir metin, aciklama
veya markdown isareti ekleme:

{
  "customer_name": "musterinin adi, bulunamazsa null",
  "phone": "telefon numarasi (sadece rakamlar/+ isareti), bulunamazsa null",
  "email": "musterinin e-posta adresi, bulunamazsa null",
  "city": "sehir, bulunamazsa null",
  "address": "acik adres, bulunamazsa null",
  "service_type": "kisa hizmet turu ozeti, is/hizmet tanimlanmamissa null",
  "project_title": "kisa, 4-8 kelimelik proje basligi, is/hizmet tanimlanmamissa null",
  "details": "isin genel ozeti, duz metin, is/hizmet tanimlanmamissa null",
  "estimates": [
    {
      "title": "Teklif basligi (orn. 'Teklif 1 - Onarim ve Boyama')",
      "description": "Bu teklif secenegine ozel is kapsami: hangi malzemeler, hangi olculer, hangi islemler yapilacak. Musterinin verdigi olculeri (orn. 2x4, 14x4 feet, 400 sqft) ve adetleri (orn. 3 adet direk/tahta) aynen koru.",
      "amount": "Kullanici bu teklif icin acikca bir tutar/fiyat soylediyse SAYI olarak yaz (orn. 1400.00), para birimi sembolu veya metin ekleme. Tutar belirtilmemisse null."
    }
  ]
}

Kurallar:
- Metin SADECE musteri iletisim bilgisi iceriyorsa (senaryo A): "service_type",
  "project_title", "details" alanlarini null birak, "estimates" dizisini
  BOS ([]) don. Bu durumda sadece musteri kaydi olusturulacak/guncellenecek,
  hicbir is/teklif kaydi acilmayacak.
- Musteri metinde birden fazla farkli secenek/teklif istiyorsa (orn. "2 teklif
  istiyor", "bir de su secenegi", "alternatif olarak..."), "estimates"
  dizisine HER SECENEK ICIN AYRI bir eleman ekle. Ikinci teklif genelde
  birinciye ek/degisiklik iceriyorsa, ikinci teklifin description'ina
  birincinin kapsamini da dahil et (musteri iki ayri teklifi
  karsilastirabilsin).
- Sadece tek bir is/teklif varsa, "estimates" dizisinde SADECE 1 eleman olsun.
- Olculeri, adetleri ve malzeme turlerini (2x4, 14x4 feet, 400 sqft, kac adet
  vb.) uydurmadan, metinde gecen sekliyle aktar.
- Kullanici "teklif X$ / X dolar / X TL vereceğim" gibi acik bir tutar
  soylerse, o tutari ilgili teklifin "amount" alanina SAYI olarak yaz (orn.
  "1400$" -> 1400.00). Tutar birden fazla teklif icin ayri ayri
  belirtilmisse her birine kendi tutarini yaz. Tutar hic belirtilmemisse
  "amount" alanini null birak, tahmin ETME.
- Bir alan metinde acikca belirtilmemisse null yaz, tahmin etme.
- Telefon numarasi verilmemisse "phone" alani null olmali; e-posta
  verilmemisse "email" alani null olmali — hicbirini UYDURMA.
PROMPT;

    /**
     * @return array{
     *   customer_name: ?string, phone: ?string, email: ?string, city: ?string,
     *   address: ?string, service_type: ?string, project_title: ?string,
     *   details: ?string,
     *   estimates: array<array{title: string, description: ?string, amount: ?float}>
     * }
     */
    public static function parse(string $rawText, array $config): array
    {
        $provider = AiProviderFactory::make($config);
        $rawResponse = $provider->complete(self::SYSTEM_PROMPT, $rawText);

        $text = trim($rawResponse);
        // Strip markdown code fences if the model added them despite instructions
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $text);
        $text = trim($text);

        $parsed = json_decode($text, true);

        if (!is_array($parsed)) {
            throw new RuntimeException('AI yaniti anlasilamadi (gecerli JSON degil): ' . substr($text, 0, 300));
        }

        // Normalize/guarantee shape so callers never have to null-check every key
        $parsed['customer_name'] = $parsed['customer_name'] ?? null;
        $parsed['phone'] = $parsed['phone'] ?? null;
        $parsed['email'] = $parsed['email'] ?? null;
        $parsed['city'] = $parsed['city'] ?? null;
        $parsed['address'] = $parsed['address'] ?? null;
        $parsed['service_type'] = $parsed['service_type'] ?? null;
        $parsed['project_title'] = $parsed['project_title'] ?? null;
        $parsed['details'] = $parsed['details'] ?? null;

        // Only fabricate a fallback single estimate when there's actual job
        // content — a pure "add customer" message should NOT create a project.
        $hasJobContent = !empty($parsed['service_type']) || !empty($parsed['details'])
            || (!empty($parsed['estimates']) && is_array($parsed['estimates']));

        // Safety net: if the AI decided there's no job content but the raw
        // text clearly mentions repair/service work, don't silently drop it.
        // This protects against the model being overly conservative about
        // what counts as "just contact info".
        if (!$hasJobContent && self::looksLikeJobText($rawText)) {
            $hasJobContent = true;
            $parsed['details'] = $parsed['details'] ?: trim($rawText);
        }

        if (empty($parsed['estimates']) || !is_array($parsed['estimates'])) {
            $parsed['estimates'] = $hasJobContent ? [[
                'title' => $parsed['project_title'] ?: ($parsed['service_type'] ?: 'Teklif 1'),
                'description' => $parsed['details'] ?? null,
                'amount' => null,
            ]] : [];
        }

        // Normalize each estimate's amount to a clean float (or null) even if
        // the AI slipped a currency symbol/text in despite instructions.
        foreach ($parsed['estimates'] as &$estimate) {
            $estimate['amount'] = self::normalizeAmount($estimate['amount'] ?? null);
        }
        unset($estimate);

        return $parsed;
    }

    private static function normalizeAmount($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        // Defensive fallback: strip anything that isn't a digit, dot, or minus
        $cleaned = preg_replace('/[^0-9.\-]/', '', (string) $value);
        return $cleaned === '' ? null : (float) $cleaned;
    }

    /**
     * Cheap keyword heuristic used only as a safety net when the AI itself
     * decided there's no job content — catches obvious repair/service
     * mentions in Turkish or English so a real work description never gets
     * silently dropped just because the model was overly conservative.
     */
    private static function looksLikeJobText(string $rawText): bool
    {
        $needle = function_exists('mb_strtolower') ? mb_strtolower($rawText, 'UTF-8') : strtolower($rawText);

        $keywords = [
            // Turkish
            'onar', 'tamir', 'boya', 'zimpara', 'değiş', 'degis', 'montaj',
            'kurulum', 'yükselt', 'yukselt', 'tesisat', 'elektrik', 'tadilat',
            'döşe', 'dose', 'söküm', 'sokum',
            // English
            'repair', 'paint', 'sand', 'stain', 'replace', 'install',
            'renovation', 'fix', 'plumbing', 'wiring', 'deck', 'roof',
        ];

        foreach ($keywords as $keyword) {
            $found = function_exists('mb_strpos')
                ? mb_strpos($needle, $keyword) !== false
                : strpos($needle, $keyword) !== false;
            if ($found) {
                return true;
            }
        }

        return false;
    }
}
