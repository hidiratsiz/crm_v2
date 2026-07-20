<?php use App\Core\Url; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Kullanicilar / Calisanlar</h2>
    <a href="<?= Url::to('/users/create') ?>" class="btn btn-primary">+ Yeni Kullanici</a>
</div>

<div class="table-responsive">
    <table class="table table-hover bg-white shadow-sm">
        <thead>
        <tr>
            <th>Ad</th>
            <th>E-posta</th>
            <th>Rol</th>
            <th>Durum</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars($u['role_name']) ?></span></td>
                <td><?= $u['is_active'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-danger">Pasif</span>' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
