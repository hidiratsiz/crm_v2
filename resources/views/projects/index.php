<?php use App\Core\Url; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Projeler ve Teklifler</h2>
    <a href="<?= Url::to('/quick-capture') ?>" class="btn btn-primary">+ Hizli Kayit</a>
</div>

<?php if (empty($projects)): ?>
    <div class="alert alert-secondary">
        Henuz bir proje yok. <a href="<?= Url::to('/quick-capture') ?>">Hizli Kayit</a> kutusunu kullanarak
        veya bir musteri sayfasindan yeni bir proje/teklif olusturabilirsiniz.
    </div>
<?php else: ?>
    <?php
    $statusLabels = ['lead' => 'Yeni Talep', 'estimate' => 'Teklif Asamasi', 'active' => 'Devam Ediyor', 'completed' => 'Tamamlandi', 'cancelled' => 'Iptal'];
    $statusBadgeClass = ['lead' => 'bg-secondary', 'estimate' => 'bg-info text-dark', 'active' => 'bg-primary', 'completed' => 'bg-success', 'cancelled' => 'bg-danger'];
    ?>
    <div class="table-responsive">
        <table class="table table-hover bg-white shadow-sm">
            <thead>
            <tr>
                <th>Proje</th>
                <th>Musteri</th>
                <th>Durum</th>
                <th>Tarih</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($projects as $project): ?>
                <tr>
                    <td><?= htmlspecialchars($project['name']) ?></td>
                    <td><a href="<?= Url::to('/customers/edit') ?>?id=<?= $project['customer_id'] ?>"><?= htmlspecialchars($project['customer_name']) ?></a></td>
                    <td><span class="badge <?= $statusBadgeClass[$project['status']] ?? 'bg-secondary' ?>"><?= htmlspecialchars($statusLabels[$project['status']] ?? $project['status']) ?></span></td>
                    <td><?= htmlspecialchars($project['created_at']) ?></td>
                    <td><a href="<?= Url::to('/projects/show') ?>?id=<?= $project['id'] ?>" class="btn btn-sm btn-outline-primary">Tekliflere Git</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
