<?php

// ==========================================================
// JobPro - Data Reset Endpoint (TEK KULLANIMLIK / GERI ALINAMAZ)
//
// Amac: test/demo asamasinda eklenen TUM musteri, proje, teklif, is,
// randevu, aktivite ve servis modulu verilerini siler; sadece
// belirttiginiz TEK bir kullanicinin giris hesabini korur. roles/
// permissions/role_permissions tablolarina DOKUNULMAZ (bunlar veri
// degil, sistem yapilandirmasidir ve silinirse hicbir hesap giris
// yapamaz hale gelir).
//
// GERI ALINAMAZ. Sadece bir kereligine, gercek musterilerle calismaya
// baslamadan once calistirmak icin.
//
// Guvenlik: migrate.php ile ayni token (config/config.php ->
// app.migrate_token) ARTI ayrica bir onay ifadesi ve korunacak
// kullanicinin e-postasi gerektirir; boylece token'i bilen biri bile
// yanlislikla/otomatik olarak tetikleyemez.
//
// Kullanim:
//   https://yourdomain.com/jobpro/reset-data.php
//     ?token=YOUR_SECRET_TOKEN
//     &confirm=HEPSINI-SIL
//     &keep_user_email=admin@jobpro.local
// ==========================================================

require __DIR__ . '/bootstrap.php';
/** @var array $config loaded by bootstrap.php */

header('Content-Type: text/plain; charset=utf-8');

$providedToken = $_GET['token'] ?? ($_SERVER['HTTP_X_MIGRATE_TOKEN'] ?? '');
$expectedToken = $config['app']['migrate_token'] ?? '';

if ($expectedToken === '' || $expectedToken === 'CHANGE_ME_TO_A_LONG_RANDOM_SECRET') {
    http_response_code(500);
    echo "HATA: config/config.php icinde 'migrate_token' ayarlanmamis.\n";
    exit;
}

if (!is_string($providedToken) || $providedToken === '' || !hash_equals($expectedToken, $providedToken)) {
    http_response_code(403);
    echo "Forbidden: gecersiz veya eksik token.\n";
    exit;
}

$confirm = $_GET['confirm'] ?? '';
if ($confirm !== 'HEPSINI-SIL') {
    http_response_code(400);
    echo "Bu islem GERI ALINAMAZ ve tum musteri/proje/teklif/is/randevu/servis\n";
    echo "modulu verilerini siler. Emin iseniz URL'ye &confirm=HEPSINI-SIL ekleyin.\n";
    exit;
}

$keepEmail = trim((string) ($_GET['keep_user_email'] ?? ''));
if ($keepEmail === '') {
    http_response_code(400);
    echo "HATA: Hangi kullanici hesabinin korunacagini belirtmelisiniz.\n";
    echo "Ornek: &keep_user_email=admin@jobpro.local\n";
    exit;
}

use App\Core\Database;

try {
    $pdo = Database::connection();

    $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE email = :email AND deleted_at IS NULL');
    $stmt->execute(['email' => $keepEmail]);
    $keepUser = $stmt->fetch();

    if (!$keepUser) {
        http_response_code(400);
        echo "HATA: '{$keepEmail}' e-postali aktif bir kullanici bulunamadi.\n";
        echo "Hicbir sey silinmedi. Dogru e-postayi kontrol edin.\n";
        exit;
    }

    // Data tables only — roles/permissions/role_permissions left untouched
    // on purpose so login/yetki sistemi calismaya devam eder.
    $dataTables = [
        'estimate_field_values',
        'estimates',
        'job_checklist_items',
        'expenses',
        'job_employees',
        'jobs',
        'appointments',
        'activity_logs',
        'projects',
        'customers',
        'service_module_fields',
        'service_modules',
    ];

    $pdo->beginTransaction();
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

    foreach ($dataTables as $table) {
        $pdo->exec("DELETE FROM {$table}");
        $pdo->exec("ALTER TABLE {$table} AUTO_INCREMENT = 1");
    }

    $deleteUsers = $pdo->prepare('DELETE FROM users WHERE email <> :email');
    $deleteUsers->execute(['email' => $keepEmail]);
    $deletedUserCount = $deleteUsers->rowCount();

    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    $pdo->commit();

    echo "OK - Veritabani sifirlandi.\n";
    echo "Korunan hesap: {$keepUser['name']} <{$keepUser['email']}>\n";
    echo "Silinen diger kullanici hesabi sayisi: {$deletedUserCount}\n";
    echo "Temizlenen tablolar: " . implode(', ', $dataTables) . "\n";
    echo "\nArtik gercek musterilerinizi eklemeye baslayabilirsiniz.\n";
} catch (\Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo "HATA: " . $e->getMessage() . "\n";
    error_log('Reset-data error: ' . $e->getMessage());
}
