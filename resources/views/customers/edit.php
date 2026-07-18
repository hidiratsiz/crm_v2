<?php use App\Core\Csrf; use App\Core\Url; ?>
<h2 class="mb-4">Musteriyi Duzenle</h2>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= Url::to('/customers/update') ?>" class="card p-4 shadow-sm" style="max-width: 600px;">
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= $customer['id'] ?>">
    <div class="mb-3">
        <label class="form-label">Ad Soyad *</label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($customer['name']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Telefon</label>
        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">E-posta</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($customer['email'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Adres</label>
        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($customer['address'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Notlar</label>
        <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($customer['notes'] ?? '') ?></textarea>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Guncelle</button>
        <a href="<?= Url::to('/customers') ?>" class="btn btn-outline-secondary">Iptal</a>
    </div>
</form>

<h4 class="mt-5 mb-3">Projeler ve Teklifler</h4>

<?php if (empty($projects)): ?>
    <p class="text-muted">Bu musteriye ait henuz bir proje/teklif yok.</p>
<?php else: ?>
    <div class="list-group" style="max-width: 700px;">
        <?php foreach ($projects as $project): ?>
            <a href="<?= Url::to('/projects/show') ?>?id=<?= $project['id'] ?>"
               class="list-group-item list-group-item-action">
                <div class="d-flex justify-content-between align-items-center">
                    <strong><?= htmlspecialchars($project['name']) ?></strong>
                    <span class="badge bg-secondary"><?= htmlspecialchars($project['status']) ?></span>
                </div>
                <?php if (!empty($project['ai_summary'])): ?>
                    <small class="text-muted"><?= htmlspecialchars($project['ai_summary']) ?></small>
                <?php endif; ?>
                <br><small class="text-muted"><?= htmlspecialchars($project['created_at']) ?></small>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
