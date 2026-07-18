<?php use App\Core\Csrf; use App\Core\Url; ?>
<h2 class="mb-1">Yeni Teklif Ekle</h2>
<p class="text-muted mb-4">Proje: <?= htmlspecialchars($project['name']) ?></p>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= Url::to('/estimates/store') ?>" class="card p-4 shadow-sm" style="max-width: 600px;">
    <?= Csrf::field() ?>
    <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
    <div class="mb-3">
        <label class="form-label">Teklif Basligi *</label>
        <input type="text" name="title" class="form-control" placeholder="orn. Teklif 3 - Alternatif Malzeme" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Aciklama / Is Kapsami</label>
        <textarea name="description" class="form-control" rows="5"></textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">Tutar ($)</label>
        <input type="text" name="amount" class="form-control" placeholder="orn. 1400">
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Kaydet</button>
        <a href="<?= Url::to('/projects/show') ?>?id=<?= $project['id'] ?>" class="btn btn-outline-secondary">Iptal</a>
    </div>
</form>
