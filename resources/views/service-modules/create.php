<?php use App\Core\Csrf; use App\Core\Url; ?>
<h2 class="mb-4">Yeni Servis Olustur</h2>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= Url::to('/service-modules/store') ?>" class="card p-4 shadow-sm" style="max-width: 600px;">
    <?= Csrf::field() ?>
    <div class="mb-3">
        <label class="form-label">Servis Adi *</label>
        <input type="text" name="name" class="form-control" placeholder="orn. Guverte Onarimi" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Aciklama</label>
        <textarea name="description" class="form-control" rows="2"></textarea>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Olustur ve Alan Ekle</button>
        <a href="<?= Url::to('/service-modules') ?>" class="btn btn-outline-secondary">Iptal</a>
    </div>
</form>
