<?php

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Lightweight migration runner (no Composer/Artisan needed).
 *
 * How it works:
 * - Keeps a `migrations` table listing every migration file already applied.
 * - Scans database/migrations/*.sql in filename order.
 * - Runs any file not yet listed, then records it.
 * - Safe to run on every deploy: on a fresh database it creates everything
 *   from scratch; on an existing database it only applies new files.
 *
 * To add a schema change later, just add a new numbered file, e.g.
 * database/migrations/012_add_phone_to_projects.sql — it will be picked
 * up and applied automatically the next time migrate.php runs.
 */
class Migrator
{
    private PDO $pdo;
    private string $migrationsPath;

    public function __construct(string $migrationsPath)
    {
        $config = require APP_ROOT . '/config/config.php';
        $db = $config['database'];

        $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}";

        try {
            // A dedicated connection with multi-statement support, since some
            // seed migrations contain several INSERT statements in one file.
            // This is only ever used to run trusted, developer-authored
            // migration files — never user input — so it's safe here.
            $this->pdo = new PDO($dsn, $db['user'], $db['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('Veritabanina baglanilamadi: ' . $e->getMessage());
        }

        $this->migrationsPath = rtrim($migrationsPath, '/');
        $this->ensureMigrationsTable();
    }

    private function ensureMigrationsTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                batch INT UNSIGNED NOT NULL,
                run_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    /**
     * @return string[] Absolute paths of migration files not yet applied, in order.
     */
    public function pendingMigrations(): array
    {
        $applied = $this->pdo->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
        $files = glob($this->migrationsPath . '/*.sql') ?: [];
        sort($files, SORT_STRING);

        return array_values(array_filter($files, function ($file) use ($applied) {
            return !in_array(basename($file), $applied, true);
        }));
    }

    /**
     * Applies every pending migration in order. Stops and throws on the
     * first failure so a broken migration never leaves a partial state
     * silently marked as "applied".
     *
     * @return array{applied: string[], message: string}
     */
    public function run(): array
    {
        $pending = $this->pendingMigrations();

        if (empty($pending)) {
            return ['applied' => [], 'message' => 'Guncel: bekleyen migration yok.'];
        }

        $batchRow = $this->pdo->query('SELECT COALESCE(MAX(batch), 0) + 1 AS b FROM migrations')->fetch();
        $batch = (int) $batchRow['b'];

        $applied = [];
        foreach ($pending as $file) {
            $name = basename($file);
            $sql = file_get_contents($file);

            if ($sql === false || trim($sql) === '') {
                continue;
            }

            try {
                $this->pdo->exec($sql);
                $stmt = $this->pdo->prepare('INSERT INTO migrations (migration, batch) VALUES (:m, :b)');
                $stmt->execute(['m' => $name, 'b' => $batch]);
                $applied[] = $name;
            } catch (PDOException $e) {
                throw new RuntimeException("Migration basarisiz oldu: {$name} -> " . $e->getMessage());
            }
        }

        return ['applied' => $applied, 'message' => count($applied) . ' migration basariyla uygulandi.'];
    }

    /**
     * @return array{applied: array, pending: string[]}
     */
    public function status(): array
    {
        $applied = $this->pdo->query('SELECT migration, batch, run_at FROM migrations ORDER BY id')->fetchAll();
        $pending = array_map('basename', $this->pendingMigrations());

        return ['applied' => $applied, 'pending' => $pending];
    }
}
