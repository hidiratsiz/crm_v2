# JobPro — Faz 1 (Foundation) Kurulum Rehberi

Bu paket şunları içerir:
- Auth (giriş/çıkış) sistemi
- Rol tabanlı yetkilendirme (RBAC): Admin, Office Staff, Estimator, Employee, Read-only
- Müşteri Yönetimi (listeleme, arama, ekleme, düzenleme, silme)
- Temel Dashboard
- CSRF koruması, bcrypt şifreleme, PDO ile SQL injection koruması
- Otomatik veritabanı kurulum/güncelleme sistemi (migration)
- GitHub Actions ile otomatik deploy (isteğe bağlı)

Composer veya Node.js **gerekmez** — sadece PHP 8.1+ ve MySQL 8 yeterlidir.

---

## Önemli: Proje Tamamen Kendi Kendine Yeterli (Self-Contained)

Bu proje **tek bir klasör** olarak tasarlandı. `index.php`, `app/`, `config/` — hepsi aynı klasörün içinde bir arada durur. Projeyi hosting'inizde **herhangi bir yere** (domain kökü, `/jobpro`, `/crm`, `/projeler/musteri1`, fark etmez) **olduğu gibi, hiçbir şeyi taşımadan veya bölmeden** yükleyip kullanabilirsiniz.

`app/`, `config/`, `routes/`, `resources/`, `database/`, `storage/` klasörlerinin her birinin **kendi içinde** bir `.htaccess` dosyası var — bu, birisi tarayıcıdan doğrudan o klasöre girmeye çalışırsa (örn. `sitenizadi.com/jobpro/config/config.php`) **403 Forbidden** almasını sağlar, ama `index.php`'nin bu dosyaları arka planda okumasını **etkilemez**. Yani ekstra bir "web dışı klasör" oluşturmanıza veya `public_html` dışında bir yere dosya taşımanıza gerek yoktur.

**Tek gereksinim:** Apache üzerinde `mod_rewrite` ve `.htaccess` desteğinin açık olması (`AllowOverride All` veya en azından `AllowOverride AuthConfig Limit`) — bu, hemen hemen tüm paylaşımlı hostinglerde (cPanel dahil) varsayılan olarak açıktır.

---

## 1. Hosting Gereksinimleri

- PHP 8.1 veya üzeri (PHP 8.3 önerilir)
- MySQL 8 (veya MariaDB 10.4+)
- Apache + `mod_rewrite` aktif
- PDO ve PDO_MySQL PHP eklentileri (standart olarak gelir)

## 2. Dosyaları Yükleme

Bu klasörün **tamamını** (dosya adlarını/yapısını değiştirmeden) hosting hesabınızda istediğiniz klasöre FTP/cPanel Dosya Yöneticisi ile yükleyin — örn. `public_html/jobpro/`. Başka hiçbir klasörü taşımanıza veya `public_html` dışına bir şey çıkarmanıza gerek yoktur.

Kurulumdan hemen sonra tarayıcıda projenin yüklendiği adrese gidin (örn. `https://alanadiniz.com/jobpro/config/config.php`) — **403 Forbidden** almalısınız. Bu, koruma dosyalarının doğru çalıştığının kanıtıdır.

## 3. Bağlantı Ayarları

Önce `.env.example` dosyasını kopyalayıp `.env` olarak kaydedin (proje kökünde, `index.php`'nin yanına), sonra içindeki alanları hosting'inizden aldığınız gerçek bilgilerle değiştirin:

```
DB_HOST=127.0.0.1
DB_NAME=jobpro_db
DB_USER=jobpro_user
DB_PASS=GERCEK_SIFRENIZ

APP_URL=https://alanadiniz.com/jobpro
MIGRATE_TOKEN=UZUN_RASTGELE_BIR_SIFRE
```

`config/config.php` dosyasının kendisi artık **hiçbir gizli bilgi içermez** — sadece `.env`'i okur. Bu yüzden `config.php`'yi elle düzenlemenize gerek yok, sadece `.env`'i doldurmanız yeterli. cPanel → **MySQL Databases** bölümünden veritabanı ve kullanıcıyı (henüz yoksa) oluşturup kullanıcıyı veritabanına "All Privileges" ile bağlamayı unutmayın.

**Güvenlik notu:** `.env` dosyası proje kökünde durur ama kök `.htaccess` dosyası, adı nokta ile başlayan tüm dosyalara (`.env` dahil) tarayıcıdan erişimi engeller. Kurulumdan sonra `https://alanadiniz.com/jobpro/.env` adresine gidip **403 Forbidden** aldığınızı doğrulamanız önerilir.

## 4. Veritabanı Kurulumu

**Önerilen yöntem — migrate.php'yi bir kere ziyaret edin:**

Tarayıcıda şu adrese gidin (kendi alan adınız ve `.env`'e yazdığınız token ile):

```
https://alanadiniz.com/jobpro/migrate.php?token=UZUN_RASTGELE_BIR_SIFRE
```

Bu, tüm tabloları oluşturur, rolleri/izinleri ekler ve varsayılan bir admin kullanıcısı oluşturur:
- **E-posta:** `admin@jobpro.local`
- **Şifre:** `Admin123!`
- ⚠️ **İlk girişten hemen sonra bu şifreyi değiştirmenizi şiddetle tavsiye ederim.**

İleride kodu güncellediğinizde (yeni migration dosyaları eklendiğinde), aynı adresi tekrar ziyaret etmeniz yeterli — sadece yeni değişiklikler uygulanır.

**Alternatif yöntem — phpMyAdmin ile elle import:**

**Not:** Veritabanı kurulumu için tek yöntem `migrate.php`'dir — bu, `database/migrations/` altındaki tüm dosyaları sırayla uygular ve hem ilk kurulumda hem gelecekteki her güncellemede kullanılır. Ayrı bir "tek seferlik SQL dosyası" tutulmuyor, böylece migration'lar her zaman tek doğru kaynak olur.

## 5. Dosya İzinleri

`storage/` klasörüne yazma izni verin:

```
chmod -R 755 storage/
```

## 6. Test

1. `https://alanadiniz.com/jobpro/login` adresine gidin.
2. Yukarıdaki admin bilgileriyle giriş yapın.
3. Dashboard ve Müşteriler sayfalarının çalıştığını doğrulayın.
4. `https://alanadiniz.com/jobpro/.env` gibi bir adrese gidip **403 aldığınızı** doğrulayın (güvenlik kontrolü).

### Giriş sonrası 500 hatası alıyorsanız

Bunu kapsamlı şekilde test ettim (gerçek MySQL ile login + dashboard akışının tamamı sorunsuz çalıştı), ama hosting ortamınıza özgü birkaç olası sebep var — sırasıyla kontrol edin:

1. **PHP sürümü:** cPanel → **MultiPHP Manager**'dan bu alan adı/klasör için PHP **8.1 veya üzeri** seçili olduğundan emin olun. Bazı hostingler varsayılan olarak eski bir PHP sürümü (7.4 gibi) kullanır, bu da 500'e yol açar.
2. **`.env` dosyası oluşturuldu mu?** `.env.example`'ı `.env` olarak kopyalamadıysanız, veritabanı bağlantı bilgileri varsayılan (yanlış) değerlere düşer ve bağlantı hatası 500 olarak görünür.
3. **Migration çalıştı mı?** `https://alanadiniz.com/jobpro/migrate.php?token=...&status=1` adresine gidip kaç migration'ın uygulandığını kontrol edin — tablo eksikse dashboard sorguları hata verir.
4. **Gerçek hata mesajını görmek için:** `.env` içinde geçici olarak `APP_DEBUG=true` yapıp sayfayı tekrar açın — ekranda tam hata mesajını göreceksiniz (test bitince tekrar `false` yapmayı unutmayın). Alternatif olarak cPanel'in **"Errors"** bölümünden veya `storage/logs/` altından hata kaydına bakabilirsiniz.

Yukarıdakilerden sonuç alamazsanız, `APP_DEBUG=true` iken ekranda çıkan tam hata mesajını paylaşın, kesin sebebini bulalım.

---

## 7. Otomatik Deploy + Otomatik Veritabanı Güncelleme (İsteğe Bağlı, Önerilen)

Bu paket **GitHub Actions ile otomatik deploy** desteği içeriyor. Kurulduktan sonra tek yapmanız gereken `git push` — GitHub, dosyaları hosting'e kopyalar ve veritabanını otomatik günceller.

### Nasıl çalışıyor?

1. **`database/migrations/`** klasöründe, her biri tek bir değişikliği temsil eden numaralı `.sql` dosyaları var.
2. **`app/Core/Migrator.php`** ve **`migrate.php`**, veritabanında hangi migration'ların zaten uygulandığını bir `migrations` tablosunda takip eder — sadece yeni/eksik olanları çalıştırır.
3. `.github/workflows/deploy.yml`, her push'ta (veya elle tetiklendiğinde):
   - `.env`'i GitHub Secrets'tan güvenli şekilde üretir (gerçek şifreler asla GitHub'a/koda yazılmaz),
   - **tüm proje klasörünü olduğu gibi** (bölmeden) hosting'e FTP ile yükler,
   - son olarak `migrate.php`'yi bir HTTP isteğiyle tetikleyerek veritabanını günceller.

### Kurulum (bir kere yapılır)

1. Bu projeyi bir GitHub deposuna push edin (`.env` `.gitignore` ile hariç tutulduğu için gerçek şifreleriniz asla GitHub'a gitmez — `config/config.php` artık gizli bilgi içermediği için normal şekilde depoya dahildir).
2. GitHub deposunda **Settings → Secrets and variables → Actions** bölümüne şu secret'ları ekleyin:

| Secret adı | Açıklama | Örnek |
|---|---|---|
| `FTP_SERVER` | Hosting FTP/FTPS adresi | `ftp.taskergenius.com` |
| `FTP_USERNAME` | FTP kullanıcı adı | `tasker_ftp` |
| `FTP_PASSWORD` | FTP şifresi | `********` |
| `FTP_DEPLOY_DIR` | Projenin tamamının gideceği tek hosting yolu | `/public_html/jobpro/` (veya `/public_html/crm/`, ne isterseniz) |
| `APP_ROOT_PATH` | **Boş bırakın** (varsayılan tek-klasör modu için) — sadece gelişmiş split-folder kurulumu istiyorsanız doldurun | *(genelde boş)* |
| `DB_HOST` | Veritabanı sunucu adresi | `127.0.0.1` |
| `DB_PORT` | Veritabanı portu | `3306` |
| `DB_NAME` | Veritabanı adı | `jobpro_db` |
| `DB_USER` | Veritabanı kullanıcısı | `jobpro_user` |
| `DB_PASS` | Veritabanı şifresi | `********` |
| `APP_URL` | Sitenizin tam adresi (migrate.php'ye ulaşmak için) | `https://taskergenius.com/jobpro` |
| `APP_TIMEZONE` | Zaman dilimi | `America/Toronto` |
| `MIGRATE_TOKEN` | `migrate.php`'yi koruyan uzun rastgele şifre | `php -r "echo bin2hex(random_bytes(32));"` komutuyla üretin |

3. cPanel'de veritabanını ve FTP kullanıcısını (henüz yoksa) oluşturun.
4. `main` dalına ilk push'u yapın — GitHub Actions sekmesinden ("Actions" tab) işlemi izleyebilirsiniz.
5. İşlem bitince sitenize gidin, admin bilgileriyle giriş yapın.

**Not:** `FTP_DEPLOY_DIR` hosting hesabınızın FTP kök dizinine göre değişir. İlk push'tan sonra dosyaların doğru yere gidip gitmediğini FTP/cPanel Dosya Yöneticisi'nden kontrol edin, gerekirse secret değerini düzeltip tekrar push edin.

### Çoklu Ortam Kurulumu (Aynı Depo, Farklı Müşteriler/Klasörler)

Workflow **iki farklı şekilde çalışır**:

1. **`main` dalına push** → otomatik olarak `production` adlı ortama deploy eder.
2. **GitHub'da "Actions" sekmesinden elle çalıştırma** ("Run workflow" butonu) → hangi ortama deploy edileceğini bir açılır listeden seçersiniz (örn. `crm`, `musteri1`), ve o ortamın **kendi secret'ları** kullanılır.

Böylece tek bir GitHub deposundan, her biri farklı domain/klasör/veritabanına sahip **sınırsız sayıda bağımsız kurulum** yönetebilirsiniz.

**Kurulum adımları:**

1. GitHub deposunda **Settings → Environments → New environment** yolunu izleyin.
2. İlk ortamı oluşturun, adını `production` yapın.
3. O ortamın sayfasında **"Environment secrets"** bölümüne yukarıdaki tablodaki **tüm secret'ları** bu projenin gerçek değerleriyle ekleyin.
4. İkinci bir müşteri/proje için tekrar **New environment**'a tıklayıp örn. `crm` adında yeni bir ortam oluşturun, aynı secret listesini **o projenin kendi bilgileriyle** doldurun.
5. Belirli bir ortama deploy etmek için: **Actions** sekmesi → **"Deploy JobPro to Hosting"** → **"Run workflow"** → açılır listeden ortamı seçin → **Run workflow**.

**Not:** `production` dışındaki ortamlar sadece elle tetiklenir — bu, yanlışlıkla başka bir müşterinin sitesine deploy yapmanızı engeller.

---

## 8. Gelişmiş / İsteğe Bağlı: Split-Folder Kurulumu

Varsayılan tek-klasör modu (yukarıda anlatılan) çoğu kullanım için yeterlidir. Ancak bazı hostinglerde ekstra güvenlik katmanı olarak `app/`, `config/` gibi klasörleri **fiziksel olarak** web'e açık klasörün dışına koymak isterseniz:

1. `app-root.example.php` dosyasını `app-root.php` olarak kopyalayın (projenin kök dizinine, `index.php`'nin yanına).
2. İçindeki `return null;` satırını, `app/config/routes/resources/database/storage` klasörlerini taşıyacağınız gerçek sunucu yoluyla değiştirin: `return '/home/kullaniciadi/jobpro-core';`
3. O klasörleri gerçekten oraya taşıyın.
4. GitHub Actions kullanıyorsanız, bunun yerine sadece `APP_ROOT_PATH` secret'ını doldurmanız yeterli — `app-root.php` otomatik üretilir.

Bu adım **isteğe bağlıdır** ve çoğu kurulumda gerekmez.

---

## Faz 2 (Başladı): AI Destekli Hızlı Kayıt

Artık sistemde **"+ Hızlı Kayıt"** adlı bir kutu var (sol menüde, giriş yaptıktan sonra). Nasıl çalışır:

1. Müşteriyle telefonda konuştuktan sonra notlarınızı bu kutuya yazın (veya telefonunuzun klavyesindeki mikrofon/sesli yazma tuşuyla dikte edin).
2. "Gönder" dediğinizde, yapılandırdığınız AI sağlayıcı (Claude/OpenAI) metni okuyup şunları çıkarır: müşteri adı, telefon, **e-posta**, adres, hizmet türü, iş detayları, ve **birden fazla teklif seçeneği isteniyorsa hepsini ayrı ayrı**.
3. Telefon numarasından (yoksa isimden) müşteri tanınır — varsa mevcut kayda proje eklenir, yoksa yeni müşteri açılır.
4. Her teklif seçeneği için bir taslak kayıt oluşturulur (başlık + iş kapsamı metni).
5. **Sadece müşteri bilgisi verirseniz** (örn. "Yeni müşteri ekle: Ahmet Yılmaz, 05551234567, ahmet@mail.com, Kadıköy") — iş/hizmet tanımlanmadığı için sistem sadece müşteri kaydı oluşturur/günceller, gereksiz bir proje açmaz.
6. **Mevcut bir müşterinin eksik bilgisini** (örn. daha önce e-postası yoktu, şimdi verdiniz) otomatik tamamlar — ama zaten dolu olan bilgilerin üzerine asla yazmaz, mevcut doğru veriyi bozmaz.

**Şu an eksik olan:** Tekliflerin otomatik **fiyat hesaplaması** yok — bu, sıradaki Dinamik Fiyatlandırma Motoru (servis modülleri, m²/adet bazlı fiyat kuralları) ile gelecek. Şimdilik teklifler taslak metin olarak kaydediliyor, fiyatlandırmayı elle ekleyebilirsiniz.

### AI Sağlayıcı Kurulumu

`.env` dosyasında:

```
AI_PROVIDER=gemini
GEMINI_API_KEY=AIza...
GEMINI_MODEL=gemini-2.5-flash
```

**Gemini API anahtarı almak için:** [aistudio.google.com/apikey](https://aistudio.google.com/apikey) üzerinden Google hesabınızla giriş yapıp ücretsiz bir API anahtarı oluşturabilirsiniz — Gemini'nin ücretsiz kullanım kotası bu tür metin çıkarma işleri için genelde yeterlidir.

Claude (Anthropic) kullanmak isterseniz sadece şunu değiştirmeniz yeterli:
```
AI_PROVIDER=anthropic
ANTHROPIC_API_KEY=sk-ant-...
ANTHROPIC_MODEL=claude-haiku-4-5-20251001
```

**Anthropic (Claude) API anahtarı almak için:** [console.anthropic.com](https://console.anthropic.com) üzerinden hesap açıp telefon doğrulaması yaptığınızda ~$5 ücretsiz deneme kredisi tanımlanır (kredi kartı gerekmez).

OpenAI kullanmak isterseniz:
```
AI_PROVIDER=openai
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4o-mini
```

Kodda **hiçbir değişiklik gerekmez** — sağlayıcı tamamen `.env` üzerinden seçilir.

**Gereksinim:** Hosting'inizde PHP `curl` eklentisinin aktif olması gerekir (neredeyse tüm paylaşımlı hostinglerde varsayılan olarak açıktır).

### Sırada Ne Var (Faz 2'nin devamı)

- Servis modülü tanımlama ekranı (Admin, kod yazmadan yeni hizmet ekleyebilecek)
- Dinamik form alanları ve fiyatlandırma kuralları (kademeli fiyat, m²/adet bazlı)
- Tekliflerin otomatik fiyatlandırılması ve PDF olarak müşteriye gönderilmesi
- Canlı fiyat hesaplama (Fetch API ile)

Hazır olduğunuzda haber verin, bir sonraki parçayı da aynı şekilde tam çalışır kod olarak hazırlarım.
