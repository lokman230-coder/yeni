<?php

/**
 * 20 SEO odaklı blog yazısı seeder.
 * Hosting/domain sektöründe en çok aranan sorulara cevap.
 * Kullanım: php console seed --only=BlogSeoContentSeeder
 */

use App\Core\Database\Connection;
use App\Support\Slug;

$posts = [
    [
        'title'    => 'Web Hosting Nedir? Yeni Başlayanlar İçin Kapsamlı Rehber (2026)',
        'excerpt'  => 'Web hosting nedir, nasıl çalışır, türleri neler? İhtiyacınıza uygun hosting seçimi için bilmeniz gereken her şey.',
        'keywords' => 'web hosting nedir, hosting türleri, hosting rehberi',
        'body'     => <<<HTML
<p>Web hosting, internet sitenizin dosyalarının 7/24 çalışan sunucularda barındırılması hizmetidir. Bu yazıda hosting türlerini, seçim kriterlerini ve dikkat edilmesi gerekenleri açıklıyoruz.</p>
<h2>Hosting Türleri</h2>
<ul>
<li><strong>Paylaşımlı Hosting:</strong> Birden fazla sitenin aynı sunucuyu paylaştığı ekonomik çözüm.</li>
<li><strong>VPS Hosting:</strong> Sanal özel sunucu — kaynaklar size ayrılmış.</li>
<li><strong>Dedicated Server:</strong> Tüm sunucu size özel.</li>
<li><strong>Cloud Hosting:</strong> Ölçeklenebilir, dağıtık altyapı.</li>
<li><strong>Managed WordPress:</strong> WordPress için özel optimize edilmiş.</li>
</ul>
<h2>Nasıl Seçmeli?</h2>
<p>Sitenizin trafik hacmi, teknik bilginiz, bütçeniz ve büyüme planlarınıza göre karar verin. Aylık 10.000 ziyaretçiye kadar paylaşımlı, üstüne VPS, e-ticaret için cloud/managed önerilir.</p>
<h2>Ahost Bilişim Önerisi</h2>
<p>Başlangıç için <strong>Business Hosting</strong> paketimizi öneririz: NVMe SSD, LiteSpeed, sınırsız trafik, ücretsiz SSL ve günlük yedek.</p>
HTML,
    ],
    [
        'title'    => 'Domain Adı Nedir ve Nasıl Alınır? (Adım Adım Rehber)',
        'excerpt'  => 'Domain nedir, hangi uzantı seçilmeli, nasıl kaydedilir? Domain sahibi olmak isteyenler için detaylı kılavuz.',
        'keywords' => 'domain nedir, domain nasıl alınır, com.tr domain',
        'body'     => <<<HTML
<p>Domain adı, internet sitenizin adresidir (örn: ahost.web.tr). İnsanların sitenize kolayca ulaşmasını sağlar.</p>
<h2>Uzantı Seçimi</h2>
<ul>
<li><strong>.com</strong> — Global, en yaygın, önerilir.</li>
<li><strong>.com.tr</strong> — Türkiye için resmi belge gerektirir (marka, vergi).</li>
<li><strong>.net / .org</strong> — Teknoloji ve dernekler için.</li>
<li><strong>.dev / .io</strong> — Teknoloji şirketleri için trendy alternatifler.</li>
</ul>
<h2>Domain Alırken Dikkat</h2>
<ol>
<li>WHOIS gizliliği açık olsun (spam koruması).</li>
<li>Otomatik yenileme aktif olsun (kaybetmemek için).</li>
<li>Registrar transfer kilidi açık olsun.</li>
<li>DNS yönetim panelinize erişimin olduğundan emin olun.</li>
</ol>
<p>Domain sorgulama için <a href="/domain">domain arama aracımızı</a> kullanabilirsiniz.</p>
HTML,
    ],
    [
        'title'    => 'SSL Sertifikası Nedir ve Neden Zorunludur?',
        'excerpt'  => 'HTTPS, SSL/TLS sertifikaları nasıl çalışır, hangi türleri var, ücretsiz Let\'s Encrypt neden yeterli?',
        'keywords' => 'ssl nedir, https, let\'s encrypt, ssl sertifikası',
        'body'     => <<<HTML
<p>SSL (Secure Sockets Layer), siteniz ile ziyaretçi tarayıcısı arasındaki veri iletimini şifreler. Google 2018'den beri HTTP siteleri "güvenli değil" olarak işaretliyor.</p>
<h2>SSL Türleri</h2>
<ul>
<li><strong>DV (Domain Validated):</strong> Ücretsiz Let's Encrypt — çoğu site için yeterli.</li>
<li><strong>OV (Organization Validated):</strong> Firma belgesi gerekir.</li>
<li><strong>EV (Extended Validation):</strong> Yeşil çubuk — banka/finans için.</li>
<li><strong>Wildcard:</strong> Tüm alt domainleri kapsar (*.site.com).</li>
</ul>
<h2>Ücretsiz mi, Ücretli mi?</h2>
<p>Küçük/orta ölçekli siteler için <strong>Let's Encrypt</strong> yeterli. E-ticaret veya finans için OV/EV gerekli.</p>
<p>Ahost hosting paketlerinde SSL ücretsizdir. <a href="/site-araclari/ssl">SSL sertifikası kontrol aracımızı</a> deneyin.</p>
HTML,
    ],
    [
        'title'    => 'cPanel Nedir? En Sık Kullanılan Hosting Kontrol Paneli',
        'excerpt'  => 'cPanel arayüzü, temel işlevler, WHM farkı ve alternatifler (Plesk, DirectAdmin, HestiaCP).',
        'keywords' => 'cpanel nedir, whm, plesk vs cpanel, hosting paneli',
        'body'     => <<<HTML
<p>cPanel, hosting yönetiminde dünya standardı bir kontrol panelidir. Web dosyaları, e-posta, veritabanı, SSL, yedek gibi işlemleri arayüzden yapmanızı sağlar.</p>
<h2>cPanel'de Neler Var?</h2>
<ul>
<li><strong>File Manager</strong> — Dosya yönetimi</li>
<li><strong>MySQL Databases</strong> — DB oluşturma</li>
<li><strong>Email Accounts</strong> — E-posta hesapları</li>
<li><strong>SSL/TLS Status</strong> — Otomatik SSL (AutoSSL)</li>
<li><strong>Backups</strong> — Otomatik + manuel yedek</li>
<li><strong>Softaculous</strong> — WordPress/Joomla 1 tık kurulum</li>
</ul>
<h2>WHM ile Farkı</h2>
<p>cPanel — <strong>müşteri</strong> paneli (kendi sitesi). WHM — <strong>reseller/admin</strong> paneli (birden fazla cPanel yönetir).</p>
<h2>Alternatifler</h2>
<p>DirectAdmin (hafif, ekonomik), Plesk (Windows uyumlu), HestiaCP/CyberPanel (ücretsiz open-source).</p>
HTML,
    ],
    [
        'title'    => 'WordPress Hosting Seçerken Dikkat Edilmesi Gereken 10 Kritik Nokta',
        'excerpt'  => 'WordPress için doğru hosting nasıl seçilir? Hız, güvenlik, otomatik güncellemeler ve daha fazlası.',
        'keywords' => 'wordpress hosting, managed wordpress, wp hız',
        'body'     => <<<HTML
<p>WordPress dünyanın %43'ünü çalıştırıyor. Ama yanlış hosting = yavaş site + saldırıya açık.</p>
<h2>10 Kritik Kriter</h2>
<ol>
<li><strong>LiteSpeed veya NGINX</strong> — Apache'den 2-5x hızlı</li>
<li><strong>PHP 8.2+</strong> — Performans + güvenlik</li>
<li><strong>NVMe SSD</strong> — SATA'dan 10x hızlı I/O</li>
<li><strong>HTTP/3 desteği</strong> — Modern hız</li>
<li><strong>Ücretsiz SSL</strong> — Wildcard tercih edin</li>
<li><strong>Günlük otomatik yedek</strong> — En az 30 gün saklama</li>
<li><strong>Managed güncellemeler</strong> — WP core + plugin</li>
<li><strong>Malware tarama</strong> — Otomatik + manuel</li>
<li><strong>Staging alan</strong> — Test sitesi</li>
<li><strong>Türkiye lokasyonu</strong> — TR ziyaretçi için düşük ping</li>
</ol>
HTML,
    ],
    [
        'title'    => 'VPS Sunucu Nedir ve Ne Zaman Almalıyım?',
        'excerpt'  => 'Paylaşımlı hosting yerine VPS\'e ne zaman geçmelisiniz? Faydaları, dezavantajları ve karar kriterleri.',
        'keywords' => 'vps nedir, vps sunucu, kvm vs openvz',
        'body'     => <<<HTML
<p>VPS (Virtual Private Server), fiziksel bir sunucunun sanallaştırılmış bir bölümüdür. Size ayrılan CPU, RAM ve disk kaynaklarını başkasıyla paylaşmazsınız.</p>
<h2>Ne Zaman VPS?</h2>
<ul>
<li>Aylık trafik 50K+ ziyaretçi</li>
<li>Ağır uygulamalar (Laravel, Node.js, Django)</li>
<li>Özel yazılım yüklemek gerektiğinde</li>
<li>WooCommerce büyük katalog</li>
<li>Root erişimine ihtiyaç varsa</li>
</ul>
<h2>KVM vs OpenVZ</h2>
<p><strong>KVM</strong> — Tam sanallaştırma, tercih edilir. Kernel değiştirebilirsiniz.<br>
<strong>OpenVZ</strong> — Container tabanlı, daha ucuz ama sınırlı.</p>
<h2>Managed vs Unmanaged</h2>
<p>Linux tecrübeniz yoksa <strong>Managed VPS</strong> alın — provider sunucuyu sizin için yönetir.</p>
HTML,
    ],
    [
        'title'    => 'Site Hızlandırma: 15 Kanıtlanmış Yöntem (2026)',
        'excerpt'  => 'Sayfa yükleme hızını 3 saniye altına düşürecek 15 uygulanabilir taktik.',
        'keywords' => 'site hızlandırma, page speed, core web vitals',
        'body'     => <<<HTML
<p>Google Core Web Vitals'a göre 3 saniye üstü açılan siteler kullanıcıların %53'ünü kaybediyor. İşte hızlandırma rehberi:</p>
<h2>15 Yöntem</h2>
<ol>
<li>NVMe SSD hosting</li>
<li>LiteSpeed veya NGINX</li>
<li>CDN kullanın (Cloudflare ücretsiz)</li>
<li>Görsel optimizasyon (WebP, AVIF)</li>
<li>Lazy loading</li>
<li>CSS/JS minify + combine</li>
<li>GZIP / Brotli sıkıştırma</li>
<li>HTTP/3 aktif</li>
<li>Browser cache header'ları</li>
<li>Database indeksleme</li>
<li>Redis/Memcached cache</li>
<li>Ölü plugin/tema kaldır</li>
<li>Google Fonts self-host</li>
<li>Video embed → thumbnail + click-to-play</li>
<li>Preload/preconnect kritik kaynaklar</li>
</ol>
<p><a href="/site-araclari/speed">Ücretsiz hız testi aracımızı</a> deneyin.</p>
HTML,
    ],
    [
        'title'    => 'DNS Nedir? Nasıl Çalışır ve Nasıl Yönetilir?',
        'excerpt'  => 'A, AAAA, MX, CNAME, TXT kayıtları — ne anlama geliyor, nasıl ayarlanır?',
        'keywords' => 'dns nedir, dns kayıtları, nameserver',
        'body'     => <<<HTML
<p>DNS (Domain Name System), internetin "telefon rehberi"dir. ahost.web.tr yazdığınızda bunu 188.132.181.10 gibi IP'ye çevirir.</p>
<h2>Kayıt Türleri</h2>
<ul>
<li><strong>A</strong> — Domain'i IPv4 adresine bağlar</li>
<li><strong>AAAA</strong> — IPv6 adresine bağlar</li>
<li><strong>CNAME</strong> — Domain'i başka domaine yönlendirir</li>
<li><strong>MX</strong> — E-posta sunucusu</li>
<li><strong>TXT</strong> — SPF, DKIM, doğrulama</li>
<li><strong>NS</strong> — Domain'in nameserver'ları</li>
<li><strong>SRV</strong> — Servis kayıtları (VoIP vb.)</li>
</ul>
<h2>DNS Değişikliği Ne Zaman Yayılır?</h2>
<p>TTL değerine göre 5 dk - 48 saat arası. Genelde 4-6 saatte tamamlanır.</p>
<p><a href="/site-araclari/dns">DNS sorgu aracımızı</a> ile kayıtlarınızı kontrol edin.</p>
HTML,
    ],
    [
        'title'    => 'E-Ticaret Sitesi İçin Hosting Nasıl Seçilir?',
        'excerpt'  => 'WooCommerce, PrestaShop, Magento için doğru hosting kriterleri.',
        'keywords' => 'e-ticaret hosting, woocommerce hosting, prestashop',
        'body'     => <<<HTML
<p>E-ticarette 1 saniyelik yavaşlama = %7 dönüşüm kaybı. Hosting seçimi kritik.</p>
<h2>Zorunlu Özellikler</h2>
<ul>
<li>PCI-DSS uyumlu altyapı</li>
<li>Wildcard SSL (yıllık, ücretsiz)</li>
<li>Redis/Memcached — sepet + oturum</li>
<li>MySQL 8+ / MariaDB 10.6+ optimize</li>
<li>Otomatik günlük yedek + kolay geri yükleme</li>
<li>Staging alan (canlıya alım öncesi test)</li>
<li>7/24 canlı destek</li>
<li>DDoS koruması</li>
<li>CDN (görsel + statik dosya)</li>
</ul>
<h2>Trafik Bazlı Seçim</h2>
<table>
<tr><th>Aylık Ziyaretçi</th><th>Öneri</th></tr>
<tr><td>&lt; 10K</td><td>Business Hosting</td></tr>
<tr><td>10K - 100K</td><td>VPS M/L</td></tr>
<tr><td>&gt; 100K</td><td>Cloud + CDN</td></tr>
</table>
HTML,
    ],
    [
        'title'    => 'KVKK Uyumluluğu: Web Site Sahipleri İçin Rehber',
        'excerpt'  => '6698 sayılı KVKK\'ya uyumlu bir site nasıl olmalı? Aydınlatma metni, çerez politikası, VERBİS kaydı.',
        'keywords' => 'kvkk, gizlilik politikası, çerez politikası, verbis',
        'body'     => <<<HTML
<p>KVKK (Kişisel Verilerin Korunması Kanunu), Türkiye'de kişisel veri işleyen her işletmeyi bağlar. Web sitesi sahibi olarak sizin de sorumluluklarınız var.</p>
<h2>Zorunlu Belgeler</h2>
<ol>
<li><strong>Aydınlatma Metni</strong> — Form doldurulan her yerde link</li>
<li><strong>Gizlilik Politikası</strong> — Genel bilgilendirme</li>
<li><strong>Çerez Politikası</strong> — Cookie kullanımı</li>
<li><strong>Açık Rıza Beyanı</strong> — Pazarlama e-postaları için</li>
<li><strong>KVKK Başvuru Formu</strong> — Kullanıcılar verilerine erişebilsin</li>
</ol>
<h2>Teknik Gereklilikler</h2>
<ul>
<li>Şifreleme (SSL zorunlu)</li>
<li>Backup + felaket kurtarma</li>
<li>Erişim kontrolü (RBAC)</li>
<li>İzleme + log tutma</li>
<li>Silme talebine 30 gün içinde cevap</li>
</ul>
<h2>VERBİS Kaydı</h2>
<p>Yıllık cirosu 25M TL üstü veya 50+ çalışanı olan işletmeler VERBİS'e kayıt yaptırmak zorundadır.</p>
HTML,
    ],
    [
        'title'    => 'Site Yedekleme Stratejisi: 3-2-1 Kuralı',
        'excerpt'  => 'Sitenizin verilerini nasıl güvende tutarsınız? 3-2-1 backup kuralı ve pratik uygulama.',
        'keywords' => 'site yedekleme, backup stratejisi, disaster recovery',
        'body'     => <<<HTML
<p>Backup olmayan site = zamanı gelince kaybolan site. Sertifikalı IT rehberi 3-2-1 kuralını önerir.</p>
<h2>3-2-1 Kuralı</h2>
<ul>
<li><strong>3 kopya</strong> — Toplam 3 kopyanız olsun</li>
<li><strong>2 farklı ortamda</strong> — Farklı disk/lokasyon</li>
<li><strong>1 tanesi off-site</strong> — Fiziksel olarak ayrı yer (S3, farklı ülke)</li>
</ul>
<h2>Uygulama</h2>
<ol>
<li>Canlı site (1. kopya)</li>
<li>Sunucuda otomatik günlük yedek — /storage/backups (2. kopya, farklı disk)</li>
<li>AWS S3 / Backblaze B2'ye rsync (3. kopya, off-site)</li>
</ol>
<h2>Test Edin!</h2>
<p>Ayda 1 kez restore testi yapın. Kullanılmayan backup = değersizdir.</p>
<p>Ahost'ta <strong>Admin > Backup</strong> ekranından günlük yedek + S3 sync bir tıkla kurulur.</p>
HTML,
    ],
    [
        'title'    => 'PHP 8.3 vs PHP 7.4: Neden Yükseltmelisiniz?',
        'excerpt'  => 'PHP 8.3\'ün getirdiği performans, güvenlik ve yenilikler. 7.4\'ten geçişte dikkat edilmesi gerekenler.',
        'keywords' => 'php 8.3, php güncelleme, php performans',
        'body'     => <<<HTML
<p>PHP 7.4 desteği Kasım 2022'de bitti. Halen kullanıyorsanız güvenlik açıklarına karşı korumasızsınız.</p>
<h2>Neden 8.3?</h2>
<ul>
<li><strong>%20-40 daha hızlı</strong> — JIT compiler + optimize edilmiş opcache</li>
<li><strong>Named arguments</strong> — Kod okunabilirliği</li>
<li><strong>Enums</strong> — Sabit değerler için tip güvenliği</li>
<li><strong>Readonly properties</strong> — Immutable data</li>
<li><strong>Match expression</strong> — Switch'in modern hali</li>
<li><strong>Fibers</strong> — Async programming</li>
</ul>
<h2>Migration Öncesi Kontrol</h2>
<ol>
<li>PHP CodeSniffer ile PHPCompatibility tarama</li>
<li>Composer paketlerin güncel olduğunu doğrula</li>
<li>Staging'de test</li>
<li>PHP-CGI değil, PHP-FPM kullan</li>
</ol>
<p>Ahost hosting paketlerinde PHP 8.3 varsayılan gelir, cPanel'den 1 tıkla eski sürümlere de dönebilirsiniz.</p>
HTML,
    ],
    [
        'title'    => 'DDoS Saldırısı Nedir ve Nasıl Korunulur?',
        'excerpt'  => 'DDoS türleri, tespit, önleme ve saldırı anında yapılması gerekenler.',
        'keywords' => 'ddos, ddos koruması, cloudflare',
        'body'     => <<<HTML
<p>DDoS (Distributed Denial of Service), binlerce bilgisayardan sitenize aynı anda istek göndererek sunucuyu çökertme saldırısıdır.</p>
<h2>Türleri</h2>
<ul>
<li><strong>Volumetric</strong> — Bant genişliği doldurma (UDP flood, ICMP flood)</li>
<li><strong>Protocol</strong> — SYN flood, Ping of Death</li>
<li><strong>Application (L7)</strong> — HTTP flood, Slowloris</li>
</ul>
<h2>Koruma Katmanları</h2>
<ol>
<li><strong>Cloudflare</strong> — Ücretsiz L3-L7 koruma, önerilir</li>
<li><strong>Rate limiting</strong> — IP başına dakikada N istek</li>
<li><strong>Fail2ban</strong> — Şüpheli IP'leri ban</li>
<li><strong>WAF</strong> — Web Application Firewall</li>
<li><strong>Anycast DNS</strong> — Saldırıyı dağıtma</li>
</ol>
<h2>Saldırı Anında</h2>
<p>1) Hosting sağlayıcınıza bildirin. 2) Cloudflare "Under Attack Mode"u aktifleştirin. 3) Log analiz edip saldırı IP aralığını bloklayın.</p>
HTML,
    ],
    [
        'title'    => 'Sunucu Taşıma: Sitenizi Kaybetmeden Nasıl Transfer Edersiniz?',
        'excerpt'  => 'Eski hosting\'den yeniye taşıma adım adım. Sıfır downtime ile.',
        'keywords' => 'site taşıma, hosting transfer, migration',
        'body'     => <<<HTML
<p>Hosting değiştirmek göründüğü kadar zor değil. 5 adımda sıfır downtime ile taşıma:</p>
<h2>1. Yeni Hosting'de Hazırlık</h2>
<p>Yeni hostingi al, cPanel'de site için hesap aç, DB oluştur.</p>
<h2>2. Backup Al</h2>
<p>Eski hostingden tam yedek (dosya + DB): cPanel > Backup Wizard > Full Backup.</p>
<h2>3. Yeni Hosting'e Yükle</h2>
<p>cPanel > File Manager > public_html/ altına dosyaları çıkart. DB'yi phpMyAdmin'den import et.</p>
<h2>4. hosts Dosyası ile Test</h2>
<p>Windows: `C:\Windows\System32\drivers\etc\hosts`<br>
Mac/Linux: `/etc/hosts`<br>
`YENI_IP  ornek.com` satırı ekle. Kendi bilgisayarında yeni sunucuyu test et.</p>
<h2>5. DNS Değiştir</h2>
<p>Yeni nameserver'ları registrar panelinden gir. 4-24 saat içinde ziyaretçiler yeni sunucuya düşer.</p>
<h2>6. Eski Hosting'i 30 Gün Bekle</h2>
<p>DNS TTL geçene kadar iki yerde de site çalışır. Sorun yoksa 30 gün sonra eski hosting'i iptal et.</p>
<p>Ahost'ta <strong>ücretsiz site taşıma</strong> hizmetimiz vardır — destek talebi açın.</p>
HTML,
    ],
    [
        'title'    => 'SEO İçin Teknik Kontrol Listesi (30 Madde)',
        'excerpt'  => 'Sitenizi Google\'da yükseltecek teknik SEO kontrolleri. Doğrudan uygulanabilir.',
        'keywords' => 'teknik seo, seo kontrol, google seo',
        'body'     => <<<HTML
<h2>Temel</h2>
<ol>
<li>HTTPS aktif</li>
<li>www / non-www yönlendirmesi</li>
<li>robots.txt doğru</li>
<li>sitemap.xml gönderilmiş (Search Console)</li>
<li>Canonical URL'ler doğru</li>
<li>404 sayfası özel + navigasyon içerir</li>
<li>301 yönlendirmeler eski URL'ler için</li>
</ol>
<h2>Hız</h2>
<ol start="8">
<li>PageSpeed 80+ (mobil)</li>
<li>Core Web Vitals yeşil</li>
<li>Görseller WebP + lazy load</li>
<li>Font-display: swap</li>
<li>GZIP/Brotli aktif</li>
<li>HTTP/3 aktif</li>
</ol>
<h2>İçerik</h2>
<ol start="14">
<li>Her sayfada tek H1</li>
<li>Meta title 50-60 karakter</li>
<li>Meta description 140-160 karakter</li>
<li>Alt text tüm görsellerde</li>
<li>Structured data (schema.org)</li>
<li>İç bağlantılar</li>
<li>OG tags (Facebook, Twitter)</li>
</ol>
<h2>Mobil</h2>
<ol start="21">
<li>Responsive tasarım</li>
<li>Viewport meta tag</li>
<li>Touch target 48x48px+</li>
<li>Yatay scroll yok</li>
</ol>
<h2>İleri</h2>
<ol start="25">
<li>hreflang (çok dilli site)</li>
<li>AMP (opsiyonel)</li>
<li>PWA manifest.json</li>
<li>Breadcrumb schema</li>
<li>FAQ schema</li>
<li>Log 4xx/5xx hataları</li>
</ol>
<p><a href="/site-araclari/seo">Ücretsiz SEO analiz aracımızla</a> sitenizi test edin.</p>
HTML,
    ],
    [
        'title'    => 'Reseller Hosting Nedir? Kendi Hosting Firmanı Nasıl Kurarsın?',
        'excerpt'  => 'Reseller hosting mantığı, iş modeli, kar marjları ve başlangıç için gerekenler.',
        'keywords' => 'reseller hosting, hosting satıcısı, whm',
        'body'     => <<<HTML
<p>Reseller hosting, bir hosting sağlayıcının ana sunucusunu bölüp <strong>kendi markanız altında</strong> satmanızı sağlar.</p>
<h2>İş Modeli</h2>
<ol>
<li>Reseller paket alırsın (aylık $30-100)</li>
<li>WHM ile bölüp müşterilere cPanel hesapları açarsın</li>
<li>Kendi fiyatını belirlersin ($5-15 arası)</li>
<li>Marka + destek senden çıkar (white-label)</li>
</ol>
<h2>Kar Örneği</h2>
<p>50 GB reseller = $50. 50 müşteriye 1 GB'lık paket = $10 × 50 = $500. Aylık $450 kar.</p>
<h2>Başlangıç İçin Gerekenler</h2>
<ul>
<li>WHM/cPanel bilgisi</li>
<li>Kendi domain'in</li>
<li>Ödeme altyapısı (PayTR/iyzico)</li>
<li>Fatura + KDV yönetimi</li>
<li>7/24 canlı destek (chat + ticket)</li>
</ul>
<h2>Ahost Reseller</h2>
<p>Yakında reseller paketimiz açılıyor — kendi hosting firmanızı 24 saatte kurun.</p>
HTML,
    ],
    [
        'title'    => 'CDN Nedir ve Neden Kullanmalısınız?',
        'excerpt'  => 'Content Delivery Network mantığı, popüler sağlayıcılar (Cloudflare, BunnyCDN) ve nasıl kurulur.',
        'keywords' => 'cdn nedir, cloudflare, bunnycdn',
        'body'     => <<<HTML
<p>CDN (Content Delivery Network), sitenizin statik dosyalarını (görsel, CSS, JS) dünya genelinde dağıtık sunuculardan sunar. Ziyaretçi Almanya'daysa Frankfurt'tan, Türkiye'deyse İstanbul'dan çeker.</p>
<h2>Faydaları</h2>
<ul>
<li><strong>Hız:</strong> Ziyaretçiye en yakın sunucudan servis</li>
<li><strong>Bant genişliği tasarrufu:</strong> Origin sunucunuz nefes alır</li>
<li><strong>DDoS koruması:</strong> Saldırı dağılır</li>
<li><strong>SSL:</strong> Genelde ücretsiz</li>
<li><strong>Analytics:</strong> Trafik + saldırı raporu</li>
</ul>
<h2>Popüler CDN'ler</h2>
<table>
<tr><th>CDN</th><th>Ücret</th><th>Özellik</th></tr>
<tr><td>Cloudflare</td><td>Ücretsiz</td><td>WAF, SSL, DDoS</td></tr>
<tr><td>BunnyCDN</td><td>$0.01/GB</td><td>Ucuz, hızlı</td></tr>
<tr><td>Fastly</td><td>$50+/ay</td><td>Enterprise</td></tr>
<tr><td>AWS CloudFront</td><td>Pay-as-you-go</td><td>AWS ecosystem</td></tr>
</table>
<h2>Kurulum</h2>
<ol>
<li>Cloudflare hesabı aç</li>
<li>Domain ekle</li>
<li>Nameserver'ları Cloudflare'e yönlendir</li>
<li>SSL/TLS > Full (strict) seç</li>
<li>Caching > Standard</li>
</ol>
HTML,
    ],
    [
        'title'    => 'E-posta Sunucusu Nedir? SMTP, IMAP, POP3 Farkları',
        'excerpt'  => 'Kurumsal e-posta hesabı nasıl oluşturulur, SPF/DKIM/DMARC nasıl kurulur?',
        'keywords' => 'kurumsal e-posta, smtp nedir, spf dkim',
        'body'     => <<<HTML
<p>info@sirket.com gibi kurumsal e-posta, güvenilirlik ve profesyonellik için şarttır.</p>
<h2>Protokoller</h2>
<ul>
<li><strong>SMTP (Port 587)</strong> — E-posta gönderme</li>
<li><strong>IMAP (Port 993)</strong> — Sunucuda tutup okuma (birden fazla cihaz)</li>
<li><strong>POP3 (Port 995)</strong> — İndirip sunucudan silme (tek cihaz)</li>
</ul>
<h2>Kurulum Adımları</h2>
<ol>
<li>Hosting'de cPanel > Email Accounts > Create</li>
<li>Outlook/Gmail'e ekle: IMAP 993 (SSL), SMTP 587 (STARTTLS)</li>
<li>DNS'e SPF/DKIM/DMARC ekle (aşağıda)</li>
</ol>
<h2>Spam'e Düşmemek İçin</h2>
<ul>
<li><strong>SPF:</strong> "v=spf1 include:_spf.hosting.com ~all"</li>
<li><strong>DKIM:</strong> cPanel > Email Deliverability > Auto-generate</li>
<li><strong>DMARC:</strong> "v=DMARC1; p=quarantine; rua=mailto:dmarc@sirket.com"</li>
<li><strong>rDNS (PTR):</strong> Sunucu IP'nizin ters DNS'i domain'inize eşleşsin</li>
</ul>
<h2>Alternatifler</h2>
<p>Google Workspace ($6/kullanıcı/ay) veya Microsoft 365 ($6+) — kurumsal seviye, kolay yönetim.</p>
HTML,
    ],
    [
        'title'    => 'WordPress Güvenliği: 20 Önlem',
        'excerpt'  => 'WordPress sitenizi hackerlardan koruyacak 20 pratik önlem.',
        'keywords' => 'wordpress güvenliği, wp güvenlik, wp hardening',
        'body'     => <<<HTML
<p>WordPress dünyadaki en popüler CMS — dolayısıyla en çok saldırılan. İşte kaleyi güçlendirmenin 20 yolu:</p>
<h2>Temel</h2>
<ol>
<li>WordPress + tema + plugin'leri güncel tut</li>
<li>Yönetici kullanıcı adı "admin" olmasın</li>
<li>Güçlü şifre + 2FA</li>
<li>Kullanılmayan tema/plugin'i sil (deaktif değil)</li>
<li>SSL zorunlu (HTTPS)</li>
</ol>
<h2>Login</h2>
<ol start="6">
<li>wp-login.php'yi gizle (Rename WP-Login plugin)</li>
<li>Login rate limit (Limit Login Attempts)</li>
<li>IP whitelist ile admin erişim</li>
<li>reCAPTCHA aktif</li>
</ol>
<h2>Dosya Sistemi</h2>
<ol start="10">
<li>wp-config.php izinleri 400</li>
<li>PHP çalıştırılamaz upload klasörü (.htaccess)</li>
<li>XML-RPC devre dışı (kullanmıyorsanız)</li>
<li>File editor kapat: `define('DISALLOW_FILE_EDIT', true);`</li>
<li>DB prefix "wp_" değil (wpx_ vb.)</li>
</ol>
<h2>İleri</h2>
<ol start="15">
<li>Wordfence veya Sucuri firewall</li>
<li>Web Application Firewall (Cloudflare)</li>
<li>Günlük malware taraması</li>
<li>Otomatik günlük yedek + off-site</li>
<li>Sadece güvenilir kaynaklardan plugin</li>
<li>Log tut (Wordfence, iThemes Security)</li>
</ol>
HTML,
    ],
    [
        'title'    => 'Domain Değerleme: Bir Domain\'in Değeri Nasıl Belirlenir?',
        'excerpt'  => 'Domain\'inizin gerçek değeri kaç? Uzunluk, TLD, marka gücü ve SEO sinyalleriyle hesaplama.',
        'keywords' => 'domain değerleme, domain değeri, domain satış',
        'body'     => <<<HTML
<p>Bir domain'in değeri; kısalık, TLD, marka gücü, SEO sinyalleri ve alıcı ilgisine bağlıdır. En büyük satışlar:</p>
<ul>
<li>cars.com — $872M</li>
<li>voice.com — $30M</li>
<li>360.com — $17M</li>
</ul>
<h2>Değer Kriterleri</h2>
<ol>
<li><strong>Uzunluk:</strong> Kısa = değerli. 4 karakter altı premium.</li>
<li><strong>TLD:</strong> .com > .net > .org > .co > .io</li>
<li><strong>Kelime:</strong> Sözlük kelimesi > uydurma</li>
<li><strong>Marka gücü:</strong> Telaffuzu kolay, akılda kalıcı</li>
<li><strong>Yaş:</strong> Eski domain = SEO avantajı</li>
<li><strong>Backlink profili:</strong> Yüksek DA/PA</li>
<li><strong>Piyasa talebi:</strong> Sektörün büyüme trendi</li>
</ol>
<h2>Değerleme Araçları</h2>
<ul>
<li><a href="/site-araclari/domain-degerleme">Ahost ücretsiz domain değerleme</a></li>
<li>GoDaddy Appraisal</li>
<li>EstiBot</li>
<li>Namebio (satış geçmişi)</li>
</ul>
<h2>Nerede Satılır?</h2>
<p>Sedo, Afternic, GoDaddy Auctions, Dan.com — komisyon %10-20 arası.</p>
HTML,
    ],
];

$sort = 0;
$now = date('Y-m-d H:i:s');
$inserted = 0;

foreach ($posts as $p) {
    $slug = Slug::make($p['title']);

    // Duplicate kontrol
    $existing = Connection::selectOne("SELECT id FROM blog_posts WHERE slug = ?", [$slug]);
    if ($existing) {
        continue;
    }

    try {
        Connection::insert('blog_posts', [
            'title'           => $p['title'],
            'slug'            => $slug,
            'excerpt'         => $p['excerpt'],
            'body_html'       => $p['body'],
            'category'        => 'hosting-domain',
            'tags'            => $p['keywords'],
            'seo_title'       => $p['title'] . ' — Ahost Bilişim',
            'seo_description' => $p['excerpt'],
            'status'          => 'published',
            'published_at'    => date('Y-m-d H:i:s', time() - ($sort * 86400 * 3)),
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);
        $inserted++;
        $sort++;
    } catch (\Throwable $e) {
        echo "  ✗ '{$p['title']}': " . $e->getMessage() . "\n";
    }
}

echo "  ✓ $inserted SEO blog yazısı eklendi.\n";
