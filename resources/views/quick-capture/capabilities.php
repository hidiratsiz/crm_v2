<?php use App\Core\Url; ?>
<h2 class="mb-4">Hizli Kayit Neler Yapabilir?</h2>

<div class="card p-4 shadow-sm mb-4">
    <h6 class="mb-2">1. Yeni musteri / is / teklif kaydi</h6>
    <p class="text-muted mb-2">Telefon gorusmesinden sonra serbest metin yazin (veya dikte edin); musteri, proje ve teklif otomatik olusur.</p>
    <ul class="mb-0">
        <li>"Yeni musteri: Ahmet Yilmaz, 0555 123 4567, Kadikoy" — sadece musteri ekler</li>
        <li>"63 Grand Valley. Musteri Leah. Guverte onarimi istiyor, 3 direk degisecek, teklif $1400" — musteri + proje + teklif olusturur</li>
        <li>"...bugun saat 14:30'da incelemeye gidecegiz" — ayrica inceleme randevusu ekler (takvimde gorunur)</li>
    </ul>
</div>

<div class="card p-4 shadow-sm mb-4">
    <h6 class="mb-2">2. Mevcut isler uzerinde komutlar</h6>
    <p class="text-muted mb-2">Musteri adiyla ya da is numarasiyla ("3 nolu is") hedefleyebilirsiniz:</p>
    <ul class="mb-0">
        <li>"Jane'in isine Alex gitsin" — calisan atar, e-posta bildirimi gider</li>
        <li>"Alex'i Jane'in isinden cikar" — atamayi kaldirir</li>
        <li>"Jane'in isine 200 dolar malzeme gideri ekle" — gider kaydeder</li>
        <li>"Jane'in isine 500 dolar odeme alindi" — odeme/tahsilat kaydeder</li>
        <li>"Jane'in isine 'eski dolaplari sok' adimini ekle" — kontrol listesine adim ekler</li>
        <li>"Jane'in isi 1 Agustos'ta baslasin" / "...saat 14:00'te, 2 saat surecek" — tarih/saat belirler</li>
        <li>"Jane'in isi tamamlandi" — durumu gunceller</li>
        <li>"3 nolu ise 50 dolar benzin gideri ekle" — is numarasiyla ayni komutlar</li>
    </ul>
</div>

<div class="card p-4 shadow-sm mb-4">
    <h6 class="mb-2">3. Teklif duzenleme ve gonderme</h6>
    <ul class="mb-0">
        <li>"David K teklifini duzenle" + yeni is kapsami + tutar — musterinin son TASLAK teklifini gunceller</li>
        <li>"David K'ye teklifi gonder" — son teklifi musteriye e-posta ile gonderir</li>
    </ul>
</div>

<div class="card p-4 shadow-sm mb-4">
    <h6 class="mb-2">4. Veri sorulari (yeni!)</h6>
    <p class="text-muted mb-2">Kayitli verileriniz hakkinda serbestce soru sorabilirsiniz; yanit veritabanindan gelir:</p>
    <ul class="mb-0">
        <li>"David K'nin kalan bakiyesi ne kadar?"</li>
        <li>"Bu hafta hangi isler var?"</li>
        <li>"Kimde ne kadar para var?"</li>
        <li>"Toplam gelirimiz ve net karimiz ne?"</li>
        <li>"En karli isimiz hangisi?"</li>
        <li>"Alex hangi islerde calisiyor?"</li>
    </ul>
</div>

<div class="alert alert-light border">
    Ipucu: telefon klavyenizdeki mikrofon tusuyla tum bunlari dikte de edebilirsiniz.
</div>

<a href="<?= Url::to('/quick-capture') ?>" class="btn btn-primary">Hizli Kayit'a Don</a>
