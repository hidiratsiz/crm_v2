<?php

use App\Controllers\AppointmentController;
use App\Controllers\AuthController;
use App\Controllers\CalendarController;
use App\Controllers\CustomerController;
use App\Controllers\DashboardController;
use App\Controllers\EstimateController;
use App\Controllers\FinanceController;
use App\Controllers\JobController;
use App\Controllers\ProjectController;
use App\Controllers\QuickCaptureController;
use App\Controllers\ServiceModuleController;
use App\Controllers\UserController;
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

// Estimates / Teklifler (ekleme, duzenleme, silme, durum degistirme, ise donusturme)
$router->get('/estimates/create', [EstimateController::class, 'showCreate'], [AuthMiddleware::class]);
$router->post('/estimates/store', [EstimateController::class, 'store'], [AuthMiddleware::class]);
$router->get('/estimates/edit', [EstimateController::class, 'showEdit'], [AuthMiddleware::class]);
$router->post('/estimates/update', [EstimateController::class, 'update'], [AuthMiddleware::class]);
$router->post('/estimates/status', [EstimateController::class, 'updateStatus'], [AuthMiddleware::class]);
$router->post('/estimates/delete', [EstimateController::class, 'delete'], [AuthMiddleware::class]);
$router->post('/estimates/convert-to-job', [EstimateController::class, 'convertToJob'], [AuthMiddleware::class]);
$router->post('/estimates/send-to-customer', [EstimateController::class, 'sendToCustomer'], [AuthMiddleware::class]);

// Jobs / Isler (calisan atama, gider, kontrol listesi, durum/tarih)
$router->get('/jobs', [JobController::class, 'index'], [AuthMiddleware::class]);
$router->get('/jobs/show', [JobController::class, 'show'], [AuthMiddleware::class]);
$router->post('/jobs/start-date', [JobController::class, 'updateStartDate'], [AuthMiddleware::class]);
$router->post('/jobs/status', [JobController::class, 'updateStatus'], [AuthMiddleware::class]);
$router->post('/jobs/assign-employee', [JobController::class, 'assignEmployee'], [AuthMiddleware::class]);
$router->post('/jobs/unassign-employee', [JobController::class, 'unassignEmployee'], [AuthMiddleware::class]);
$router->post('/jobs/expenses/add', [JobController::class, 'addExpense'], [AuthMiddleware::class]);
$router->post('/jobs/expenses/delete', [JobController::class, 'deleteExpense'], [AuthMiddleware::class]);
$router->post('/jobs/payments/add', [JobController::class, 'addPayment'], [AuthMiddleware::class]);
$router->post('/jobs/payments/delete', [JobController::class, 'deletePayment'], [AuthMiddleware::class]);
$router->post('/jobs/checklist/add', [JobController::class, 'addChecklistItem'], [AuthMiddleware::class]);
$router->post('/jobs/checklist/toggle', [JobController::class, 'toggleChecklistItem'], [AuthMiddleware::class]);
$router->post('/jobs/checklist/delete', [JobController::class, 'deleteChecklistItem'], [AuthMiddleware::class]);

// Randevular / On Gorusme-Inceleme Ziyaretleri (proje/lead uzerinde, henuz
// teklif/is olmadan da planlanabilir)
$router->post('/appointments/store', [AppointmentController::class, 'store'], [AuthMiddleware::class]);
$router->post('/appointments/status', [AppointmentController::class, 'updateStatus'], [AuthMiddleware::class]);
$router->post('/appointments/delete', [AppointmentController::class, 'delete'], [AuthMiddleware::class]);

// Takvim (isler ve randevular burada takvim gorunumunde)
$router->get('/calendar', [CalendarController::class, 'index'], [AuthMiddleware::class]);

// Finans (sirket geneli odeme/gider ozeti ve takibi)
$router->get('/finance', [FinanceController::class, 'index'], [AuthMiddleware::class]);

// Users / Calisanlar (Admin - users.manage yetkisi)
$router->get('/users', [UserController::class, 'index'], [AuthMiddleware::class]);
$router->get('/users/create', [UserController::class, 'showCreate'], [AuthMiddleware::class]);
$router->post('/users/store', [UserController::class, 'store'], [AuthMiddleware::class]);

// Servis Modulleri / Dinamik Fiyatlandirma (Admin - service_modules.manage yetkisi)
$router->get('/service-modules', [ServiceModuleController::class, 'index'], [AuthMiddleware::class]);
$router->get('/service-modules/create', [ServiceModuleController::class, 'showCreate'], [AuthMiddleware::class]);
$router->post('/service-modules/store', [ServiceModuleController::class, 'store'], [AuthMiddleware::class]);
$router->get('/service-modules/edit', [ServiceModuleController::class, 'showEdit'], [AuthMiddleware::class]);
$router->post('/service-modules/update', [ServiceModuleController::class, 'update'], [AuthMiddleware::class]);
$router->post('/service-modules/toggle-active', [ServiceModuleController::class, 'toggleActive'], [AuthMiddleware::class]);
$router->post('/service-modules/delete', [ServiceModuleController::class, 'delete'], [AuthMiddleware::class]);
$router->post('/service-modules/fields/add', [ServiceModuleController::class, 'addField'], [AuthMiddleware::class]);
$router->post('/service-modules/fields/delete', [ServiceModuleController::class, 'deleteField'], [AuthMiddleware::class]);
$router->get('/service-modules/fields-json', [ServiceModuleController::class, 'fieldsJson'], [AuthMiddleware::class]);
$router->get('/service-modules/calculate', [ServiceModuleController::class, 'calculate'], [AuthMiddleware::class]);

// Customers
$router->get('/customers', [CustomerController::class, 'index'], [AuthMiddleware::class]);
$router->get('/customers/create', [CustomerController::class, 'showCreate'], [AuthMiddleware::class]);
$router->post('/customers/store', [CustomerController::class, 'store'], [AuthMiddleware::class]);
$router->get('/customers/edit', [CustomerController::class, 'showEdit'], [AuthMiddleware::class]);
$router->post('/customers/update', [CustomerController::class, 'update'], [AuthMiddleware::class]);
$router->post('/customers/delete', [CustomerController::class, 'delete'], [AuthMiddleware::class]);
