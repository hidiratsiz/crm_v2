<?php use App\Core\Csrf; use App\Core\Url; ?>
<h2 class="mb-4">Servisi Duzenle: <?= htmlspecialchars($module['name']) ?></h2>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= Url::to('/service-modules/update') ?>" class="card p-4 shadow-sm mb-4" style="max-width: 600px;">
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= $module['id'] ?>">
    <div class="mb-3">
        <label class="form-label">Servis Adi *</label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($module['name']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Aciklama</label>
        <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($module['description'] ?? '') ?></textarea>
    </div>
    <button type="submit" class="btn btn-outline-primary">Bilgileri Guncelle</button>
</form>

<h4 class="mb-3">Alanlar</h4>

<?php if (empty($fields)): ?>
    <p class="text-muted">Henuz bir alan eklenmedi. Asagidan ekleyin.</p>
<?php else: ?>
    <div class="table-responsive mb-4">
        <table class="table table-sm bg-white shadow-sm">
            <thead><tr><th>Etiket</th><th>Tur</th><th>Fiyatlandirma</th><th>Detay</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($fields as $field): ?>
                <tr>
                    <td><?= htmlspecialchars($field['label']) ?></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($field['field_type']) ?></span></td>
                    <td><?= htmlspecialchars($field['pricing_method']) ?></td>
                    <td class="small text-muted">
                        <?php if ($field['pricing_method'] === 'per_unit'): ?>
                            Birim fiyat: $<?= number_format((float) $field['unit_price'], 2) ?>
                        <?php elseif ($field['pricing_method'] === 'fixed'): ?>
                            Sabit: $<?= number_format((float) $field['fixed_price'], 2) ?>
                        <?php elseif ($field['pricing_method'] === 'tiered' && !empty($field['tiers_json'])): ?>
                            <?php foreach (json_decode($field['tiers_json'], true) as $tier): ?>
                                <?= htmlspecialchars($tier['min']) ?>-<?= $tier['max'] !== null ? htmlspecialchars($tier['max']) : '+' ?>: $<?= number_format((float) $tier['price'], 2) ?><br>
                            <?php endforeach; ?>
                        <?php elseif ($field['pricing_method'] === 'dropdown_priced' && !empty($field['options_json'])): ?>
                            <?php foreach (json_decode($field['options_json'], true) as $opt): ?>
                                <?= htmlspecialchars($opt['label']) ?>: $<?= number_format((float) $opt['price'], 2) ?><br>
                            <?php endforeach; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td>
                        <form action="<?= Url::to('/service-modules/fields/delete') ?>" method="post"
                              onsubmit="return confirm('Bu alani silmek istediginizden emin misiniz?');">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= $field['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Sil</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<div class="card p-4 shadow-sm mb-4">
    <h5 class="mb-3">Yeni Alan Ekle</h5>
    <form action="<?= Url::to('/service-modules/fields/add') ?>" method="post" id="add-field-form">
        <?= Csrf::field() ?>
        <input type="hidden" name="service_module_id" value="<?= $module['id'] ?>">

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Etiket *</label>
                <input type="text" name="label" class="form-control" placeholder="orn. Alan (m²)" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Alan Turu *</label>
                <select name="field_type" id="field_type" class="form-select" required>
                    <option value="number">Sayi (m², adet, vb.)</option>
                    <option value="checkbox">Onay Kutusu (evet/hayir)</option>
                    <option value="dropdown">Acilir Liste</option>
                    <option value="text">Serbest Metin (fiyatsiz)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Zorunlu mu?</label>
                <select name="is_required" class="form-select">
                    <option value="0">Hayir</option>
                    <option value="1">Evet</option>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Fiyatlandirma Yontemi *</label>
            <select name="pricing_method" id="pricing_method" class="form-select" required>
                <option value="none">Fiyatsiz (sadece bilgi)</option>
                <option value="per_unit">Birim Fiyat (deger x birim fiyat)</option>
                <option value="tiered">Kademeli Fiyat (araliklara gore)</option>
                <option value="fixed">Sabit Ucret (onay kutusu isaretlenirse)</option>
                <option value="dropdown_priced">Secenege Gore Fiyat (acilir liste)</option>
            </select>
            <small class="text-muted">
                Sayi alanlari icin: Birim Fiyat veya Kademeli Fiyat. Onay kutusu icin: Sabit Ucret.
                Acilir liste icin: Secenege Gore Fiyat.
            </small>
        </div>

        <div id="section-per_unit" class="pricing-section mb-3" style="display:none;">
            <label class="form-label">Birim Fiyat ($)</label>
            <input type="text" name="unit_price" class="form-control" placeholder="orn. 3.50" style="max-width:200px;">
            <small class="text-muted">Ornek: m² basina $3.50 ise, kullanici 400 girerse toplam $1400 hesaplanir.</small>
        </div>

        <div id="section-fixed" class="pricing-section mb-3" style="display:none;">
            <label class="form-label">Sabit Ucret ($)</label>
            <input type="text" name="fixed_price" class="form-control" placeholder="orn. 150" style="max-width:200px;">
        </div>

        <div id="section-tiered" class="pricing-section mb-3" style="display:none;">
            <label class="form-label">Kademeli Fiyat Araliklari</label>
            <small class="text-muted d-block mb-2">Bos birakilan satirlar yok sayilir. Son satirda "Max" bos birakilirsa "ve ustu" anlamina gelir.</small>
            <table class="table table-sm">
                <thead><tr><th>Min</th><th>Max</th><th>Fiyat ($)</th></tr></thead>
                <tbody>
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <tr>
                        <td><input type="text" name="tier_min[]" class="form-control form-control-sm" placeholder="0"></td>
                        <td><input type="text" name="tier_max[]" class="form-control form-control-sm" placeholder="200"></td>
                        <td><input type="text" name="tier_price[]" class="form-control form-control-sm" placeholder="250"></td>
                    </tr>
                <?php endfor; ?>
                </tbody>
            </table>
        </div>

        <div id="section-dropdown_priced" class="pricing-section mb-3" style="display:none;">
            <label class="form-label">Acilir Liste Secenekleri</label>
            <small class="text-muted d-block mb-2">Bos birakilan satirlar yok sayilir.</small>
            <table class="table table-sm">
                <thead><tr><th>Secenek Adi</th><th>Fiyat ($)</th></tr></thead>
                <tbody>
                <?php for ($i = 0; $i < 8; $i++): ?>
                    <tr>
                        <td><input type="text" name="option_label[]" class="form-control form-control-sm" placeholder="orn. Wood Railing"></td>
                        <td><input type="text" name="option_price[]" class="form-control form-control-sm" placeholder="350"></td>
                    </tr>
                <?php endfor; ?>
                </tbody>
            </table>
        </div>

        <button type="submit" class="btn btn-primary">+ Alan Ekle</button>
    </form>
</div>

<a href="<?= Url::to('/service-modules') ?>" class="btn btn-outline-secondary">Servis Listesine Don</a>

<script>
(function () {
    var pricingSelect = document.getElementById('pricing_method');
    var sections = document.querySelectorAll('.pricing-section');

    function updateVisibility() {
        var selected = pricingSelect.value;
        sections.forEach(function (section) {
            section.style.display = (section.id === 'section-' + selected) ? 'block' : 'none';
        });
    }

    pricingSelect.addEventListener('change', updateVisibility);
    updateVisibility();
})();
</script>
