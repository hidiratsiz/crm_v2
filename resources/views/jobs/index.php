<?php use App\Core\Url; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= htmlspecialchars($scopeLabel) ?></h2>
    <a href="<?= Url::to('/quick-capture') ?>" class="btn btn-primary">+ Hizli Kayit</a>
</div>

<?php if (empty($jobs)): ?>
    <div class="alert alert-secondary">
        Henuz bir is yok. Bir teklif "Kabul Edildi" durumuna getirilip <strong>"Ise Donustur"</strong>
        butonuyla ise cevrildiginde burada gorunecek.
    </div>
<?php else: ?>
    <?php
    $statusLabels = [
        'pending_schedule' => 'Baslangic Bekleniyor',
        'scheduled' => 'Planlandi',
        'in_progress' => 'Devam Ediyor',
        'completed' => 'Tamamlandi',
        'cancelled' => 'Iptal',
    ];
    $statusBadgeClass = [
        'pending_schedule' => 'bg-warning text-dark',
        'scheduled' => 'bg-info text-dark',
        'in_progress' => 'bg-primary',
        'completed' => 'bg-success',
        'cancelled' => 'bg-danger',
    ];
    ?>
    <div class="table-responsive">
        <table class="table table-hover bg-white shadow-sm">
            <thead>
            <tr>
                <th>ID</th>
                <th>Musteri / Is</th>
                <th>Atanan Calisan(lar)</th>
                <th>Durum</th>
                <th>Baslangic Tarihi</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($jobs as $job): ?>
                <tr>
                    <td><span class="badge bg-secondary">#<?= $job['id'] ?></span></td>
                    <td>
                        <strong><?= htmlspecialchars($job['customer_name'] ?? '-') ?></strong>
                        <br><small class="text-muted"><?= htmlspecialchars($job['project_name'] ?? '') ?></small>
                    </td>
                    <td>
                        <?php if (!empty($job['employee_names'])): ?>
                            <?= htmlspecialchars($job['employee_names']) ?>
                        <?php else: ?>
                            <span class="text-muted">Kimse atanmadi</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= $statusBadgeClass[$job['status']] ?? 'bg-secondary' ?>"><?= htmlspecialchars($statusLabels[$job['status']] ?? $job['status']) ?></span></td>
                    <td><?= htmlspecialchars($job['start_date'] ?? '-') ?></td>
                    <td><a href="<?= Url::to('/jobs/show') ?>?id=<?= $job['id'] ?>" class="btn btn-sm btn-outline-primary">Goruntule / Duzenle</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
