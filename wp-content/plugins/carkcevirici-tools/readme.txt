=== Çark Çevirici Araçları ===
Contributors: carkcevirici
Tags: wheel, spinner, random, draw, turkish
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.2.0
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

= 1.2.0 =
* Fixed: the winner popup rendered with broken/unreadable colors (showed up as a "blue screen" for some users) because it was appended outside the `.cark-wheel` element and lost access to its CSS theming variables. Fixed by carrying the active theme's resolved colors onto the popup.
* Added: win-tally statistics panel -- shows how many times each entry has won, with a percentage bar per entry, plus a one-line summary ("12 çeviriş · en çok kazanan: Ahmet (5 kez)").
* Added: winners history now persists in the browser across page reloads (previously reset on every visit).
* Added: "Geçmişi Temizle" (clear history) button.
* Added: "Kopyala" button on the winner popup to copy the winner's name directly.
* Added: short vibration feedback on mobile devices when a winner is announced.
* The downloaded `.txt` history file now includes the win-tally summary and a timestamp per entry.

= 1.1.0 =
* `[iletisim_formu]` kısa kodu eklendi: honeypot korumalı, e-posta ile iletişim formu (harici form eklentisi gerektirmez).

= 1.0.0 =
* İlk sürüm: çark motoru, 5 ek araç, hazır liste sistemi, embed ve llms.txt uç noktaları.
