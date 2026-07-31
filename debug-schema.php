<?php

// ==========================================================
// JobPro - Gecici Sema Teshis Endpoint (READ-ONLY)
//
// Amac: "payments"/"expenses" tablolarinda beklenen kolonlarin
// (paid_at, expense_date) gercekten var olup olmadigini dogrudan
// veritabanindan kontrol etmek — 500 hatasinin "migration calisti ama
// eski icerikle isaretlendi" ihtimalini kesin olarak dogrulamak/
// elemek icin. Hicbir veri degistirmez, sadece okur.
//
// Islem bitince bu dosyayi silebilirsiniz.
//
// Kullanim:
//   https://yourdomain.com/jobpro/debug-schema.php?token=YOUR_SECRET_TOKEN
// ==========================================================

require __DIR__ . '/bootstrap.php';
/** @var array $config loaded by bootstrap.php */

header('Content-Type: text/plain; charset=utf-8');

$providedToken = $_GET['token'] ?? '';
$expectedToken = $config['app']['migrate_token'] ?? '';

if ($expectedToken === '' || !is_string($providedToken) || $providedToken === '' || !hash_equals($expectedToken, $providedToken)) {
    http_response_code(403);
    echo "Forbidden: gecersiz veya eksik token.\n";
    exit;
}

use App\Core\Database;

try {
    $pdo = Database::connection();

    $tables = ['payments', 'expenses', 'jobs', 'estimates'];
    foreach ($tables as $table) {
        echo "=== {$table} ===\n";
        $stmt = $pdo->prepare(
            'SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t
             ORDER BY ORDINAL_POSITION'
        );
        $stmt->execute(['t' => $table]);
        $cols = $stmt->fetchAll();
        if (empty($cols)) {
            echo "  (tablo bulunamadi)\n";
            continue;
        }
        foreach ($cols as $col) {
            echo "  - {$col['COLUMN_NAME']} ({$col['DATA_TYPE']}, nullable={$col['IS_NULLABLE']})\n";
        }
    }

    echo "\n=== Diskteki migration dosyalari (database/migrations/*.sql) ===\n";
    $files = glob(APP_ROOT . '/database/migrations/*.sql') ?: [];
    sort($files, SORT_STRING);
    foreach ($files as $file) {
        echo '  - ' . basename($file) . ' (' . filesize($file) . ' bytes, degisim: ' . date('Y-m-d H:i:s', filemtime($file)) . ")\n";
    }
    if (empty($files)) {
        echo "  (hic dosya bulunamadi!)\n";
    }

    echo "\n=== Uygulanmis migrationlar ===\n";
    $stmt = $pdo->query('SELECT migration, batch, run_at FROM migrations ORDER BY id DESC LIMIT 15');
    foreach ($stmt->fetchAll() as $row) {
        echo "  - {$row['migration']} (batch {$row['batch']}, {$row['run_at']})\n";
    }

    echo "\n=== job id=3 ===\n";
    $stmt = $pdo->prepare('SELECT * FROM jobs WHERE id = 3');
    $stmt->execute();
    $job = $stmt->fetch();
    if (!$job) {
        echo "  (job id=3 bulunamadi)\n";
    } else {
        foreach ($job as $key => $value) {
            if (is_string($key)) {
                echo "  - {$key}: " . var_export($value, true) . "\n";
            }
        }
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo "HATA: " . $e->getMessage() . "\n";
}
