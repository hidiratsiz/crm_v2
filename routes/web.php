<?php

use App\Controllers\AuthController;
use App\Controllers\CustomerController;
use App\Controllers\DashboardController;
use App\Controllers\EstimateController;
use App\Controllers\ProjectController;
use App\Controllers\QuickCaptureController;
use App\Middleware\AuthMiddleware;

/** @var \App\Core\Router $router */

// Public routes
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);

// Root redirects to dashboard (or login if not authed, handled by middleware)
$router->get('/', [DashboardController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);

// Quick Capture (AI destekli hizli kayit kutusu)
$router->get('/quick-capture', [QuickCaptureController::class, 'showForm'], [AuthMiddleware::class]);
$router->post('/quick-capture', [QuickCaptureController::class, 'process'], [AuthMiddleware::class]);

// Projects (teklifler burada gorunur)
$router->get('/projects', [ProjectController::class, 'index'], [AuthMiddleware::class]);
$router->get('/projects/show', [ProjectController::class, 'show'], [AuthMiddleware::class]);

// Estimates / Teklifler (ekleme, duzenleme, silme, durum degistirme)
$router->get('/estimates/create', [EstimateController::class, 'showCreate'], [AuthMiddleware::class]);
$router->post('/estimates/store', [EstimateController::class, 'store'], [AuthMiddleware::class]);
$router->get('/estimates/edit', [EstimateController::class, 'showEdit'], [AuthMiddleware::class]);
$router->post('/estimates/update', [EstimateController::class, 'update'], [AuthMiddleware::class]);
$router->post('/estimates/status', [EstimateController::class, 'updateStatus'], [AuthMiddleware::class]);
$router->post('/estimates/delete', [EstimateController::class, 'delete'], [AuthMiddleware::class]);

// Customers
$router->get('/customers', [CustomerController::class, 'index'], [AuthMiddleware::class]);
$router->get('/customers/create', [CustomerController::class, 'showCreate'], [AuthMiddleware::class]);
$router->post('/customers/store', [CustomerController::class, 'store'], [AuthMiddleware::class]);
$router->get('/customers/edit', [CustomerController::class, 'showEdit'], [AuthMiddleware::class]);
$router->post('/customers/update', [CustomerController::class, 'update'], [AuthMiddleware::class]);
$router->post('/customers/delete', [CustomerController::class, 'delete'], [AuthMiddleware::class]);
