<?php use App\Core\Url; ?>
<div class="text-center py-5">
    <h1 class="display-4">403</h1>
    <p class="text-muted">Bu islem icin yetkiniz bulunmuyor.</p>
    <a href="<?= Url::to('/dashboard') ?>" class="btn btn-primary">Dashboard'a Don</a>
</div>
