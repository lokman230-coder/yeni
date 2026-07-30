/**
 * Ahost Builder 4 Core
 * Shared block registry for Builder Pro, Site Builder pages, inline editing and future AI actions.
 * It does not own rendering by itself; existing editors can consume this registry safely.
 */
(function (window) {
  'use strict';

  const groups = [
    { key: 'grid', title: 'AhostBuilder Grid', items: ['section', 'container', 'columns_2', 'columns_3'] },
    { key: 'popular', title: 'En Çok Kullanılan', items: ['hero', 'slider', 'domain', 'product', 'pricing', 'support_widget'] },
    { key: 'commerce', title: 'Satış ve Ürün', items: ['hosting', 'vps', 'ssl', 'marketplace', 'cart_flow', 'addon'] },
    { key: 'content', title: 'İçerik', items: ['text', 'button', 'form', 'media', 'banner', 'faq', 'testimonial', 'blog'] },
    { key: 'panel', title: 'Panel / Operasyon', items: ['kpi', 'chart', 'renewal', 'invoice', 'ticket', 'tabs', 'ai_builder'] },
    { key: 'global', title: 'Tema Global', items: ['header', 'footer', 'seo', 'language_currency', 'mobile_nav'] }
  ];

  const blocks = {
    section: { label: 'Bölüm', hint: 'Arka plan, padding, renk ve görsel alanı', icon: '▦', defaults: { title: 'Yeni Bölüm', text: 'Bu bölüm içine bloklar yerleştirilebilir.', props: { padding: '28px', radius: '22px' } } },
    container: { label: 'Container', hint: 'İçerik genişliği ve hizalama', icon: '□', defaults: { title: 'Container', text: 'İçeriği kontrollü genişlikte tutar.', props: { maxWidth: '1200px', padding: '24px' } } },
    columns_2: { label: '2 Kolon', hint: 'Yan yana iki kolon düzeni', icon: '▥', defaults: { title: '2 Kolon Düzen', text: 'Yan yana iki alan oluşturur.' } },
    columns_3: { label: '3 Kolon', hint: 'Yan yana üç kolon düzeni', icon: '▥', defaults: { title: '3 Kolon Düzen', text: 'Yan yana üç alan oluşturur.' } },
    hero: { label: 'Hero', hint: 'Başlık, açıklama, görsel, CTA ve arka plan', icon: '✦', defaults: { title: 'Premium Hero', text: 'Domain, hosting, marketplace ve destek tek panelde.', button: 'Hemen Başla', props: { titleSize: '34px', bodySize: '15px' } } },
    slider: { label: 'Slider', hint: 'Arka plan görseli, yazı ve butonlu slider', icon: '▣', defaults: { title: 'Slider', text: 'Menü altında görünen slider alanı.', button: 'Slider Aktif' } },
    domain: { label: 'Domain Sorgu', hint: 'Domain sorgu, WHOIS, DNS, SSL ve değerleme', icon: '◎', defaults: { title: 'Domain Search Center', text: 'Alan adınızı sorgulayın, fiyat ve uygunluk görün.', button: 'Sorgula' } },
    product: { label: 'Ürün / Paket', hint: 'Hosting, lisans, tasarım veya hizmet kartı', icon: '▤', defaults: { title: 'Ürün / Paket', text: 'Hosting, VPS, web tasarım veya lisans ürünü.', price: '₺149/ay', button: 'Satın Al' } },
    pricing: { label: 'Fiyat Tablosu', hint: 'Plan karşılaştırma ve satın alma', icon: '₺', defaults: { title: 'Pro Paket', text: 'Kurumsal kullanım için optimize paket.', price: '₺399/ay', button: 'Satın Al' } },
    support_widget: { label: 'Destek Widget', hint: 'Sağ ikonlar, WhatsApp, ticket, telefon, canlı destek', icon: '●', defaults: { title: 'Sağ Alt Destek', text: 'WhatsApp, canlı destek, AI ve ticket butonları.', button: 'Destek Aç' } },
    hosting: { label: 'Hosting', hint: 'Hosting paket kartı ve kaynak bilgileri', icon: '▣', defaults: { title: 'Hosting Paketleri', text: 'Paylaşımlı, bayi ve WordPress hosting paketleri.', price: 'TRY 149/ay', button: 'İncele' } },
    vps: { label: 'VPS', hint: 'VPS/cloud paket kartı', icon: '▧', defaults: { title: 'VPS Cloud', text: 'NVMe disk, yedekleme ve ölçeklenebilir kaynak.', price: 'TRY 399/ay', button: 'Kur' } },
    ssl: { label: 'SSL', hint: 'SSL ürün kartı ve sertifika bilgisi', icon: '◈', defaults: { title: 'SSL Sertifikaları', text: 'DV/OV/EV sertifika ve otomatik kurulum.', price: 'TRY 99/yıl', button: 'Seç' } },
    marketplace: { label: 'Marketplace', hint: 'İlan, domain satışı ve tema/eklenti kartları', icon: '◇', defaults: { title: 'Marketplace', text: 'Domain, tema, eklenti ve dijital ürünleri yönetin.', button: 'Keşfet' } },
    cart_flow: { label: 'Sepet Akışı', hint: 'Domain, hosting, eklenti ve ödeme adımları', icon: '🛒', defaults: { title: 'Sipariş Akışı', text: 'Domain, hosting, eklenti ve ödeme adımlarını düzenleyin.', button: 'Devam Et' } },
    addon: { label: 'Eklenti', hint: 'SSL, ek disk, trafik, yedekleme gibi opsiyonlar', icon: '+', defaults: { title: 'Ek Paket', text: 'SSL, ek disk, trafik veya yedekleme ekleyin.', price: '₺49/ay', button: 'Ekle' } },
    text: { label: 'Metin', hint: 'Serbest içerik ve tanıtım alanı', icon: 'T', defaults: { title: 'Metin Bloğu', text: 'Bu alanı doğrudan düzenleyebilirsiniz.' } },
    button: { label: 'Buton', hint: 'Link, popup veya dropdown aksiyonu', icon: '↗', defaults: { title: 'Buton', button: 'Buton Metni', props: { actionType: 'link', actionUrl: '#' } } },
    form: { label: 'Form', hint: 'Teklif, iletişim ve başvuru formu', icon: '▤', defaults: { title: 'Form', text: 'Teklif, destek veya iletişim formu.', button: 'Gönder' } },
    media: { label: 'Medya', hint: 'Görsel, video, galeri veya kapak alanı', icon: '▧', defaults: { title: 'Medya Alanı', text: 'Görsel, video veya galeri ekleyin.' } },
    banner: { label: 'Banner', hint: 'Duyuru veya kampanya bandı', icon: '▰', defaults: { title: 'Duyuru Banner', text: 'Bakım, indirim veya kampanya duyurusu.', button: 'Detay' } },
    faq: { label: 'SSS', hint: 'Soru-cevap akordeon alanı', icon: '?', defaults: { title: 'Sık Sorulan Sorular', text: 'Soru ve cevaplarınızı ekleyin.' } },
    testimonial: { label: 'Referans / Yorum', hint: 'Müşteri yorumu veya referans kartı', icon: '★', defaults: { title: 'Müşteri Yorumu', text: 'Ahost One operasyonu tek panele topladı.' } },
    blog: { label: 'Blog / Bilgi', hint: 'Blog, duyuru ve bilgi bankası kartları', icon: '▤', defaults: { title: 'Blog ve Duyurular', text: 'SEO uyumlu içerik kartları.', button: 'Oku' } },
    kpi: { label: 'KPI', hint: 'Admin veya müşteri paneli istatistik kartı', icon: '#', defaults: { title: 'Dashboard KPI', text: 'Müşteri, domain, hizmet veya gelir kartı.', price: '128' } },
    chart: { label: 'Grafik', hint: 'Gelir, sipariş, kaynak veya SLA grafiği', icon: '⌁', defaults: { title: 'Grafik', text: 'Rapor ve istatistik grafiği.' } },
    renewal: { label: 'Yenileme', hint: 'Yenileme tarihi ve kalan süre kartı', icon: '↻', defaults: { title: 'Yenileme Kartı', text: 'Hosting/domain ödeme tarihi ve kalan gün.', button: 'Yenile' } },
    invoice: { label: 'Fatura', hint: 'Fatura ve ödeme durumu kartı', icon: '▤', defaults: { title: 'Fatura Kartı', text: 'Son faturalar ve ödeme durumu.', button: 'Detay' } },
    ticket: { label: 'Ticket', hint: 'Destek talebi ve SLA kartı', icon: '◌', defaults: { title: 'Ticket Kartı', text: 'Açık destek kayıtları ve SLA.', button: 'Ticket Aç' } },
    tabs: { label: 'Sekmeler', hint: 'Panel veya sayfa sekmeleri', icon: '☰', defaults: { title: 'Sekmeli Panel', text: 'Özet, Hizmetler, Domainler, Faturalar, Destek' } },
    ai_builder: { label: 'AI Builder', hint: 'AI tasarım ve içerik öneri paneli', icon: 'AI', defaults: { title: 'AI Builder', text: 'Prompt ile sayfa, başlık, kampanya ve SEO metni önerileri.', button: 'AI Öner' } },
    header: { label: 'Header', hint: 'Logo, topbar, menü ve aksiyonlar', icon: '▔', defaults: { title: 'Header', text: 'Logo, menü ve topbar düzeni.', button: 'Müşteri Paneli' } },
    footer: { label: 'Footer', hint: 'Footer menüleri, yasal metinler ve sosyal ağlar', icon: '▁', defaults: { title: 'Footer', text: 'Menüler, SEO metinleri, sosyal bağlantılar ve destek kanalları.' } },
    seo: { label: 'SEO / Meta', hint: 'Başlık, açıklama, canonical ve sosyal meta', icon: 'SEO', defaults: { title: 'SEO / Meta', text: 'Sayfa başlığı, meta açıklaması ve sosyal paylaşım metinleri.' } },
    language_currency: { label: 'Dil / Para', hint: 'Refreshsiz dil ve para birimi seçimi', icon: 'TR', defaults: { title: 'Dil ve Para Birimi', text: 'Bayrak, para simgesi ve dropdown davranışı.' } },
    mobile_nav: { label: 'Mobil Menü', hint: 'Alt menü, kategori ve destek ikonları', icon: '☰', defaults: { title: 'Mobil Menü', text: 'Kategori, domain, sepet ve panel kısa yolları.' } }
  };

  function clone(value) {
    return JSON.parse(JSON.stringify(value || {}));
  }

  function uid(prefix) {
    return (prefix || 'ab4') + '_' + Math.random().toString(36).slice(2, 10);
  }

  function get(type) {
    return blocks[type] || blocks.text;
  }

  function createWidget(type, extra) {
    const def = get(type);
    const base = clone(def.defaults);
    return Object.assign({ id: uid('ab4w'), type, title: '', text: '', button: '', price: '', props: {} }, base, extra || {}, {
      props: Object.assign({}, base.props || {}, (extra && extra.props) || {})
    });
  }

  function libraryHtml() {
    return groups.map(group => {
      const items = group.items.map(type => {
        const block = get(type);
        return '<button type="button" class="ab4-library-item" data-ab4-block="' + type + '"><span>' + block.icon + '</span><b>' + block.label + '</b><small>' + block.hint + '</small></button>';
      }).join('');
      return '<section class="ab4-library-group" data-ab4-group="' + group.key + '"><h4>' + group.title + '</h4><div>' + items + '</div></section>';
    }).join('');
  }

  window.AhostBuilder4 = {
    version: '4.0.0',
    groups,
    blocks,
    get,
    createWidget,
    libraryHtml,
    uid
  };
})(window);
