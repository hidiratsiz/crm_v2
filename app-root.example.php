<?php

/**
 * JobPro - APP_ROOT Override (İSTEĞE BAĞLI / GELİŞMİŞ KULLANIM)
 *
 * VARSAYILAN DURUM: Bu dosyaya hiç ihtiyacınız yok. Proje tek parça
 * halinde (index.php, app/, config/, hepsi ayni klasorde) herhangi bir
 * yere yuklendiginde kendiliginden calisir — app/, config/ gibi
 * klasorler kendi ic .htaccess dosyalariyla tarayicidan zaten korunur.
 *
 * NE ZAMAN KULLANILIR: Sadece app/, config/, routes/, resources/,
 * database/, storage/ klasorlerini FIZIKSEL olarak web'e acik klasorun
 * (bu dosyanin bulundugu klasor) DISINA, ayrı bir konuma koymak
 * isterseniz (bazi hostinglerde ekstra guvenlik katmani icin) bu dosyayi
 * "app-root.php" olarak kopyalayip asagidaki yolu kendi gercek sunucu
 * yolunuzla degistirin:
 *
 *    return '/home/kullaniciadi/jobpro-core';
 *
 * GitHub Actions kullaniyorsaniz bu dosyayi elle olusturmaniza GEREK
 * YOK — APP_ROOT_PATH secret'ini bos birakirsaniz basit tek-klasor
 * modu kullanilir; deger girerseniz bu dosya otomatik uretilir.
 */

return null;
