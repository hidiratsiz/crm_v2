<?php use App\Core\Url; ?>

<div class="d-flex justify-content-between align-items-start mb-4">
    <h2 class="mb-0">Finans</h2>
</div>

<!-- Tarih Filtresi -->
<form action="<?= Url::to('/finance') ?>" method="get" class="card p-3 shadow-sm mb-4 row g-2 align-items-end">
    <div class="col-md-3">
        <label class="form-label small text-muted mb-1">Baslangic Tarihi</label>
        <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from ?? '') ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label small text-muted mb-1">Bitis Tarihi</label>
        <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to ?? '') ?>">
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-outline-primary w-100">Filtrele</button>
    </div>
    <?php if (!empty($from) || !empty($to)): ?>
        <div class="col-md-2">
            <a href="<?= Url::to('/finance') ?>" class="btn btn-outline-secondary w-100">Temizle</a>
        </div>
    <?php endif; ?>
</form>

<!-- Ozet Kartlari -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card p-3 shadow-sm text-center h-100">
            <div class="text-muted small">Toplam Gelir</div>
            <div class="fs-4 fw-bold text-success">$<?= number_format($totalIncome, 2) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 shadow-sm text-center h-100">
            <div class="text-muted small">Toplam Gider</div>
            <div class="fs-4 fw-bold text-danger">$<?= number_format($totalExpense, 2) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 shadow-sm text-center h-100">
            <div class="text-muted small">Personel Gideri</div>
            <div class="fs-4 fw-bold text-danger">$<?= number_format($totalLabor, 2) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 shadow-sm text-center h-100">
            <div class="text-muted small">Net</div>
            <div class="fs-4 fw-bold <?= $net >= 0 ? 'text-success' : 'text-danger' ?>">$<?= number_format($net, 2) ?></div>
        </div>
    </div>
</div>

<!-- Calisan Bazinda Ozet -->
<div class="card p-3 shadow-sm mb-4">
    <h6 class="mb-3">Kimde Ne Kadar Var (Sirket Geneli)</h6>
    <?php if (empty($employeeFinance)): ?>
        <p class="text-muted mb-0">Bu donemde kayitli bir odeme/gider yok.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Calisan</th><th class="text-end">Aldigi Odeme</th><th class="text-end">Yaptigi Gider</th><th class="text-end">Odedigi Iscilik</th><th class="text-end">Elinde Kalan</th></tr></thead>
                <tbody>
                <?php foreach ($employeeFinance as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td class="text-end">$<?= number_format($row['received'], 2) ?></td>
                        <td class="text-end">$<?= number_format($row['spent'], 2) ?></td>
                        <td class="text-end">$<?= number_format($row['labor_paid'] ?? 0, 2) ?></td>
                        <td class="text-end fw-bold <?= $row['net'] > 0 ? 'text-warning' : ($row['net'] < 0 ? 'text-danger' : '') ?>">
                            $<?= number_format($row['net'], 2) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <small class="text-muted d-block mt-2">
            "Elinde kalan" = aldigi odeme - yaptigi gider - odedigi iscilik. Pozitifse o calisan musterilerden aldigi paranin bir kismini henuz sirkete/kasaya teslim etmemis demektir.
        </small>
    <?php endif; ?>
</div>

<!-- Ise/Projeye Gore Ozet -->
<div class="card p-3 shadow-sm mb-4">
    <h6 class="mb-3">Ise Gore Ozet</h6>
    <?php if (empty($jobSummary)): ?>
        <p class="text-muted mb-0">Bu donemde kayitli bir odeme/gider yok.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Musteri / Is</th><th class="text-end">Sozlesme</th><th class="text-end">Gelir</th><th class="text-end">Gider</th><th class="text-end">Personel</th><th class="text-end">Bakiye</th><th class="text-end">Net</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($jobSummary as $row): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($row['customer_name'] ?? '-') ?>
                            <br><small class="text-muted"><?= htmlspecialchars($row['project_name'] ?? '') ?></small>
                        </td>
                        <td class="text-end"><?= $row['contract_amount'] !== null ? '$' . number_format($row['contract_amount'], 2) : '-' ?></td>
                        <td class="text-end text-success">$<?= number_format($row['income'], 2) ?></td>
                        <td class="text-end text-danger">$<?= number_format($row['expense'], 2) ?></td>
                        <td class="text-end text-danger">$<?= number_format($row['labor'], 2) ?></td>
                        <td class="text-end fw-bold"><?= $row['balance'] !== null ? '$' . number_format($row['balance'], 2) : '-' ?></td>
                        <td class="text-end fw-bold <?= $row['net'] >= 0 ? 'text-success' : 'text-danger' ?>">$<?= number_format($row['net'], 2) ?></td>
                        <td><a href="<?= Url::to('/jobs/show') ?>?id=<?= $row['job_id'] ?>" class="btn btn-sm btn-outline-secondary">Ise Git</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Tum Islemler -->
<div class="card p-3 shadow-sm mb-4">
    <h6 class="mb-3">Tum Islemler</h6>
    <?php if (empty($transactions)): ?>
        <p class="text-muted mb-0">Bu donemde kayitli bir odeme/gider yok.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Tarih</th><th>Tur</th><th>Musteri / Is</th><th>Detay</th><th class="text-end">Tutar</th><th>Kim</th></tr></thead>
                <tbody>
                <?php foreach ($transactions as $tx): ?>
                    <tr>
                        <td><?= !empty($tx['date']) ? htmlspecialchars(date('d.m.Y', strtotime($tx['date']))) : '-' ?></td>
                        <td>
                            <?php if ($tx['type'] === 'income'): ?>
                                <span class="badge bg-success">Gelir</span>
                            <?php elseif ($tx['type'] === 'labor'): ?>
                                <span class="badge bg-warning text-dark">Personel</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Gider</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= Url::to('/jobs/show') ?>?id=<?= $tx['job_id'] ?>"><?= htmlspecialchars($tx['customer_name'] ?? '-') ?></a>
                            <br><small class="text-muted"><?= htmlspecialchars($tx['project_name'] ?? '') ?></small>
                        </td>
                        <td>
                            <?= htmlspecialchars($tx['label'] ?? '-') ?>
                            <?php if (!empty($tx['note'])): ?><br><small class="text-muted"><?= htmlspecialchars($tx['note']) ?></small><?php endif; ?>
                        </td>
                        <td class="text-end <?= $tx['type'] === 'income' ? 'text-success' : 'text-danger' ?>">
                            <?= $tx['type'] === 'income' ? '+' : '-' ?>$<?= number_format($tx['amount'], 2) ?>
                        </td>
                        <td><?= htmlspecialchars($tx['who'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
