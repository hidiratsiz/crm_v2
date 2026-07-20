<?php use App\Core\Csrf; use App\Core\Url; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Servis Modulleri</h2>
    <a href="<?= Url::to('/service-modules/create') ?>" class="btn btn-primary">+ Yeni Servis</a>
</div>
<p class="text-muted">
    Her servis icin alanlar (m², adet, sabit ucret vb.) ve fiyatlandirma kurallari tanimlayin.
    Teklif olustururken bir servis secildiginde bu alanlar otomatik gorunur ve fiyat canli hesaplanir.
</p>

<?php if (empty($modules)): ?>
    <div class="alert alert-secondary">Henuz bir servis modulu yok.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover bg-white shadow-sm">
            <thead><tr><th>Ad</th><th>Aciklama</th><th>Durum</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($modules as $module): ?>
                <tr>
                    <td><?= htmlspecialchars($module['name']) ?></td>
                    <td class="text-muted"><?= htmlspecialchars($module['description'] ?? '-') ?></td>
                    <td>
                        <?= $module['is_active']
                            ? '<span class="badge bg-success">Aktif</span>'
                            : '<span class="badge bg-secondary">Pasif</span>' ?>
                    </td>
                    <td class="d-flex gap-2">
                        <a href="<?= Url::to('/service-modules/edit') ?>?id=<?= $module['id'] ?>" class="btn btn-sm btn-outline-secondary">Duzenle</a>
                        <form action="<?= Url::to('/service-modules/toggle-active') ?>" method="post">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= $module['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                <?= $module['is_active'] ? 'Pasife Al' : 'Aktif Et' ?>
                            </button>
                        </form>
                        <form action="<?= Url::to('/service-modules/delete') ?>" method="post"
                              onsubmit="return confirm('Bu servisi silmek istediginizden emin misiniz?');">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= $module['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Sil</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
