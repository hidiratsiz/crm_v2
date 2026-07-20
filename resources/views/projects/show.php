<?php use App\Core\Auth; use App\Core\Csrf; use App\Core\Url; ?>
<div class="d-flex justify-content-between align-items-start mb-4">
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
                <table class="table table-sm mb-3">
                    <?php foreach ($fieldValuesByEstimate[$estimate['id']] as $fv): ?>
                        <tr>
                            <td class="text-muted"><?= htmlspecialchars($fv['label']) ?></td>
                            <td><?= htmlspecialchars($fv['value'] ?? '-') ?></td>
                            <td class="text-end">$<?= number_format((float) $fv['computed_price'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
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
