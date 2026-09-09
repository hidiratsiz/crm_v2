<?php use App\Core\Url; ?>
<h2 class="mb-4">Soru / Yanit</h2>

<div class="card p-3 shadow-sm mb-3 bg-light">
    <small class="text-muted d-block mb-1">Sorunuz</small>
    <div><?= nl2br(htmlspecialchars($question)) ?></div>
</div>

<div class="card p-4 shadow-sm mb-4">
    <small class="text-muted d-block mb-2">Yanit</small>
    <div style="white-space: pre-wrap;"><?= htmlspecialchars($answer) ?></div>
</div>

<a href="<?= Url::to('/quick-capture') ?>" class="btn btn-primary">Yeni Soru / Kayit</a>
