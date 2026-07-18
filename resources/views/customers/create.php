<?php use App\Core\Csrf; use App\Core\Url; ?>
<h2 class="mb-4">Yeni Musteri</h2>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= Url::to('/customers/store') ?>" class="card p-4 shadow-sm" style="max-width: 600px;">
    <?= Csrf::field() ?>
    <div class="mb-3">
        <label class="form-label">Ad Soyad *</label>
        <input type="text" name="name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Telefon</label>
        <input type="text" name="phone" class="form-control">
    </div>
    <div class="mb-3">
        <label class="form-label">E-posta</label>
        <input type="email" name="email" class="form-control">
    </div>
    <div class="mb-3">
        <label class="form-label">Adres</label>
        <input type="text" name="address" class="form-control">
    </div>
    <div class="mb-3">
        <label class="form-label">Notlar</label>
        <textarea name="notes" class="form-control" rows="3"></textarea>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Kaydet</button>
        <a href="<?= Url::to('/customers') ?>" class="btn btn-outline-secondary">Iptal</a>
    </div>
</form>
