<?php use App\Core\Csrf; use App\Core\Url; ?>
<h2 class="mb-4">Yeni Kullanici / Calisan Ekle</h2>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= Url::to('/users/store') ?>" class="card p-4 shadow-sm" style="max-width: 500px;">
    <?= Csrf::field() ?>
    <div class="mb-3">
        <label class="form-label">Ad Soyad *</label>
        <input type="text" name="name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">E-posta *</label>
        <input type="email" name="email" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Sifre * (en az 8 karakter)</label>
        <input type="password" name="password" class="form-control" minlength="8" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Rol *</label>
        <select name="role_id" class="form-select" required>
            <?php foreach ($roles as $role): ?>
                <option value="<?= $role['id'] ?>" <?= $role['name'] === 'Employee' ? 'selected' : '' ?>>
                    <?= htmlspecialchars($role['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Olustur</button>
        <a href="<?= Url::to('/users') ?>" class="btn btn-outline-secondary">Iptal</a>
    </div>
</form>
