<?php

// ==========================================================
// JobPro - Shared Bootstrap
// Included by both index.php (web requests) and migrate.php
// (deployment migrations). Keeping this logic in one place means
// both entry points always agree on where app/config/routes live.
// ==========================================================

// ----------------------------------------------------------
// APP_ROOT: absolute path to the folder that contains
// app/, config/, routes/, resources/, database/, storage/
//
// DEFAULT (works out of the box, no configuration needed): this
// file's own directory. The whole project — index.php, app/,
// config/, everything — is designed to be uploaded as ONE self-
// contained folder to any path (domain root, /jobpro, /crm,
// /projeler/musteri1, anywhere) and just work immediately. The
// sensitive folders (app/, config/, routes/, resources/,
// database/, storage/) each carry their own .htaccess that blocks
// direct browser access, so nothing needs to live outside this
// folder or outside your site's document root.
//
// ADVANCED / OPTIONAL: if you specifically want app/, config/, etc.
// physically stored in a separate location outside the web-served
// folder (extra defense-in-depth on hosts where you control the
// document root), create app-root.php (copy app-root.example.php)
// next to this file and return the absolute path there instead.
// GitHub Actions can generate this automatically from the
// APP_ROOT_PATH secret when that pattern is used; leave the secret
// empty to use the simple single-folder default above.
// ----------------------------------------------------------
$appRootFile = __DIR__ . '/app-root.php';
$appRootOverride = file_exists($appRootFile) ? require $appRootFile : null;
define('APP_ROOT', $appRootOverride ?: __DIR__);

// ---- Simple PSR-4-style autoloader (no Composer required) ----
// Registered BEFORE config.php loads, since config.php now uses
// App\Core\Env to read secrets from .env.
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = APP_ROOT . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($path)) {
        require $path;
    }
});

$config = require APP_ROOT . '/config/config.php';

// Error display based on environment
if ($config['app']['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

date_default_timezone_set($config['app']['timezone']);
