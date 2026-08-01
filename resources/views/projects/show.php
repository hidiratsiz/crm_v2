<?php use App\Core\Auth; use App\Core\Csrf; use App\Core\Url; ?>
<?php if (isset($_GET['mail_sent'])): ?>
    <?php if ($_GET['mail_sent'] === '1'): ?>
        <div class="alert alert-success">Teklif musteriye e-posta ile gonderildi.</div>
    <?php else: ?>
        <div class="alert alert-danger">Teklif gonderilemedi — musterinin e-postasi kayitli mi kontrol edin.</div>
    <?php endif; ?>
<?php endif; ?>
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><?= htmlspecialchars($project['name']) ?></h2>
        <?php if ($customer): ?>
            <p class="text-muted mb-0">
                Musteri: <a href="<?= Url::to('/customers/edit') ?>?id=<?= $customer['id'] ?>"><?= htmlspecialchars($customer['name']) ?></a>
                <?php if (!empty($customer['phone'])): ?> — <?= htmlspecialchars($customer['phone']) ?><?php endif; ?>
                <?php if (!empty($customer['address'])): ?> — <?= htmlspecialchars($customer['address']) ?><?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
    <span class="badge bg-secondary fs-6"><?= htmlspecialchars($project['status']) ?></span>
</div>

<?php if (!empty($project['notes'])): ?>
    <div class="card p-3 shadow-sm mb-4">
        <h6>Is Detaylari</h6>
        <p class="mb-0"><?= nl2br(htmlspecialchars($project['notes'])) ?></p>
    </div>
<?php endif; ?>

<div class="card p-3 shadow-sm mb-4">
    <h6 class="mb-3">Randevular (On Gorusme / Inceleme)</h6>
    <?php
    $appointmentStatusLabels = ['scheduled' => 'Planlandi', 'completed' => 'Tamamlandi', 'cancelled' => 'Iptal'];
    $appointmentStatusBadge = ['scheduled' => 'bg-info text-dark', 'completed' => 'bg-success', 'cancelled' => 'bg-danger'];
    ?>
    <?php if (empty($appointments)): ?>
        <p class="text-muted">Henuz bir randevu planlanmadi.</p>
    <?php else: ?>
        <ul class="list-group mb-3">
            <?php foreach ($appointments as $appt): ?>
                <li class="list-group-item d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <strong><?= htmlspecialchars($appt['title'] ?: 'Randevu') ?></strong>
                        — <?= htmlspecialchars($appt['scheduled_date']) ?>
                        <?php if (!empty($appt['scheduled_time'])): ?>
                            <?= htmlspecialchars(substr($appt['scheduled_time'], 0, 5)) ?>
                        <?php endif; ?>
                        <span class="badge <?= $appointmentStatusBadge[$appt['status']] ?? 'bg-secondary' ?> ms-1">
                            <?= htmlspecialchars($appointmentStatusLabels[$appt['status']] ?? $appt['status']) ?>
                        </span>
                        <?php if (!empty($appt['notes'])): ?>
                            <br><small class="text-muted"><?= htmlspecialchars($appt['notes']) ?></small>
                        <?php endif; ?>
                    </div>
                    <?php if (Auth::can('customers.edit')): ?>
                        <div class="d-flex align-items-center gap-2">
                            <form action="<?= Url::to('/appointments/status') ?>" method="post" class="d-inline-flex align-items-center gap-1">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= $appt['id'] ?>">
                                <select name="status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                    <?php foreach ($appointmentStatusLabels as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= $appt['status'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                            <?php if (Auth::can('customers.delete')): ?>
                                <form action="<?= Url::to('/appointments/delete') ?>" method="post"
                                      onsubmit="return confirm('Bu randevuyu silmek istediginizden emin misiniz?');">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= $appt['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Sil</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if (Auth::can('customers.create')): ?>
        <form action="<?= Url::to('/appointments/store') ?>" method="post" class="row g-2">
            <?= Csrf::field() ?>
            <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
            <div class="col-md-3"><input type="date" name="scheduled_date" class="form-control" required></div>
            <div class="col-md-2"><input type="time" name="scheduled_time" class="form-control"></div>
            <div class="col-md-4"><input type="text" name="notes" class="form-control" placeholder="orn. Guverte inceleme"></div>
            <div class="col-md-3"><button type="submit" class="btn btn-outline-primary w-100">+ Randevu Ekle</button></div>
        </form>
    <?php endif; ?>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Teklifler</h4>
    <?php if (Auth::can('customers.create')): ?>
        <a href="<?= Url::to('/estimates/create') ?>?project_id=<?= $project['id'] ?>" class="btn btn-sm btn-primary">+ Yeni Teklif Ekle</a>
    <?php endif; ?>
</div>

<?php if (empty($estimates)): ?>
    <p class="text-muted">Bu projeye ait henuz bir teklif yok.</p>
<?php else: ?>
    <?php
    $statusLabels = ['draft' => 'Taslak', 'sent' => 'Gonderildi', 'accepted' => 'Kabul Edildi', 'rejected' => 'Reddedildi'];
    $statusBadgeClass = ['draft' => 'bg-light text-dark border', 'sent' => 'bg-info text-dark', 'accepted' => 'bg-success', 'rejected' => 'bg-danger'];
    ?>
    <?php foreach ($estimates as $estimate): ?>
        <div class="card p-4 shadow-sm mb-3">
            <div class="d-flex justify-content-between align-items-start">
                <h5 class="mb-2"><?= htmlspecialchars($estimate['title']) ?></h5>
                <?php if (!empty($estimate['amount'])): ?>
                    <span class="badge bg-success fs-6">$<?= number_format((float) $estimate['amount'], 2) ?></span>
                <?php endif; ?>
            </div>
            <p class="text-muted mb-3"><?= nl2br(htmlspecialchars($estimate['description'] ?? '-')) ?></p>

            <?php if (!empty($fieldValuesByEstimate[$estimate['id']])): ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-3">
                        <?php foreach ($fieldValuesByEstimate[$estimate['id']] as $fv): ?>
                            <tr>
                                <td class="text-muted"><?= htmlspecialchars($fv['label']) ?></td>
                                <td><?= htmlspecialchars($fv['value'] ?? '-') ?></td>
                                <td class="text-end">$<?= number_format((float) $fv['computed_price'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="badge <?= $statusBadgeClass[$estimate['status']] ?? 'bg-secondary' ?>">
                    <?= htmlspecialchars($statusLabels[$estimate['status']] ?? $estimate['status']) ?>
                </span>

                <div class="d-flex align-items-center gap-2">
                    <?php if (Auth::can('customers.edit')): ?>
                        <form action="<?= Url::to('/estimates/status') ?>" method="post" class="d-inline-flex align-items-center gap-1">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= $estimate['id'] ?>">
                            <select name="status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                <?php foreach ($statusLabels as $value => $label): ?>
                                    <option value="<?= $value ?>" <?= $estimate['status'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        <a href="<?= Url::to('/estimates/edit') ?>?id=<?= $estimate['id'] ?>" class="btn btn-sm btn-outline-secondary">Duzenle</a>

                        <?php if (!empty($customer['email'])): ?>
                            <form action="<?= Url::to('/estimates/send-to-customer') ?>" method="post" class="d-inline">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= $estimate['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-primary">Musteriye Gonder</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($estimate['status'] === 'accepted'): ?>
                            <?php if (isset($jobsByEstimate[$estimate['id']])): ?>
                                <a href="<?= Url::to('/jobs/show') ?>?id=<?= $jobsByEstimate[$estimate['id']]['id'] ?>" class="btn btn-sm btn-success">Isi Goruntule</a>
                            <?php else: ?>
                                <form action="<?= Url::to('/estimates/convert-to-job') ?>" method="post" class="d-inline">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= $estimate['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-success">Ise Donustur</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if (Auth::can('customers.delete')): ?>
                        <form action="<?= Url::to('/estimates/delete') ?>" method="post" class="d-inline"
                              onsubmit="return confirm('Bu teklifi silmek istediginizden emin misiniz?');">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= $estimate['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Sil</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <small class="text-muted d-block mt-2"><?= htmlspecialchars($estimate['created_at']) ?></small>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($project['raw_input'])): ?>
    <details class="mt-4">
        <summary class="text-muted" style="cursor: pointer;">Orijinal not (Hizli Kayit girdisi)</summary>
        <div class="card p-3 shadow-sm mt-2">
            <pre class="mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($project['raw_input']) ?></pre>
        </div>
    </details>
<?php endif; ?>

<a href="<?= Url::to('/customers/edit') ?>?id=<?= $project['customer_id'] ?>" class="btn btn-outline-secondary mt-3">Musteriye Don</a>
