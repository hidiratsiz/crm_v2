<?php use App\Core\Url; ?>
<h2 class="mb-4">Komut Sonucu</h2>

<div class="alert <?= $success ? 'alert-success' : 'alert-warning' ?>">
    <?= htmlspecialchars($message) ?>
</div>

<?php if ($job): ?>
    <a href="<?= Url::to('/jobs/show') ?>?id=<?= $job['id'] ?>" class="btn btn-primary">Isi Goruntule</a>
<?php endif; ?>
<?php if (!empty($project)): ?>
    <a href="<?= Url::to('/projects/show') ?>?id=<?= $project['id'] ?>" class="btn btn-primary">Projeyi/Teklifi Goruntule</a>
<?php endif; ?>
<a href="<?= Url::to('/quick-capture') ?>" class="btn btn-outline-secondary">Yeni Kayit / Komut</a>
