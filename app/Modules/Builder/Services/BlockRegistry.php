<?php

declare(strict_types=1);

namespace App\Modules\Builder\Services;

/**
 * Blok kütüphanesi — Site + Mobile Builder için tüm blok tipleri.
 *
 * Her blok:
 *   - type: kimlik (hero, pricing, radio_player, ...)
 *   - category: menu (structural, content, marketing, data, advanced, radio, ecommerce)
 *   - kind_scope: hangi builder tipinde görünür (site, mobile, both)
 *   - sector_scope: hangi sektörlerde görünür (null = hepsi)
 *   - schema: prop tanımları (adı, tipi, varsayılanı, seçenekler)
 *
 * Şartname madde 23: "Hosting seçiliyken radyo alanları görünmeyecek."
 * kind_scope + sector_scope bunu sağlar.
 */
final class BlockRegistry
{
    /** @return array<string, array> */
    public static function all(): array
    {
        return [
            // ====== STRUCTURAL ======
            'section'   => ['category' => 'structural', 'label' => 'Bölüm', 'icon' => '▭', 'kind_scope' => 'both', 'schema' => ['bg' => 'string', 'padding' => 'select']],
            'row'       => ['category' => 'structural', 'label' => 'Satır (12-col)', 'icon' => '━', 'kind_scope' => 'site'],
            'column'    => ['category' => 'structural', 'label' => 'Kolon', 'icon' => '┃', 'kind_scope' => 'site'],
            'container' => ['category' => 'structural', 'label' => 'Container', 'icon' => '☰', 'kind_scope' => 'both'],

            // ====== CONTENT ======
            'hero'      => ['category' => 'content', 'label' => 'Kahraman Alanı', 'icon' => '🌟', 'kind_scope' => 'both', 'schema' => ['title' => 'string', 'subtitle' => 'string', 'cta_text' => 'string', 'cta_link' => 'string', 'image' => 'image']],
            'text'      => ['category' => 'content', 'label' => 'Metin', 'icon' => '📝', 'kind_scope' => 'both'],
            'heading'   => ['category' => 'content', 'label' => 'Başlık', 'icon' => 'H', 'kind_scope' => 'both'],
            'image'     => ['category' => 'content', 'label' => 'Görsel', 'icon' => '🖼️', 'kind_scope' => 'both'],
            'video'     => ['category' => 'content', 'label' => 'Video', 'icon' => '🎬', 'kind_scope' => 'both'],
            'button'    => ['category' => 'content', 'label' => 'Buton', 'icon' => '⏺', 'kind_scope' => 'both'],
            'gallery'   => ['category' => 'content', 'label' => 'Galeri', 'icon' => '🖼️', 'kind_scope' => 'both'],
            'divider'   => ['category' => 'content', 'label' => 'Ayraç', 'icon' => '—', 'kind_scope' => 'both'],
            'spacer'    => ['category' => 'content', 'label' => 'Boşluk', 'icon' => '↕', 'kind_scope' => 'both'],
            'icon'      => ['category' => 'content', 'label' => 'İkon', 'icon' => '✨', 'kind_scope' => 'both'],

            // ====== MARKETING ======
            'features'      => ['category' => 'marketing', 'label' => 'Özellik Kartları', 'icon' => '⭐', 'kind_scope' => 'both'],
            'pricing'       => ['category' => 'marketing', 'label' => 'Fiyat Tablosu', 'icon' => '💰', 'kind_scope' => 'both'],
            'testimonials'  => ['category' => 'marketing', 'label' => 'Referanslar', 'icon' => '💬', 'kind_scope' => 'both'],
            'faq'           => ['category' => 'marketing', 'label' => 'SSS', 'icon' => '❓', 'kind_scope' => 'both'],
            'cta'           => ['category' => 'marketing', 'label' => 'CTA Kutusu', 'icon' => '📣', 'kind_scope' => 'both'],
            'countdown'     => ['category' => 'marketing', 'label' => 'Geri Sayım', 'icon' => '⏳', 'kind_scope' => 'both'],
            'stats'         => ['category' => 'marketing', 'label' => 'İstatistikler', 'icon' => '📊', 'kind_scope' => 'both'],
            'team'          => ['category' => 'marketing', 'label' => 'Ekibimiz', 'icon' => '👥', 'kind_scope' => 'both'],
            'logos'         => ['category' => 'marketing', 'label' => 'Marka Logoları', 'icon' => '🏷️', 'kind_scope' => 'site'],

            // ====== DATA (dinamik içerik) ======
            'product_grid'  => ['category' => 'data', 'label' => 'Ürün Grid', 'icon' => '📦', 'kind_scope' => 'both'],
            'blog_grid'     => ['category' => 'data', 'label' => 'Blog Yazıları', 'icon' => '📰', 'kind_scope' => 'both'],
            'services'      => ['category' => 'data', 'label' => 'Hizmet Kartları', 'icon' => '🛠️', 'kind_scope' => 'both'],
            'categories'    => ['category' => 'data', 'label' => 'Kategoriler', 'icon' => '📁', 'kind_scope' => 'both'],

            // ====== FORM / ETKİLEŞİM ======
            'contact'          => ['category' => 'form', 'label' => 'İletişim Formu', 'icon' => '📮', 'kind_scope' => 'both'],
            'newsletter'       => ['category' => 'form', 'label' => 'Bülten Kaydı', 'icon' => '✉️', 'kind_scope' => 'both'],
            'reservation_form' => ['category' => 'form', 'label' => 'Rezervasyon', 'icon' => '📅', 'kind_scope' => 'both', 'sector_scope' => ['restaurant','clinic','gym','booking']],
            'appointment_form' => ['category' => 'form', 'label' => 'Randevu Formu', 'icon' => '📅', 'kind_scope' => 'both', 'sector_scope' => ['clinic','booking']],
            'search'           => ['category' => 'form', 'label' => 'Arama Kutusu', 'icon' => '🔍', 'kind_scope' => 'both'],
            'song_request'     => ['category' => 'form', 'label' => 'Şarkı İsteği', 'icon' => '🎵', 'kind_scope' => 'both', 'sector_scope' => ['radio']],

            // ====== SEKTÖR-ÖZEL: RADYO ======
            'radio_player' => ['category' => 'radio', 'label' => 'Radyo Player', 'icon' => '🎧', 'kind_scope' => 'both', 'sector_scope' => ['radio']],
            'dj_schedule'  => ['category' => 'radio', 'label' => 'DJ Programı', 'icon' => '🎙️', 'kind_scope' => 'both', 'sector_scope' => ['radio']],
            'now_playing'  => ['category' => 'radio', 'label' => 'Şu An Çalan', 'icon' => '▶️', 'kind_scope' => 'mobile', 'sector_scope' => ['radio']],
            'podcast_list' => ['category' => 'radio', 'label' => 'Podcast Listesi', 'icon' => '🎙', 'kind_scope' => 'both', 'sector_scope' => ['radio']],

            // ====== SEKTÖR-ÖZEL: RESTORAN ======
            'menu'             => ['category' => 'restaurant', 'label' => 'Menü', 'icon' => '📋', 'kind_scope' => 'both', 'sector_scope' => ['restaurant']],
            'menu_categories'  => ['category' => 'restaurant', 'label' => 'Menü Kategorileri', 'icon' => '🍴', 'kind_scope' => 'mobile', 'sector_scope' => ['restaurant']],

            // ====== SEKTÖR-ÖZEL: E-TİCARET ======
            'cart'            => ['category' => 'ecommerce', 'label' => 'Sepet', 'icon' => '🛒', 'kind_scope' => 'both', 'sector_scope' => ['ecommerce']],
            'checkout'        => ['category' => 'ecommerce', 'label' => 'Ödeme', 'icon' => '💳', 'kind_scope' => 'both', 'sector_scope' => ['ecommerce']],
            'order_tracking'  => ['category' => 'ecommerce', 'label' => 'Sipariş Takip', 'icon' => '📦', 'kind_scope' => 'mobile', 'sector_scope' => ['ecommerce']],
            'product_list'    => ['category' => 'ecommerce', 'label' => 'Ürün Listesi', 'icon' => '🛍', 'kind_scope' => 'mobile', 'sector_scope' => ['ecommerce']],
            'categories_grid' => ['category' => 'ecommerce', 'label' => 'Kategori Grid', 'icon' => '🗂', 'kind_scope' => 'mobile', 'sector_scope' => ['ecommerce']],
            'banner_slider'   => ['category' => 'ecommerce', 'label' => 'Kampanya Slider', 'icon' => '🎯', 'kind_scope' => 'mobile'],

            // ====== SEKTÖR-ÖZEL: SPOR / RANDEVU ======
            'schedule_grid'    => ['category' => 'gym', 'label' => 'Ders Programı', 'icon' => '📆', 'kind_scope' => 'mobile', 'sector_scope' => ['gym']],
            'trainers_list'    => ['category' => 'gym', 'label' => 'Antrenör Listesi', 'icon' => '💪', 'kind_scope' => 'mobile', 'sector_scope' => ['gym']],
            'calendar'         => ['category' => 'booking', 'label' => 'Takvim', 'icon' => '📅', 'kind_scope' => 'mobile', 'sector_scope' => ['booking']],
            'service_selector' => ['category' => 'booking', 'label' => 'Hizmet Seçici', 'icon' => '📋', 'kind_scope' => 'mobile', 'sector_scope' => ['booking']],

            // ====== SEKTÖR-ÖZEL: HABER ======
            'featured_news' => ['category' => 'news', 'label' => 'Öne Çıkan Haber', 'icon' => '📰', 'kind_scope' => 'mobile', 'sector_scope' => ['news']],
            'news_list'     => ['category' => 'news', 'label' => 'Haber Listesi', 'icon' => '📰', 'kind_scope' => 'mobile', 'sector_scope' => ['news']],
            'category_tabs' => ['category' => 'news', 'label' => 'Kategori Tabları', 'icon' => '📑', 'kind_scope' => 'mobile', 'sector_scope' => ['news']],

            // ====== ORTAK / MEDIA ======
            'social_links'  => ['category' => 'content', 'label' => 'Sosyal Medya', 'icon' => '🔗', 'kind_scope' => 'both'],
            'whatsapp_btn'  => ['category' => 'content', 'label' => 'WhatsApp Butonu', 'icon' => '💬', 'kind_scope' => 'both'],
            'quick_call_button' => ['category' => 'content', 'label' => 'Hemen Ara', 'icon' => '📞', 'kind_scope' => 'mobile'],
            'map'           => ['category' => 'content', 'label' => 'Harita', 'icon' => '🗺', 'kind_scope' => 'both'],
            'about'         => ['category' => 'content', 'label' => 'Hakkımızda', 'icon' => 'ℹ️', 'kind_scope' => 'both'],
            'footer'        => ['category' => 'structural', 'label' => 'Footer', 'icon' => '━', 'kind_scope' => 'site'],
        ];
    }

    /** Bir builder tipi + sektör için görünmesi gereken bloklar. */
    public static function forContext(string $kind, ?string $sector = null): array
    {
        $out = [];
        foreach (self::all() as $type => $meta) {
            $scope = $meta['kind_scope'] ?? 'both';
            if ($scope !== 'both' && $scope !== $kind) continue;

            // Sektör kısıtı — belirtilmiş sektör dışında ise filtrele
            if ($sector !== null && !empty($meta['sector_scope'])) {
                if (!in_array($sector, $meta['sector_scope'], true)) continue;
            }

            $out[$type] = array_merge($meta, ['type' => $type]);
        }
        return $out;
    }

    /** Kategoriye göre gruplanmış bloklar (menu için). */
    public static function grouped(string $kind, ?string $sector = null): array
    {
        $blocks = self::forContext($kind, $sector);
        $groups = [];
        foreach ($blocks as $type => $b) {
            $cat = $b['category'] ?? 'other';
            $groups[$cat] ??= [];
            $groups[$cat][] = $b;
        }
        return $groups;
    }

    /** Kategori etiketleri (Türkçe). */
    public static function categoryLabels(): array
    {
        return [
            'structural' => 'Yapı',
            'content'    => 'İçerik',
            'marketing'  => 'Pazarlama',
            'data'       => 'Dinamik Veri',
            'form'       => 'Form / Etkileşim',
            'radio'      => 'Radyo',
            'restaurant' => 'Restoran',
            'ecommerce'  => 'E-Ticaret',
            'gym'        => 'Spor Salonu',
            'booking'    => 'Randevu',
            'news'       => 'Haber',
            'other'      => 'Diğer',
        ];
    }
}
