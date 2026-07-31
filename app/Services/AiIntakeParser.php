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
tesisat vb.) icin bir CRM asistanisin. Kullanicinin girdigi metin IKI ANA
TURDEN birine girer — once hangisi oldugunu belirle ("intent" alani):

ONCELIK KURALI (her seyden once kontrol et): Metin bir musteri adiyla
birlikte "teklifini duzenle", "teklifi duzenle", "teklifini guncelle",
"teklifi guncelle" gibi MEVCUT bir teklifi DUZENLEME/GUNCELLEME komutuyla
BASLIYORSA, bu HER ZAMAN TUR 2 "update_estimate"dir — ardindan gelen is
kapsami aciklamasi ne kadar uzun/detayli olursa olsun, ne kadar cok onarim/
tamir/boya gibi HIZMET FIILI icerirse icersin, bunu senaryo B (new_capture)
YAPMAZ. Asagidaki TUR 1 senaryo B kurali SADECE metin boyle bir "duzenle/
guncelle" komutuyla BASLAMIYORSA gecerlidir. Ornek: "davit K teklifini
duzenle" ile baslayip ardindan uzun bir is kapsami metni ve tutar gelmesi
-> intent MUTLAKA "update_estimate", ASLA "new_capture" degil.

Benzer sekilde, metin bir musteri adiyla birlikte "teklifi gonder",
"teklifini gonder", "teklifi mailat/eposta at" gibi bir GONDERME
komutuyla BASLIYORSA, bu HER ZAMAN TUR 2 "send_estimate"dir.

======================================================================
TUR 1 — "new_capture": Yeni musteri/is/teklif bilgisi
======================================================================
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

(C) Senaryo B ile birlikte (veya bazen tek basina) bir ON GORUSME/INCELEME/
    OLCUM RANDEVUSU da soylenmis olabilir — henuz teklif/fiyat verilmeden
    once "gidip bakacagiz", "incelemeye gidiyoruz", "olcum alacagiz" gibi bir
    saha ziyareti. Ornek: "Deck restoration, 6000 Bradgate Thornhill, David
    K, dk@rogers.com, 416 407 0110. Bu musteriyle bugun saat 14:30'da deck
    incelemeye gitme programi yaptik." Burada hem bir is tanimi (deck
    restoration -> senaryo B, estimates doldurulur) HEM DE bir randevu
    (bugun 14:30, inceleme) var — ikisi ayni yanitta birlikte doldurulur:
    appointment_date/appointment_time/appointment_notes VE service_type/
    estimates ayni anda dolu olabilir, biri digerini disaramaz.

======================================================================
TUR 2 — Mevcut bir is/musteri uzerinde KOMUT (yeni kayit degil)
======================================================================
Kullanici zaten sistemde olan bir musterinin isiyle ilgili bir islem
istiyorsa, bunlardan birini sec:

- "assign_employee": Bir calisani bir musterinin isine atama komutu.
  Ornek: "Jane'in isine Alex gitsin", "Alex'i Jane'in isine ata",
  "Jane'in projesine Ahmet'i gonder"
  -> target_customer_name: "Jane", employee_name: "Alex"

- "unassign_employee": Bir calisani bir isten kaldirma komutu.
  Ornek: "Alex'i Jane'in isinden cikar"
  -> target_customer_name: "Jane", employee_name: "Alex"

- "add_expense": Bir ise gider ekleme komutu.
  Ornek: "Jane'in isine 200 dolar malzeme gideri ekle"
  -> target_customer_name: "Jane", expense_category: "Malzeme",
     expense_description: (varsa detay), expense_amount: 200.00

- "add_checklist_item": Bir ise yapilacak bir adim ekleme komutu.
  Ornek: "Jane'in isine 'eski dolaplari sok' adimini ekle"
  -> target_customer_name: "Jane", checklist_description: "Eski dolaplari sok"

- "set_start_date": Bir isin baslangic tarihini (ve varsa saatini/suresini) belirleme komutu.
  Ornek: "Jane'in isi 1 Agustos'ta baslasin"
  -> target_customer_name: "Jane", start_date: "2026-08-01" (YYYY-MM-DD formatinda)
  Ornek (saatlik is): "Jane'in isi 1 Agustos saat 14:00'te baslasin, 2 saat surecek"
  -> target_customer_name: "Jane", start_date: "2026-08-01", start_time: "14:00", duration_hours: 2

- "change_job_status": Bir isin durumunu degistirme komutu.
  Ornek: "Jane'in isi tamamlandi", "Jane'in isini iptal et"
  -> target_customer_name: "Jane",
     new_status: "pending_schedule" | "scheduled" | "in_progress" | "completed" | "cancelled"

- "update_estimate": Bir musterinin TASLAK teklifini (henuz musteriye
  gonderilmemis/kabul edilmemis) yeni bir is kapsami aciklamasi ve/veya
  tutarla guncelleme komutu. Genelde saha ziyareti/inceleme sonrasi
  kullanilir — once musterinin adi/teklifi duzenleme komutu, ardindan
  YENI is kapsami aciklamasi (olculer, yapilacak isler, malzemeler) ve
  genelde bir tutar gelir.
  Ornek: "David K teklifini duzenle" + ardindan tum is kapsami metni ve
  "$2,950" gibi bir tutar.
  -> target_customer_name: "David K"
  -> estimate_description: is kapsaminin TAMAMI, metinde gectigi sekliyle
     (maddeler/olculer/yapilacak isler UYDURULMADAN, oldugu gibi aktarilir;
     madde isaretli bir liste varsa her maddeyi ayri bir satirda "- " ile
     basla)
  -> estimate_amount: metinde acikca bir tutar varsa SAYI olarak (orn.
     "$2,950" -> 2950.00), yoksa null

- "send_estimate": Hazirlanmis bir teklifi musteriye e-posta ile gonderme
  komutu (teklifin kendisini degil, sadece GONDERME islemini tetikler).
  Ornek: "David K'ye teklifi gonder", "Teklifi musteriye ilet",
  "Son teklifi David K'ye mailat"
  -> target_customer_name: "David K"

Bu komutlarin HEPSINDE "target_customer_name" alani, komutun HANGI
MUSTERININ/ISININ uzerinde oldugunu belirtir (musteri adi veya varsa is
basligi olabilir). Emin degilsen metinde gecen ismi aynen yaz, tahmin
etme.

======================================================================
YANIT FORMATI
======================================================================
Metni oku ve SADECE gecerli JSON olarak don — baska hicbir metin, aciklama
veya markdown isareti ekleme:

{
  "intent": "new_capture" | "assign_employee" | "unassign_employee" | "add_expense" | "add_checklist_item" | "set_start_date" | "change_job_status" | "update_estimate" | "send_estimate",

  "customer_name": "musterinin adi, bulunamazsa null (sadece new_capture icin)",
  "phone": "telefon numarasi (sadece rakamlar/+ isareti), bulunamazsa null",
  "email": "musterinin e-posta adresi, bulunamazsa null",
  "city": "sehir, bulunamazsa null",
  "address": "acik adres, bulunamazsa null",
  "service_type": "kisa hizmet turu ozeti, is/hizmet tanimlanmamissa null",
  "project_title": "kisa, 4-8 kelimelik proje basligi, is/hizmet tanimlanmamissa null",
  "details": "isin genel ozeti, duz metin, is/hizmet tanimlanmamissa null",
  "appointment_date": "TUR 1 icin: musteriyle bir ON GORUSME/INCELEME/OLCUM randevusu/saha ziyareti planlaniyorsa, YYYY-MM-DD formatinda MUTLAK tarih (metindeki 'bugun', 'yarin' gibi goreceli ifadeleri en basta verilen 'bugunun tarihi'ne gore MUTLAKA cevir), yoksa null",
  "appointment_time": "TUR 1 icin: randevunun saati HH:MM formatinda (metinde acikca bir saat soylenmisse), yoksa null",
  "appointment_notes": "TUR 1 icin: randevuyla ilgili kisa bir not (orn. 'Deck inceleme'), yoksa null",
  "estimates": [
    {
      "title": "Teklif basligi (orn. 'Teklif 1 - Onarim ve Boyama')",
      "description": "Bu teklif secenegine ozel is kapsami: hangi malzemeler, hangi olculer, hangi islemler yapilacak. Musterinin verdigi olculeri (orn. 2x4, 14x4 feet, 400 sqft) ve adetleri (orn. 3 adet direk/tahta) aynen koru.",
      "amount": "Kullanici bu teklif icin acikca bir tutar/fiyat soylediyse SAYI olarak yaz (orn. 1400.00), para birimi sembolu veya metin ekleme. Tutar belirtilmemisse null."
    }
  ],

  "target_customer_name": "TUR 2 komutlari icin: hangi musterinin/isinin isi, bulunamazsa null",
  "employee_name": "TUR 2 icin (assign/unassign_employee): calisan adi, yoksa null",
  "expense_category": "TUR 2 icin (add_expense): gider kategorisi, yoksa null",
  "expense_description": "TUR 2 icin (add_expense): gider aciklamasi, yoksa null",
  "expense_amount": "TUR 2 icin (add_expense): SAYI olarak tutar, yoksa null",
  "checklist_description": "TUR 2 icin (add_checklist_item): adim aciklamasi, yoksa null",
  "start_date": "TUR 2 icin (set_start_date): YYYY-MM-DD formatinda tarih, yoksa null",
  "start_time": "TUR 2 icin (set_start_date): HH:MM formatinda saat (sadece is saatli/randevu tarzinda ise), yoksa null",
  "duration_hours": "TUR 2 icin (set_start_date): isin kac saat surecegi, SAYI olarak (orn. 2 veya 1.5), belirtilmemisse null",
  "new_status": "TUR 2 icin (change_job_status): pending_schedule|scheduled|in_progress|completed|cancelled, yoksa null",
  "estimate_description": "TUR 2 icin (update_estimate): yeni is kapsami aciklamasinin TAMAMI, yoksa null",
  "estimate_amount": "TUR 2 icin (update_estimate): SAYI olarak yeni tutar, belirtilmemisse null"
}

Kurallar:
- intent = "new_capture" DEGILSE, "customer_name", "estimates" vb. new_capture
  alanlarini null/bos birak — sadece ilgili TUR 2 alanlarini doldur.
- intent = "new_capture" ISE, TUR 2 alanlarinin hepsini null birak.
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
- appointment_date/appointment_time/appointment_notes, estimates alanindan
  BAGIMSIZDIR: bir randevu soylenmisse doldurulur, is/teklif tanimlanmamis
  olsa (senaryo A gibi) bile bir saha ziyareti ayri ayri soylenmis olabilir.
  Randevu soylenmemisse appointment_date/time/notes'un HEPSI null olmali.
PROMPT;

    /**
     * @return array{
     *   intent: string,
     *   customer_name: ?string, phone: ?string, email: ?string, city: ?string,
     *   address: ?string, service_type: ?string, project_title: ?string,
     *   details: ?string, appointment_date: ?string, appointment_time: ?string,
     *   appointment_notes: ?string,
     *   estimates: array<array{title: string, description: ?string, amount: ?float}>,
     *   target_customer_name: ?string, employee_name: ?string,
     *   expense_category: ?string, expense_description: ?string, expense_amount: ?float,
     *   checklist_description: ?string, start_date: ?string, new_status: ?string,
     *   estimate_description: ?string, estimate_amount: ?float
     * }
     */
    public static function parse(string $rawText, array $config): array
    {
        $provider = AiProviderFactory::make($config);
        // The AI model has no innate sense of "today" — without this, "bugun"/
        // "yarin" (today/tomorrow) in appointment or start_date phrases can't
        // be turned into an absolute date. Prepended fresh on every call so
        // it's always accurate regardless of when the request happens.
        $systemPrompt = self::dateContextHeader() . "\n\n" . self::SYSTEM_PROMPT;
        $rawResponse = $provider->complete($systemPrompt, $rawText);

        $text = trim($rawResponse);
        // Strip markdown code fences if the model added them despite instructions
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $text);
        $text = trim($text);

        $parsed = json_decode($text, true);

        if (!is_array($parsed)) {
            throw new RuntimeException('AI yaniti anlasilamadi (gecerli JSON degil): ' . substr($text, 0, 300));
        }

        // Unknown/missing intent defaults to new_capture — the safest fallback,
        // since that path never mutates existing data (it only creates).
        $validIntents = [
            'new_capture', 'assign_employee', 'unassign_employee', 'add_expense',
            'add_checklist_item', 'set_start_date', 'change_job_status',
            'update_estimate', 'send_estimate',
        ];
        $intent = $parsed['intent'] ?? 'new_capture';
        $parsed['intent'] = in_array($intent, $validIntents, true) ? $intent : 'new_capture';

        // Command-mode fields (Tur 2) — always normalized regardless of intent
        $parsed['target_customer_name'] = $parsed['target_customer_name'] ?? null;
        $parsed['employee_name'] = $parsed['employee_name'] ?? null;
        $parsed['expense_category'] = $parsed['expense_category'] ?? null;
        $parsed['expense_description'] = $parsed['expense_description'] ?? null;
        $parsed['expense_amount'] = self::normalizeAmount($parsed['expense_amount'] ?? null);
        $parsed['checklist_description'] = $parsed['checklist_description'] ?? null;
        $parsed['start_date'] = self::normalizeDate($parsed['start_date'] ?? null);
        $parsed['start_time'] = self::normalizeTime($parsed['start_time'] ?? null);
        $parsed['duration_hours'] = self::normalizeAmount($parsed['duration_hours'] ?? null);
        $validStatuses = ['pending_schedule', 'scheduled', 'in_progress', 'completed', 'cancelled'];
        $parsed['new_status'] = in_array($parsed['new_status'] ?? null, $validStatuses, true) ? $parsed['new_status'] : null;
        $parsed['estimate_description'] = $parsed['estimate_description'] ?? null;
        $parsed['estimate_amount'] = self::normalizeAmount($parsed['estimate_amount'] ?? null);

        // new_capture fields (Tur 1) — always normalized so views/controller
        // never have to null-check every key, even for command-mode intents.
        $parsed['customer_name'] = $parsed['customer_name'] ?? null;
        $parsed['phone'] = $parsed['phone'] ?? null;
        $parsed['email'] = $parsed['email'] ?? null;
        $parsed['city'] = $parsed['city'] ?? null;
        $parsed['address'] = $parsed['address'] ?? null;
        $parsed['service_type'] = $parsed['service_type'] ?? null;
        $parsed['project_title'] = $parsed['project_title'] ?? null;
        $parsed['details'] = $parsed['details'] ?? null;
        $parsed['appointment_date'] = self::normalizeDate($parsed['appointment_date'] ?? null);
        $parsed['appointment_time'] = self::normalizeTime($parsed['appointment_time'] ?? null);
        $parsed['appointment_notes'] = $parsed['appointment_notes'] ?? null;

        // The estimates-fallback / keyword safety-net logic only makes sense
        // for new_capture — command-mode intents never create projects.
        if ($parsed['intent'] !== 'new_capture') {
            $parsed['estimates'] = [];
            return $parsed;
        }

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

    /**
     * A short, always-fresh date-context line prepended to the system
     * prompt so the model can resolve relative date words ("bugun",
     * "yarin", "bu hafta cuma" etc.) into an absolute YYYY-MM-DD instead of
     * guessing or passing the relative phrase through untouched.
     */
    private static function dateContextHeader(): string
    {
        $dayNamesTr = ['Pazartesi', 'Sali', 'Carsamba', 'Persembe', 'Cuma', 'Cumartesi', 'Pazar'];
        $dayName = $dayNamesTr[((int) date('N')) - 1];

        return 'Bugunun tarihi: ' . date('Y-m-d') . " ({$dayName}). Metinde 'bugun', 'yarin', "
            . "'obur gun', 'bu hafta cuma' gibi GORECELI tarih ifadeleri gecerse, bunlari bu "
            . 'tarihe gore MUTLAK bir YYYY-MM-DD tarihine cevirerek yaz — asla goreceli ifadeyi '
            . 'oldugu gibi birakma veya tahmin etme, sadece bu tarihe gore hesapla.';
    }

    private static function normalizeDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        $timestamp = strtotime((string) $value);
        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }

    private static function normalizeTime($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        // Accept "14:00", "2:00 PM", etc. — strtotime handles common formats.
        $timestamp = strtotime((string) $value);
        return $timestamp === false ? null : date('H:i', $timestamp);
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
