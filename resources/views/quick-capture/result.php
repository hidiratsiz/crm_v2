<?php use App\Core\Url; ?>
<h2 class="mb-4">Kayit Olusturuldu</h2>

<div class="alert <?= $isNewCustomer ? 'alert-success' : 'alert-info' ?>">
    <?php if ($isNewCustomer): ?>
        <strong>Yeni musteri olusturuldu:</strong> <?= htmlspecialchars($parsed['customer_name'] ?: 'Isimsiz Musteri') ?>
    <?php else: ?>
        <strong>Mevcut musteri bulundu ve kullanildi:</strong> <?= htmlspecialchars($parsed['customer_name'] ?: '') ?>
        <?php if (!empty($customerUpdated)): ?>
            <br><small>Eksik olan bazi bilgiler (telefon/e-posta/adres) bu kayitla tamamlandi.</small>
        <?php endif; ?>
    <?php endif; ?>
    — <a href="<?= Url::to('/customers/edit') ?>?id=<?= $customerId ?>">musteri kaydini goruntule</a>
</div>

<div class="card p-4 shadow-sm mb-4">
    <h5 class="mb-3">AI'nin Cikardigi Bilgiler</h5>
    <table class="table table-sm">
        <tr><th style="width:180px;">Musteri Adi</th><td><?= htmlspecialchars($parsed['customer_name'] ?: '-') ?></td></tr>
        <tr><th>Telefon</th><td><?= htmlspecialchars($parsed['phone'] ?: '-') ?></td></tr>
        <tr><th>E-posta</th><td><?= htmlspecialchars($parsed['email'] ?: '-') ?></td></tr>
        <tr><th>Sehir</th><td><?= htmlspecialchars($parsed['city'] ?: '-') ?></td></tr>
        <tr><th>Adres</th><td><?= htmlspecialchars($parsed['address'] ?: '-') ?></td></tr>
        <?php if (!empty($parsed['service_type'])): ?>
            <tr><th>Hizmet Turu</th><td><?= htmlspecialchars($parsed['service_type']) ?></td></tr>
        <?php endif; ?>
        <?php if (!empty($parsed['details'])): ?>
            <tr><th>Detaylar</th><td><?= nl2br(htmlspecialchars($parsed['details'])) ?></td></tr>
        <?php endif; ?>
    </table>
</div>

<?php if ($projectId !== null): ?>
    <div class="card p-4 shadow-sm mb-4">
        <h5 class="mb-3"><?= $estimateCount ?> Teklif Secenegi Olusturuldu (Taslak)</h5>
        <?php foreach ($parsed['estimates'] as $i => $estimate): ?>
            <div class="border rounded p-3 mb-2">
                <div class="d-flex justify-content-between align-items-start">
                    <strong><?= htmlspecialchars($estimate['title'] ?? ('Teklif ' . ($i + 1))) ?></strong>
                    <?php if (!empty($estimate['amount'])): ?>
                        <span class="badge bg-success fs-6">$<?= number_format((float) $estimate['amount'], 2) ?></span>
                    <?php endif; ?>
                </div>
                <p class="mb-0 text-muted"><?= nl2br(htmlspecialchars($estimate['description'] ?? '-')) ?></p>
            </div>
        <?php endforeach; ?>
        <small class="text-muted">
            Not: Tutarlar sadece siz metinde acikca bir fiyat belirttiyseniz gorunur. Otomatik
            (m²/adet bazli) fiyat hesaplama Faz 2'deki Dinamik Fiyatlandirma Motoru ile gelecek.
        </small>
    </div>
<?php else: ?>
    <div class="alert alert-secondary">
        Bu notta bir is/hizmet tanimi bulunmadigi icin sadece musteri kaydi olusturuldu/guncellendi —
        proje veya teklif acilmadi.
    </div>
<?php endif; ?>

<a href="<?= Url::to('/quick-capture') ?>" class="btn btn-primary">Yeni Kayit Ekle</a>
<?php if ($projectId !== null): ?>
    <a href="<?= Url::to('/projects/show') ?>?id=<?= $projectId ?>" class="btn btn-success">Projeyi ve Teklifleri Goruntule</a>
<?php endif; ?>
<a href="<?= Url::to('/customers/edit') ?>?id=<?= $customerId ?>" class="btn btn-outline-secondary">Musteriye Git</a>
