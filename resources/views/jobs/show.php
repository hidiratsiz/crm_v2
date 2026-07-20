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

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h2 class="mb-1"><?= htmlspecialchars($project['name'] ?? 'Is') ?></h2>
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
        <table class="table table-sm mb-3">
            <thead><tr><th>Kategori</th><th>Aciklama</th><th>Tutar</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($expenses as $expense): ?>
                <tr>
                    <td><?= htmlspecialchars($expense['category'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($expense['description'] ?? '-') ?></td>
                    <td>$<?= number_format((float) $expense['amount'], 2) ?></td>
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
    <?php endif; ?>

    <?php if (Auth::can('customers.edit')): ?>
        <form action="<?= Url::to('/jobs/expenses/add') ?>" method="post" class="row g-2">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $job['id'] ?>">
            <div class="col-md-3"><input type="text" name="category" class="form-control" placeholder="Kategori (orn. Malzeme)"></div>
            <div class="col-md-5"><input type="text" name="description" class="form-control" placeholder="Aciklama"></div>
            <div class="col-md-2"><input type="text" name="amount" class="form-control" placeholder="Tutar $" required></div>
            <div class="col-md-2"><button type="submit" class="btn btn-outline-primary w-100">Ekle</button></div>
        </form>
    <?php endif; ?>
</div>

<a href="<?= Url::to('/projects/show') ?>?id=<?= $job['project_id'] ?>" class="btn btn-outline-secondary">Teklife/Projeye Don</a>
