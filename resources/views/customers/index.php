<?php use App\Core\Auth; use App\Core\Csrf; use App\Core\Url; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Musteriler</h2>
    <?php if (Auth::can('customers.create')): ?>
        <a href="<?= Url::to('/customers/create') ?>" class="btn btn-primary">+ Yeni Musteri</a>
    <?php endif; ?>
</div>

<form method="get" action="<?= Url::to('/customers') ?>" class="mb-3">
    <div class="input-group" style="max-width: 400px;">
        <input type="text" name="q" class="form-control" placeholder="Isim, telefon, e-posta veya adres ara..."
               value="<?= htmlspecialchars($search ?? '') ?>">
        <button class="btn btn-outline-secondary" type="submit">Ara</button>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-hover bg-white shadow-sm">
        <thead>
        <tr>
            <th>Ad</th>
            <th>Telefon</th>
            <th>E-posta</th>
            <th>Adres</th>
            <th>Islemler</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($customers)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Kayit bulunamadi.</td></tr>
        <?php endif; ?>
        <?php foreach ($customers as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['name']) ?></td>
                <td><?= htmlspecialchars($c['phone'] ?? '-') ?></td>
                <td><?= htmlspecialchars($c['email'] ?? '-') ?></td>
                <td><?= htmlspecialchars($c['address'] ?? '-') ?></td>
                <td>
                    <?php if (Auth::can('customers.edit')): ?>
                        <a href="<?= Url::to('/customers/edit') ?>?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary">Duzenle</a>
                    <?php endif; ?>
                    <?php if (Auth::can('customers.delete')): ?>
                        <form action="<?= Url::to('/customers/delete') ?>" method="post" class="d-inline"
                              onsubmit="return confirm('Bu musteriyi silmek istediginizden emin misiniz?');">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Sil</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
