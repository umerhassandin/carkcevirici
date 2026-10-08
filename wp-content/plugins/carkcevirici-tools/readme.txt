=== Çark Çevirici Araçları ===
Contributors: carkcevirici
Tags: wheel, spinner, random, draw, turkish
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Çark Çevirici'nin kendi çark ve rastgele seçim araçları. Harici kütüphane veya sayfa oluşturucu kullanmaz.

== Description ==

Bu eklenti, carkcevirici.com'un çekirdek ürünüdür:

* `[cark]` kısa kodu ve "Çark" Gutenberg bloğu -- ağırlıklı seçenekler, tema seçimi, kazananı kaldırma, çoklu kazanan, ses ve karanlık mod destekli dönen bir çark.
* `[takim_olusturucu]`, `[kura_cekme]`, `[sayi_secici]`, `[yazi_tura]`, `[zar_at]` -- beş ek rastgele seçim aracı.
* Ayarlar -> Çark Hazır Listeler ekranından kod yazmadan yeni hazır çark listesi eklenebilir.
* `/embed/` -- başka sitelere gömülebilen, noindex bir çark sayfası.
* `/llms.txt` ve `/llms-full.txt` -- AI arama motorları için otomatik üretilen site dizini.

Tüm JavaScript vanilla ES2020'dir; jQuery, React veya harici CDN kullanılmaz. Kazanan, dönüş animasyonu başlamadan önce `crypto.getRandomValues()` ile belirlenir.

== Installation ==

1. `wp-content/plugins/carkcevirici-tools` klasörünü sunucuya yükle.
2. WordPress yönetim panelinden eklentiyi etkinleştir.
3. Ayarlar -> Kalıcı Bağlantılar sayfasını aç ve "Kaydet"e bas (bu, `/embed/`, `/llms.txt` ve `/llms-full.txt` için gereken rewrite kurallarını yeniler).
4. Bir sayfaya `[cark preset="anasayfa"]` kısa kodunu ekle ya da Çark bloğunu kullan.

== Changelog ==

= 1.0.0 =
* İlk sürüm: çark motoru, 5 ek araç, hazır liste sistemi, embed ve llms.txt uç noktaları.
