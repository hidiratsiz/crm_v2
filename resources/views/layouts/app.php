<?php use App\Core\Auth; use App\Core\Url; ?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JobPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= Url::to('/assets/css/app.css') ?>" rel="stylesheet">
</head>
<body>
<div class="mobile-topbar">
    <button id="sidebar-toggle" class="btn btn-outline-light btn-sm" type="button" aria-label="Menuyu ac/kapat" aria-controls="sidebar" aria-expanded="false">&#9776;</button>
    <span class="fw-bold">JobPro</span>
    <span style="width: 2.5rem;"></span>
</div>
<div class="sidebar-backdrop" id="sidebar-backdrop"></div>
<div class="d-flex" id="app-wrapper">
    <nav class="bg-dark text-white p-3 sidebar" id="sidebar" style="width: 240px; min-height: 100vh;">
        <h4 class="mb-4">JobPro</h4>
        <ul class="nav nav-pills flex-column gap-1">
            <li class="nav-item"><a class="nav-link text-white" href="<?= Url::to('/dashboard') ?>">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link text-white fw-bold" href="<?= Url::to('/quick-capture') ?>">+ Hizli Kayit</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="<?= Url::to('/projects') ?>">Projeler / Teklifler</a></li>
            <li class="nav-item"><a class="nav-link text-white fw-bold" href="<?= Url::to('/jobs') ?>">Isler</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="<?= Url::to('/calendar') ?>">Takvim</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="<?= Url::to('/customers') ?>">Musteriler</a></li>
            <?php if (Auth::can('users.manage')): ?>
                <li class="nav-item"><a class="nav-link text-white" href="<?= Url::to('/users') ?>">Kullanicilar</a></li>
            <?php endif; ?>
            <?php if (Auth::can('service_modules.manage')): ?>
                <li class="nav-item"><a class="nav-link text-white" href="<?= Url::to('/service-modules') ?>">Servisler</a></li>
            <?php endif; ?>
        </ul>
        <hr class="text-secondary">
        <div class="small text-secondary">
            <?= htmlspecialchars($_SESSION['user_name'] ?? '') ?><br>
            <span class="badge bg-secondary"><?= htmlspecialchars(Auth::roleName() ?? '') ?></span>
        </div>
        <form action="<?= Url::to('/logout') ?>" method="post" class="mt-3">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="btn btn-sm btn-outline-light w-100">Cikis Yap</button>
        </form>
    </nav>
    <main class="flex-grow-1 p-4">
        <?= $content ?? '' ?>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= Url::to('/assets/js/app.js') ?>"></script>
</body>
</html>
