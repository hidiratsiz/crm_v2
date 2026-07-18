<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../../config/config.php';
            $db = $config['database'];

            $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}";

            try {
                self::$instance = new PDO($dsn, $db['user'], $db['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                // Always log the real error for diagnosis (check storage/logs or
                // your hosting's PHP error log / cPanel "Errors" section).
                error_log('DB Connection Error: ' . $e->getMessage());
                http_response_code(500);

                if ($config['app']['debug'] ?? false) {
                    // Debug mode: show the real error to help pinpoint the cause.
                    die('Veritabani baglantisi kurulamadi: ' . $e->getMessage() .
                        "\n\nKontrol edin: proje kokundeki .env dosyasi (DB_HOST, DB_NAME, DB_USER, DB_PASS) " .
                        "gercek hosting bilgilerinizle eslesiyor mu?");
                }

                // Production: generic message, no leaked details.
                die('Veritabani baglantisi kurulamadi. Lutfen proje kokundeki .env dosyasini kontrol edin.');
            }
        }

        return self::$instance;
    }
}
