<?php

// ==========================================================
// JobPro - Front Controller (single entry point for web requests)
// ==========================================================

require __DIR__ . '/bootstrap.php';
/** @var array $config loaded by bootstrap.php */

// ---- Secure session configuration ----
session_name($config['app']['session_name']);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    // 'secure' => true, // uncomment once your domain has HTTPS enabled
]);
session_start();

// ----------------------------------------------------------
// Auto-detect subdirectory base path (e.g. "/jobpro" when the app
// lives at https://example.com/jobpro/ instead of the domain root).
// This lets every link, form action, and redirect in the app work
// correctly no matter where it's installed — no manual config needed.
// ----------------------------------------------------------
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath = ($scriptDir === '/' || $scriptDir === '\\') ? '' : rtrim($scriptDir, '/');
define('BASE_URL', $basePath);

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if ($basePath !== '' && strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
    if ($requestUri === '' || $requestUri === false) {
        $requestUri = '/';
    }
}

// ---- Routing ----
use App\Core\Router;

$router = new Router();
require APP_ROOT . '/routes/web.php';

$router->dispatch($_SERVER['REQUEST_METHOD'], $requestUri);
