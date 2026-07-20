<?php use App\Core\Url; ?>
<h2 class="mb-4">Dashboard</h2>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <a href="<?= Url::to('/customers') ?>" class="text-decoration-none">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Toplam Musteri</h6>
                    <h3><?= (int) \App\Core\Database::connection()->query('SELECT COUNT(*) c FROM customers WHERE deleted_at IS NULL')->fetch()['c'] ?></h3>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="<?= Url::to('/projects') ?>" class="text-decoration-none">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Toplam Proje</h6>
                    <h3><?= (int) \App\Core\Database::connection()->query('SELECT COUNT(*) c FROM projects WHERE deleted_at IS NULL')->fetch()['c'] ?></h3>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="<?= Url::to('/projects') ?>" class="text-decoration-none">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Toplam Teklif</h6>
                    <h3><?= (int) \App\Core\Database::connection()->query('SELECT COUNT(*) c FROM estimates WHERE deleted_at IS NULL')->fetch()['c'] ?></h3>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="<?= Url::to('/jobs') ?>" class="text-decoration-none">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Toplam Is</h6>
                    <h3><?= (int) \App\Core\Database::connection()->query('SELECT COUNT(*) c FROM jobs WHERE deleted_at IS NULL')->fetch()['c'] ?></h3>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Isler</h4>
    <a href="<?= Url::to('/jobs') ?>" class="btn btn-sm btn-outline-primary">Tumunu Gor</a>
</div>

<?php if (empty($recentJobs)): ?>
    <div class="alert alert-secondary mb-4">
        Henuz bir is yok. Bir teklif "Kabul Edildi" yapilip <strong>"Ise Donustur"</strong> ile
        cevrildiginde burada gorunecek.
    </div>
<?php else: ?>
    <div class="list-group mb-4">
        <?php foreach ($recentJobs as $job): ?>
            <a href="<?= Url::to('/jobs/show') ?>?id=<?= $job['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <div>
                    <strong><?= htmlspecialchars($job['customer_name'] ?? '') ?></strong> — <?= htmlspecialchars($job['project_name'] ?? '') ?>
                    <br><small class="text-muted">
                        <?= !empty($job['employee_names']) ? htmlspecialchars($job['employee_names']) : 'Kimse atanmadi' ?>
                    </small>
                </div>
                <span class="badge bg-secondary"><?= htmlspecialchars($job['status']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Son Projeler / Teklifler</h4>
    <a href="<?= Url::to('/projects') ?>" class="btn btn-sm btn-outline-primary">Tumunu Gor</a>
</div>

<?php if (empty($recentProjects)): ?>
    <div class="alert alert-secondary">
        Henuz bir proje yok. <a href="<?= Url::to('/quick-capture') ?>">+ Hizli Kayit</a> kutusunu kullanarak
        telefonda konustugunuz bir musteriyi hemen kaydedebilirsiniz.
    </div>
<?php else: ?>
    <div class="list-group mb-4">
        <?php foreach ($recentProjects as $project): ?>
            <a href="<?= Url::to('/projects/show') ?>?id=<?= $project['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <div>
                    <strong><?= htmlspecialchars($project['name']) ?></strong>
                    <br><small class="text-muted"><?= htmlspecialchars($project['customer_name']) ?> — <?= htmlspecialchars($project['created_at']) ?></small>
                </div>
                <span class="badge bg-secondary"><?= htmlspecialchars($project['status']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="alert alert-info">
    Faz 2 devam ediyor: AI destekli Hizli Kayit kutusu, coklu teklif olusturma, teklif durumu yonetimi tamamlandi.
    Sirada: otomatik fiyatlandirma motoru ve teklif PDF ciktisi.
</div>
