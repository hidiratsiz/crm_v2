<?php use App\Core\Auth; use App\Core\Csrf; use App\Core\Url; ?>
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

<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1">
            <?= htmlspecialchars($project['name'] ?? 'Is') ?>
            <span class="badge bg-secondary fs-6 align-middle">Is #<?= $job['id'] ?></span>
        </h2>
        <?php if ($customer): ?>
            <p class="text-muted mb-0">
                Musteri: <a href="<?= Url::to('/customers/edit') ?>?id=<?= $customer['id'] ?>"><?= htmlspecialchars($customer['name']) ?></a>
                <?php if (!empty($customer['phone'])): ?> — <?= htmlspecialchars($customer['phone']) ?><?php endif; ?>
                <?php if (!empty($customer['address'])): ?> — <?= htmlspecialchars($customer['address']) ?><?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
    <span class="badge <?= $statusBadgeClass[$job['status']] ?? 'bg-secondary' ?> fs-6">
        <?= htmlspecialchars($statusLabels[$job['status']] ?? $job['status']) ?>
    </span>
</div>

<!-- Proje Finansmani -->
<div class="card p-3 shadow-sm mb-4">
    <h6 class="mb-3">Proje Finansmani</h6>
    <div class="row g-3 text-center mb-3">
        <div class="col-6 col-md-2">
            <div class="text-muted small">Sozlesme Tutari</div>
            <div class="fs-5 fw-bold">
                <?= $contractAmount !== null ? '$' . number_format((float) $contractAmount, 2) : '-' ?>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="text-muted small">Alinan Odeme (Gelir)</div>
            <div class="fs-5 fw-bold text-success">$<?= number_format($paymentTotal, 2) ?></div>
        </div>
        <div class="col-6 col-md-2">
            <div class="text-muted small">Toplam Gider</div>
            <div class="fs-5 fw-bold text-danger">$<?= number_format($expenseTotal, 2) ?></div>
        </div>
        <div class="col-6 col-md-2">
            <div class="text-muted small">Personel Gideri</div>
            <div class="fs-5 fw-bold text-danger">$<?= number_format($laborTotal, 2) ?></div>
        </div>
        <div class="col-6 col-md-2">
            <div class="text-muted small">Kalan Bakiye</div>
            <div class="fs-5 fw-bold <?= ($balanceDue !== null && $balanceDue > 0) ? 'text-warning' : 'text-success' ?>">
                <?= $balanceDue !== null ? '$' . number_format($balanceDue, 2) : '-' ?>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="text-muted small">Net Kar</div>
            <div class="fs-5 fw-bold <?= $netProfit >= 0 ? 'text-success' : 'text-danger' ?>">$<?= number_format($netProfit, 2) ?></div>
        </div>
    </div>

    <?php if (!empty($employeeFinance)): ?>
        <h6 class="mb-2 mt-3">Kimde Ne Kadar Var</h6>
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
            "Elinde kalan" = aldigi odeme - yaptigi gider - odedigi iscilik. Pozitifse o calisan musteriden aldigi paranin bir kismini henuz sirkete/kasaya teslim etmemis demektir.
        </small>
    <?php endif; ?>
</div>

<div class="row g-3 mb-4">
    <!-- Durum ve Baslangic Tarihi -->
    <div class="col-md-6">
        <div class="card p-3 shadow-sm h-100">
            <h6 class="mb-3">Durum</h6>
            <?php if (Auth::can('customers.edit')): ?>
                <form action="<?= Url::to('/jobs/status') ?>" method="post" class="d-flex gap-2 mb-3">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= $job['id'] ?>">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($statusLabels as $value => $label): ?>
                            <option value="<?= $value ?>" <?= $job['status'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>

            <h6 class="mb-2">Baslangic Tarihi / Saati</h6>
            <?php if (Auth::can('customers.edit')): ?>
                <form action="<?= Url::to('/jobs/start-date') ?>" method="post">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= $job['id'] ?>">
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small text-muted mb-1">Tarih</label>
                            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($job['start_date'] ?? '') ?>">
                        </div>
                        <div class="col-3">
                            <label class="form-label small text-muted mb-1">Saat (varsa)</label>
                            <input type="time" name="start_time" class="form-control" value="<?= htmlspecialchars(substr($job['start_time'] ?? '', 0, 5)) ?>">
                        </div>
                        <div class="col-3">
                            <label class="form-label small text-muted mb-1">Sure (saat)</label>
                            <input type="text" name="duration_hours" class="form-control" placeholder="orn. 2" value="<?= htmlspecialchars($job['duration_hours'] ?? '') ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline-primary">Kaydet</button>
                </form>
                <?php if (empty($job['start_date'])): ?>
                    <small class="text-muted d-block mt-2">Baslangic tarihi henuz belirlenmedi.</small>
                <?php elseif (!empty($job['start_time'])): ?>
                    <small class="text-muted d-block mt-2">
                        Saatlik is — takvimde <?= htmlspecialchars(substr($job['start_time'], 0, 5)) ?>
                        <?php if (!empty($job['duration_hours'])): ?> - <?= htmlspecialchars($job['duration_hours']) ?> saat<?php endif; ?> olarak gorunecek.
                    </small>
                <?php endif; ?>
            <?php else: ?>
                <p class="mb-0">
                    <?= htmlspecialchars($job['start_date'] ?? 'Belirlenmedi') ?>
                    <?php if (!empty($job['start_time'])): ?> — <?= htmlspecialchars(substr($job['start_time'], 0, 5)) ?><?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Calisan Atama -->
    <div class="col-md-6">
        <div class="card p-3 shadow-sm h-100">
            <h6 class="mb-3">Atanan Calisanlar</h6>
            <?php if (empty($assignedEmployees)): ?>
                <p class="text-muted">Henuz kimse atanmadi.</p>
            <?php else: ?>
                <ul class="list-unstyled mb-3">
                    <?php foreach ($assignedEmployees as $emp): ?>
                        <li class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong><?= htmlspecialchars($emp['name']) ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars($emp['email']) ?></small>
                                <?php if (!empty($emp['notified_at'])): ?>
                                    <br><small class="text-success">✓ Bildirim e-postasi gonderildi</small>
                                <?php else: ?>
                                    <br><small class="text-warning">Bildirim gonderilemedi</small>
                                <?php endif; ?>
                            </div>
                            <?php if (Auth::can('customers.edit')): ?>
                                <form action="<?= Url::to('/jobs/unassign-employee') ?>" method="post">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= $job['id'] ?>">
                                    <input type="hidden" name="user_id" value="<?= $emp['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Kaldir</button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if (Auth::can('customers.edit')): ?>
                <form action="<?= Url::to('/jobs/assign-employee') ?>" method="post" class="d-flex gap-2">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= $job['id'] ?>">
                    <select name="user_id" class="form-select" required>
                        <option value="">Calisan secin...</option>
                        <?php foreach ($availableEmployees as $emp): ?>
                            <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?> (<?= htmlspecialchars($emp['role_name']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-outline-primary text-nowrap">Ata + Bildir</button>
                </form>
                <small class="text-muted d-block mt-2">
                    Atama yapildiginda calisana musteri adi, adresi ve is detaylari e-posta ile otomatik gonderilir.
                </small>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Kontrol Listesi -->
<div class="card p-3 shadow-sm mb-4">
    <h6 class="mb-3">Is Sureci - Kontrol Listesi</h6>
    <?php if (empty($checklist)): ?>
        <p class="text-muted">Henuz bir adim eklenmedi.</p>
    <?php else: ?>
        <ul class="list-group mb-3">
            <?php foreach ($checklist as $item): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div class="form-check">
                        <form action="<?= Url::to('/jobs/checklist/toggle') ?>" method="post" class="d-inline">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <input type="checkbox" class="form-check-input" onchange="this.form.submit()" <?= $item['is_done'] ? 'checked' : '' ?>
                                <?= Auth::can('customers.edit') ? '' : 'disabled' ?>>
                        </form>
                        <span class="<?= $item['is_done'] ? 'text-decoration-line-through text-muted' : '' ?>">
                            <?= htmlspecialchars($item['description']) ?>
                        </span>
                    </div>
                    <?php if (Auth::can('customers.delete')): ?>
                        <form action="<?= Url::to('/jobs/checklist/delete') ?>" method="post">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Sil</button>
                        </form>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if (Auth::can('customers.edit')): ?>
        <form action="<?= Url::to('/jobs/checklist/add') ?>" method="post" class="d-flex gap-2">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $job['id'] ?>">
            <input type="text" name="description" class="form-control" placeholder="orn. Guverte zimparalama" required>
            <button type="submit" class="btn btn-outline-primary text-nowrap">+ Adim Ekle</button>
        </form>
    <?php endif; ?>
</div>

<!-- Giderler -->
<div class="card p-3 shadow-sm mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0">Giderler</h6>
        <strong>Toplam: $<?= number_format($expenseTotal, 2) ?></strong>
    </div>

    <?php if (empty($expenses)): ?>
        <p class="text-muted">Henuz bir gider eklenmedi.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm mb-3">
                <thead><tr><th>Tarih</th><th>Kategori</th><th>Aciklama</th><th>Tutar</th><th>Kim</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($expenses as $expense): ?>
                    <tr>
                        <td><?= !empty($expense['expense_date']) ? htmlspecialchars(date('d.m.Y', strtotime($expense['expense_date']))) : '-' ?></td>
                        <td><?= htmlspecialchars($expense['category'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($expense['description'] ?? '-') ?></td>
                        <td>$<?= number_format((float) $expense['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($expense['created_by_name'] ?? '-') ?></td>
                        <td>
                            <?php if (Auth::can('customers.delete')): ?>
                                <form action="<?= Url::to('/jobs/expenses/delete') ?>" method="post">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= $expense['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Sil</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if (Auth::can('customers.edit')): ?>
        <form action="<?= Url::to('/jobs/expenses/add') ?>" method="post" class="row g-2">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $job['id'] ?>">
            <div class="col-md-2"><input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
            <div class="col-md-2"><input type="text" name="category" class="form-control" placeholder="Kategori (orn. Malzeme)"></div>
            <div class="col-md-3"><input type="text" name="description" class="form-control" placeholder="Aciklama"></div>
            <div class="col-md-2"><input type="text" name="amount" class="form-control" placeholder="Tutar $" required></div>
            <div class="col-md-2">
                <select name="performed_by" class="form-select">
                    <option value="">Kim yapti? (Ben)</option>
                    <?php foreach ($availableEmployees as $emp): ?>
                        <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1"><button type="submit" class="btn btn-outline-primary w-100">Ekle</button></div>
        </form>
    <?php endif; ?>
</div>

<!-- Odemeler -->
<div class="card p-3 shadow-sm mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0">Odemeler</h6>
        <strong>Toplam: $<?= number_format($paymentTotal, 2) ?></strong>
    </div>

    <?php
    $paymentMethodLabels = ['cash' => 'Nakit', 'card' => 'Kredi Karti', 'bank_transfer' => 'Havale/EFT', 'check' => 'Cek'];
    ?>

    <?php if (empty($payments)): ?>
        <p class="text-muted">Henuz bir odeme kaydedilmedi.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm mb-3">
                <thead><tr><th>Tarih</th><th>Tutar</th><th>Yontem</th><th>Not</th><th>Kim Aldi</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?= !empty($payment['paid_at']) ? htmlspecialchars(date('d.m.Y', strtotime($payment['paid_at']))) : '-' ?></td>
                        <td>$<?= number_format((float) $payment['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($paymentMethodLabels[$payment['method']] ?? $payment['method']) ?></td>
                        <td><?= htmlspecialchars($payment['note'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($payment['received_by_name'] ?? '-') ?></td>
                        <td>
                            <?php if (Auth::can('customers.delete')): ?>
                                <form action="<?= Url::to('/jobs/payments/delete') ?>" method="post">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= $payment['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Sil</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if (Auth::can('customers.edit')): ?>
        <form action="<?= Url::to('/jobs/payments/add') ?>" method="post" class="row g-2">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $job['id'] ?>">
            <div class="col-md-2"><input type="date" name="paid_at" class="form-control" value="<?= date('Y-m-d') ?>"></div>
            <div class="col-md-2"><input type="text" name="amount" class="form-control" placeholder="Tutar $" required></div>
            <div class="col-md-2">
                <select name="method" class="form-select">
                    <?php foreach ($paymentMethodLabels as $value => $label): ?>
                        <option value="<?= $value ?>"><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3"><input type="text" name="note" class="form-control" placeholder="Not (istege bagli)"></div>
            <div class="col-md-2">
                <select name="received_by" class="form-select">
                    <option value="">Kim aldi? (Ben)</option>
                    <?php foreach ($availableEmployees as $emp): ?>
                        <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1"><button type="submit" class="btn btn-outline-primary w-100">Ekle</button></div>
        </form>
    <?php endif; ?>
</div>

<!-- Personel Giderleri -->
<div class="card p-3 shadow-sm mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0">Personel Giderleri (Iscilik)</h6>
        <strong>Toplam: $<?= number_format($laborTotal, 2) ?></strong>
    </div>

    <?php if (empty($laborCosts)): ?>
        <p class="text-muted">Henuz bir personel gideri eklenmedi.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm mb-3">
                <thead><tr><th>Tarih</th><th>Calisan</th><th>Not</th><th>Tutar</th><th>Kim Odedi</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($laborCosts as $laborCost): ?>
                    <tr>
                        <td><?= !empty($laborCost['work_date']) ? htmlspecialchars(date('d.m.Y', strtotime($laborCost['work_date']))) : '-' ?></td>
                        <td><?= htmlspecialchars($laborCost['employee_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($laborCost['note'] ?? '-') ?></td>
                        <td>$<?= number_format((float) $laborCost['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($laborCost['paid_by_name'] ?? '-') ?></td>
                        <td>
                            <?php if (Auth::can('customers.delete')): ?>
                                <form action="<?= Url::to('/jobs/labor-costs/delete') ?>" method="post">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= $laborCost['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Sil</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if (Auth::can('customers.edit')): ?>
        <form action="<?= Url::to('/jobs/labor-costs/add') ?>" method="post" class="row g-2">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $job['id'] ?>">
            <div class="col-md-2"><input type="date" name="work_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
            <div class="col-md-2">
                <select name="user_id" class="form-select">
                    <option value="">Calisan secin...</option>
                    <?php foreach ($availableEmployees as $emp): ?>
                        <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3"><input type="text" name="note" class="form-control" placeholder="Not (orn. 2 gunluk yevmiye)"></div>
            <div class="col-md-2"><input type="text" name="amount" class="form-control" placeholder="Tutar $" required></div>
            <div class="col-md-2">
                <select name="paid_by" class="form-select">
                    <option value="">Kim odedi? (Ben)</option>
                    <?php foreach ($availableEmployees as $emp): ?>
                        <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1"><button type="submit" class="btn btn-outline-primary w-100">Ekle</button></div>
        </form>
    <?php endif; ?>
</div>

<a href="<?= Url::to('/projects/show') ?>?id=<?= $job['project_id'] ?>" class="btn btn-outline-secondary">Teklife/Projeye Don</a>
