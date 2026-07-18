<?php use App\Core\Csrf; use App\Core\Url; ?>
<h2 class="mb-4">Teklifi Duzenle</h2>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= Url::to('/estimates/update') ?>" class="card p-4 shadow-sm" style="max-width: 600px;">
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= $estimate['id'] ?>">
    <div class="mb-3">
        <label class="form-label">Teklif Basligi *</label>
        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($estimate['title']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Aciklama / Is Kapsami</label>
        <textarea name="description" class="form-control" rows="5"><?= htmlspecialchars($estimate['description'] ?? '') ?></textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">Tutar ($)</label>
        <input type="text" name="amount" class="form-control" value="<?= $estimate['amount'] !== null ? htmlspecialchars((string) $estimate['amount']) : '' ?>">
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Guncelle</button>
        <a href="<?= Url::to('/projects/show') ?>?id=<?= $estimate['project_id'] ?>" class="btn btn-outline-secondary">Iptal</a>
    </div>
</form>
