<?php use App\Core\Csrf; use App\Core\Url; ?>
<h2 class="mb-4">Teklifi Duzenle</h2>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= Url::to('/estimates/update') ?>" class="card p-4 shadow-sm" style="max-width: 700px;" id="estimate-form">
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= $estimate['id'] ?>">

    <div class="mb-3">
        <label class="form-label">Teklif Basligi *</label>
        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($estimate['title']) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Servis (Otomatik Fiyatlandirma)</label>
        <select name="service_module_id" id="service_module_id" class="form-select">
            <option value="0">Manuel — servis secmeden serbest metin/tutar girecegim</option>
            <?php foreach ($serviceModules as $module): ?>
                <option value="<?= $module['id'] ?>" <?= (int) $estimate['service_module_id'] === (int) $module['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($module['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small class="text-muted">
            Bir servis secerseniz, ilgili alanlar (m², adet, secenekler) burada gorunur ve tutar otomatik hesaplanir. Servisi degistirirseniz alanlar sifirlanir.
        </small>
    </div>

    <div id="dynamic-fields-container" class="mb-3" style="display:none;">
        <div class="card p-3 bg-light">
            <div id="dynamic-fields"></div>
            <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2">
                <strong>Hesaplanan Toplam:</strong>
                <strong id="calculated-total">$0.00</strong>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Aciklama / Is Kapsami</label>
        <textarea name="description" class="form-control" rows="5"><?= htmlspecialchars($estimate['description'] ?? '') ?></textarea>
    </div>

    <div class="mb-3" id="manual-amount-wrapper">
        <label class="form-label">Tutar ($)</label>
        <input type="text" name="amount" id="manual-amount" class="form-control"
               value="<?= $estimate['amount'] !== null ? htmlspecialchars((string) $estimate['amount']) : '' ?>">
        <small class="text-muted" id="manual-amount-hint"></small>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Guncelle</button>
        <a href="<?= Url::to('/projects/show') ?>?id=<?= $estimate['project_id'] ?>" class="btn btn-outline-secondary">Iptal</a>
    </div>
</form>

<script>
(function () {
    var serviceSelect = document.getElementById('service_module_id');
    var fieldsContainer = document.getElementById('dynamic-fields-container');
    var fieldsDiv = document.getElementById('dynamic-fields');
    var totalDisplay = document.getElementById('calculated-total');
    var manualAmountInput = document.getElementById('manual-amount');
    var manualAmountHint = document.getElementById('manual-amount-hint');

    var fieldsJsonUrl = <?= json_encode(Url::to('/service-modules/fields-json')) ?>;
    var calculateUrl = <?= json_encode(Url::to('/service-modules/calculate')) ?>;

    // field_id (int) => previously stored value (string|null), so the fields
    // this estimate already has get pre-filled instead of starting blank.
    var existingValues = <?= json_encode($existingValues, JSON_UNESCAPED_UNICODE) ?>;
    // Only applied on the very first render (the module the estimate was
    // saved with) — if the user switches services afterwards, that's a new
    // set of fields and prefilling old values by coincidence-of-id would be
    // wrong, so it's cleared once used.
    var initialModuleId = <?= json_encode($estimate['service_module_id'] !== null ? (string) $estimate['service_module_id'] : '0') ?>;

    function inputNameFor(field) {
        return 'field_' + field.id;
    }

    function renderField(field) {
        var wrapper = document.createElement('div');
        wrapper.className = 'mb-2';

        var input;
        var prefill = Object.prototype.hasOwnProperty.call(existingValues, field.id) ? existingValues[field.id] : null;

        if (field.field_type === 'checkbox') {
            var checkWrap = document.createElement('div');
            checkWrap.className = 'form-check';

            input = document.createElement('input');
            input.type = 'checkbox';
            input.className = 'form-check-input';
            input.id = 'field_input_' + field.id;
            input.value = '1';
            if (prefill === '1' || prefill === 1) {
                input.checked = true;
            }

            var checkLabel = document.createElement('label');
            checkLabel.className = 'form-check-label';
            checkLabel.setAttribute('for', input.id);
            checkLabel.textContent = field.label + (field.is_required ? ' *' : '');

            checkWrap.appendChild(input);
            checkWrap.appendChild(checkLabel);
            wrapper.appendChild(checkWrap);
        } else {
            var label = document.createElement('label');
            label.className = 'form-label mb-1';
            label.textContent = field.label + (field.is_required ? ' *' : '');
            wrapper.appendChild(label);

            if (field.field_type === 'dropdown') {
                input = document.createElement('select');
                input.className = 'form-select';
                var emptyOpt = document.createElement('option');
                emptyOpt.value = '';
                emptyOpt.textContent = '-- Secin --';
                input.appendChild(emptyOpt);
                (field.options || []).forEach(function (opt) {
                    var o = document.createElement('option');
                    o.value = opt.value;
                    o.textContent = opt.label + (opt.price ? ' (+$' + parseFloat(opt.price).toFixed(2) + ')' : '');
                    if (prefill !== null && String(prefill) === String(opt.value)) {
                        o.selected = true;
                    }
                    input.appendChild(o);
                });
            } else {
                // number or text
                input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control';
                if (field.field_type === 'number') {
                    input.placeholder = '0';
                }
                if (prefill !== null) {
                    input.value = prefill;
                }
            }
            wrapper.appendChild(input);
        }

        input.name = inputNameFor(field);
        input.dataset.fieldId = field.id;
        if (field.is_required) {
            input.required = true;
        }
        input.addEventListener('input', recalculate);
        input.addEventListener('change', recalculate);

        return wrapper;
    }

    function loadFields(moduleId) {
        fieldsDiv.innerHTML = '';
        if (!moduleId || moduleId === '0') {
            fieldsContainer.style.display = 'none';
            manualAmountInput.readOnly = false;
            manualAmountHint.textContent = '';
            return;
        }

        fetch(fieldsJsonUrl + '?service_module_id=' + encodeURIComponent(moduleId))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                fieldsContainer.style.display = 'block';
                (data.fields || []).forEach(function (field) {
                    fieldsDiv.appendChild(renderField(field));
                });
                manualAmountInput.readOnly = true;
                manualAmountHint.textContent = 'Bu tutar secilen servise gore otomatik hesaplaniyor.';
                recalculate();
            })
            .catch(function () {
                fieldsContainer.style.display = 'none';
            });
    }

    function recalculate() {
        var moduleId = serviceSelect.value;
        if (!moduleId || moduleId === '0') {
            return;
        }

        var params = new URLSearchParams();
        params.set('service_module_id', moduleId);

        fieldsDiv.querySelectorAll('[data-field-id]').forEach(function (input) {
            var name = 'field_' + input.dataset.fieldId;
            var value = (input.type === 'checkbox') ? (input.checked ? '1' : '0') : input.value;
            params.set(name, value);
        });

        fetch(calculateUrl + '?' + params.toString())
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var total = data.total || 0;
                totalDisplay.textContent = '$' + parseFloat(total).toFixed(2);
                manualAmountInput.value = total;
            });
    }

    serviceSelect.addEventListener('change', function () {
        // Values only belong to the module the estimate was originally
        // saved with — once the user picks a different one, stop prefilling.
        existingValues = {};
        loadFields(serviceSelect.value);
    });

    // On page load, if this estimate already has a service module, render
    // its fields immediately (pre-filled) instead of waiting for the user
    // to touch the dropdown — that's the whole point of this screen.
    if (initialModuleId && initialModuleId !== '0') {
        loadFields(initialModuleId);
    }
})();
</script>
