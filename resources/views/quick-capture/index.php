<?php use App\Core\Csrf; use App\Core\Url; ?>
<h2 class="mb-2">Hizli Kayit</h2>
<p class="text-muted mb-4">
    Musteriyle telefonda konustuktan sonra buraya yazin (veya telefonunuzun klavyesindeki
    mikrofon/sesli yazma tusuyla dikte edin). Yapay zeka musteri bilgilerini ve is detaylarini
    okuyup sisteme kaydedecek.
</p>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= Url::to('/quick-capture') ?>" class="card p-4 shadow-sm" id="quick-capture-form">
    <?= Csrf::field() ?>
    <div class="mb-3">
        <textarea name="raw_text" class="form-control" rows="10" required
                  placeholder="Ornek: 63 Grand Valley Bulvari. Musteri adi: Leah. Musteri guverte onarimi ve boyama istiyor. 3 adet 2x4 direk degistirilecek..."
        ><?= htmlspecialchars($raw_text ?? '') ?></textarea>
    </div>
    <div class="d-flex justify-content-between align-items-center">
        <small class="text-muted">Ipucu: telefon klavyenizdeki mikrofon tusuyla direkt bu kutuya dikte edebilirsiniz.</small>
        <button type="submit" class="btn btn-primary" id="quick-capture-submit">Gonder</button>
    </div>
</form>

<script>
document.getElementById('quick-capture-form').addEventListener('submit', function () {
    var btn = document.getElementById('quick-capture-submit');
    btn.disabled = true;
    btn.textContent = 'Isleniyor...';
});
</script>
