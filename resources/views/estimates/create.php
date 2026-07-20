<?php use App\Core\Csrf; use App\Core\Url; ?>
<h2 class="mb-1">Yeni Teklif Ekle</h2>
<p class="text-muted mb-4">Proje: <?= htmlspecialchars($project['name']) ?></p>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= Url::to('/estimates/store') ?>" class="card p-4 shadow-sm" style="max-width: 700px;" id="estimate-form">
    <?= Csrf::field() ?>
    <input type="hidden" name="project_id" value="<?= $project['id'] ?>">

    <div class="mb-3">
        <label class="form-label">Teklif Basligi *</label>
        <input type="text" name="title" class="form-control" placeholder="orn. Teklif 3 - Alternatif Malzeme" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Servis (Otomatik Fiyatlandirma)</label>
        <select name="service_module_id" id="service_module_id" class="form-select">
            <option value="0">Manuel — servis secmeden serbest metin/tutar girecegim</option>
            <?php foreach ($serviceModules as $module): ?>
                <option value="<?= $module['id'] ?>"><?= htmlspecialchars($module['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <small class="text-muted">
            Bir servis secerseniz, ilgili alanlar (m², adet, secenekler) burada gorunur ve tutar otomatik hesaplanir.
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
        <textarea name="description" class="form-control" rows="5"></textarea>
    </div>

    <div class="mb-3" id="manual-amount-wrapper">
        <label class="form-label">Tutar ($)</label>
        <input type="text" name="amount" id="manual-amount" class="form-control" placeholder="orn. 1400">
        <small class="text-muted" id="manual-amount-hint"></small>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Kaydet</button>
        <a href="<?= Url::to('/projects/show') ?>?id=<?= $project['id'] ?>" class="btn btn-outline-secondary">Iptal</a>
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

    function inputNameFor(field) {
        return 'field_' + field.id;
    }

    function renderField(field) {
        var wrapper = document.createElement('div');
        wrapper.className = 'mb-2';

        var input;

        if (field.field_type === 'checkbox') {
            var checkWrap = document.createElement('div');
            checkWrap.className = 'form-check';

            input = document.createElement('input');
            input.type = 'checkbox';
            input.className = 'form-check-input';
            input.id = 'field_input_' + field.id;
            input.value = '1';

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
        loadFields(serviceSelect.value);
    });
})();
</script>
