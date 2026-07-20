<?php
/**
 * JobPro - Application Configuration
 *
 * Bu dosya artik GERCEK sifre/anahtar ICERMEZ ve git'e commit edilmesi
 * guvenlidir. Tum gizli bilgiler (.env) dosyasindan okunur.
 *
 * ILK KURULUM: proje kokunde (bu dosyanin iki dizin ustunde) ".env.example"
 * dosyasini ".env" olarak kopyalayip gercek bilgilerinizi oraya girin.
 */

use App\Core\Env;

Env::load(APP_ROOT . '/.env');

return [
    'app' => [
        'name' => 'JobPro',
        'env' => Env::get('APP_ENV', 'production'),
        'debug' => Env::get('APP_DEBUG', 'false') === 'true',
        'url' => Env::get('APP_URL', 'https://yourdomain.com'), // no trailing slash
        'timezone' => Env::get('APP_TIMEZONE', 'Europe/Istanbul'),
        'session_name' => 'jobpro_session',

        // Secret token that protects migrate.php from random visitors.
        // Generate one with: php -r "echo bin2hex(random_bytes(32));"
        'migrate_token' => Env::get('MIGRATE_TOKEN', 'CHANGE_ME_TO_A_LONG_RANDOM_SECRET'),

        // Calisanlara is atama bildirimi gonderirken kullanilan gonderen bilgisi
        'mail_from_address' => Env::get('MAIL_FROM_ADDRESS', 'no-reply@yourdomain.com'),
        'mail_from_name' => Env::get('MAIL_FROM_NAME', 'JobPro'),
    ],

    'database' => [
        'host' => Env::get('DB_HOST', '127.0.0.1'),
        'port' => Env::get('DB_PORT', '3306'),
        'name' => Env::get('DB_NAME', 'jobpro_db'),
        'user' => Env::get('DB_USER', 'jobpro_user'),
        'pass' => Env::get('DB_PASS', 'CHANGE_ME'),
        'charset' => 'utf8mb4',
    ],

    // Hizli Kayit (AI destekli kutu) hangi AI saglayicisini kullanacak.
    // 'provider' degerini degistirmek disinda baska hicbir kod degisikligi
    // gerekmez — Claude, OpenAI veya OpenAI-uyumlu herhangi bir servis.
    'ai' => [
        'provider' => Env::get('AI_PROVIDER', 'anthropic'), // 'anthropic' | 'openai' | 'openai_compatible'

        'anthropic' => [
            'api_key' => Env::get('ANTHROPIC_API_KEY', 'CHANGE_ME_ANTHROPIC_API_KEY'),
            'model' => Env::get('ANTHROPIC_MODEL', 'claude-haiku-4-5-20251001'),
        ],

        'openai' => [
            'api_key' => Env::get('OPENAI_API_KEY', 'CHANGE_ME_OPENAI_API_KEY'),
            'model' => Env::get('OPENAI_MODEL', 'gpt-4o-mini'),
        ],

        'gemini' => [
            'api_key' => Env::get('GEMINI_API_KEY', 'CHANGE_ME_GEMINI_API_KEY'),
            'model' => Env::get('GEMINI_MODEL', 'gemini-2.5-flash'),
        ],

        // Herhangi bir OpenAI-uyumlu /chat/completions endpoint'i (kendi
        // sunucunuzdaki bir model, baska bir saglayici, vb.)
        'openai_compatible' => [
            'api_key' => Env::get('AI_COMPATIBLE_API_KEY', 'CHANGE_ME'),
            'model' => Env::get('AI_COMPATIBLE_MODEL', ''),
            'api_url' => Env::get('AI_COMPATIBLE_API_URL', ''),
        ],
    ],
];
