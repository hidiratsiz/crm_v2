<?php

// ==========================================================
// JobPro - Migration Endpoint
//
// Purpose: on shared hosting without SSH access, GitHub Actions can't
// run a CLI command after deploying files. Instead, after copying the
// files, the workflow makes a single HTTP request to this endpoint,
// which checks the database and creates/updates any missing or
// outdated tables automatically.
//
// Protected by a secret token (config/config.php -> app.migrate_token)
// so random visitors can't trigger it. Compare with hash_equals to
// avoid timing attacks.
//
// Usage:
//   https://yourdomain.com/jobpro/migrate.php?token=YOUR_SECRET_TOKEN
// ==========================================================

require __DIR__ . '/bootstrap.php';
/** @var array $config loaded by bootstrap.php */

header('Content-Type: text/plain; charset=utf-8');

$providedToken = $_GET['token'] ?? ($_SERVER['HTTP_X_MIGRATE_TOKEN'] ?? '');
$expectedToken = $config['app']['migrate_token'] ?? '';

if ($expectedToken === '' || $expectedToken === 'CHANGE_ME_TO_A_LONG_RANDOM_SECRET') {
    http_response_code(500);
    echo "HATA: config/config.php icinde 'migrate_token' ayarlanmamis.\n";
    echo "Once uzun, rastgele bir sifre belirleyin, sonra tekrar deneyin.\n";
    exit;
}

if (!is_string($providedToken) || $providedToken === '' || !hash_equals($expectedToken, $providedToken)) {
    http_response_code(403);
    echo "Forbidden: gecersiz veya eksik migrate token.\n";
    exit;
}

use App\Core\Migrator;

try {
    $migrator = new Migrator(APP_ROOT . '/database/migrations');

    // ?status=1 just reports applied/pending migrations without running anything
    if (($_GET['status'] ?? '') === '1') {
        $status = $migrator->status();
        echo "Uygulanmis migration sayisi: " . count($status['applied']) . "\n";
        echo "Bekleyen migration sayisi: " . count($status['pending']) . "\n";
        foreach ($status['pending'] as $p) {
            echo " - bekliyor: {$p}\n";
        }
        exit;
    }

    $result = $migrator->run();
    echo "OK\n";
    echo $result['message'] . "\n";
    foreach ($result['applied'] as $name) {
        echo " - uygulandi: {$name}\n";
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo "HATA: " . $e->getMessage() . "\n";
    error_log('Migration error: ' . $e->getMessage());
}
