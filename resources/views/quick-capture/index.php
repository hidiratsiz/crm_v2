<?php use App\Core\Csrf; use App\Core\Url; ?>
<h2 class="mb-2">Hizli Kayit</h2>
<p class="text-muted mb-3">
    Musteriyle telefonda konustuktan sonra buraya yazin (veya telefonunuzun klavyesindeki
    mikrofon/sesli yazma tusuyla dikte edin). Yapay zeka musteri bilgilerini ve is detaylarini
    okuyup sisteme kaydedecek.
</p>

<div class="alert alert-light border mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <strong>Bu kutuya yazabilecekleriniz:</strong>
        <ul class="mb-0 mt-2">
            <li><strong>Yeni kayit:</strong> "63 Grand Valley, musteri Leah, guverte onarimi istiyor..."</li>
            <li><strong>Komut:</strong> "Jane'in isine 200 dolar gider ekle", "3 nolu is tamamlandi", "David K'ye teklifi gonder"</li>
            <li><strong>Soru:</strong> "David K'nin bakiyesi ne?", "kimde ne kadar para var?", "bu hafta hangi isler var?"</li>
        </ul>
    </div>
    <a href="<?= Url::to('/quick-capture/help') ?>" class="btn btn-outline-secondary btn-sm text-nowrap">Neler yapabilir?</a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= Url::to('/quick-capture') ?>" class="card p-4 shadow-sm" id="quick-capture-form">
    <?= Csrf::field() ?>
    <div class="mb-3">
        <textarea name="raw_text" class="form-control" rows="10" required
                  placeholder="Ornek: 63 Grand Valley Bulvari. Musteri adi: Leah. Musteri guverte onarimi ve boyama istiyor. 3 adet 2x4 direk degistirilecek...

veya bir komut: Jane'in isine Alex gitsin"
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
