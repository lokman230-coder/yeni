<?php

declare(strict_types=1);

namespace App\Modules\Builder\Services;

use App\Core\Database\Connection;

/**
 * Şablon kütüphanesi — Site + Mobile için sektör bazlı hazır şablonlar.
 * DB'deki `builder_templates` kayıtları üzerinden çalışır.
 */
final class TemplateLibrary
{
    /** Site Builder sektörleri (şartname madde 23). */
    public static function siteSectors(): array
    {
        return [
            'hosting'    => ['icon' => '🌐', 'label' => 'Hosting Firması'],
            'agency'     => ['icon' => '💼', 'label' => 'Ajans / Kurumsal'],
            'landing'    => ['icon' => '🚀', 'label' => 'Landing Page'],
            'radio'      => ['icon' => '📻', 'label' => 'Radyo / Medya'],
            'ecommerce'  => ['icon' => '🛍️', 'label' => 'E-Ticaret'],
            'restaurant' => ['icon' => '🍽️', 'label' => 'Restoran'],
            'clinic'     => ['icon' => '🏥', 'label' => 'Doktor / Klinik'],
            'education'  => ['icon' => '🎓', 'label' => 'Eğitim'],
            'portfolio'  => ['icon' => '🎨', 'label' => 'Portfolyo'],
            'saas'       => ['icon' => '⚡', 'label' => 'SaaS'],
            'local'      => ['icon' => '📍', 'label' => 'Yerel İşletme'],
        ];
    }

    /** Mobile Builder sektörleri (şartname madde 24). */
    public static function mobileSectors(): array
    {
        return [
            'radio'      => ['icon' => '📻', 'label' => 'Radyo Uygulaması'],
            'corporate'  => ['icon' => '💼', 'label' => 'Kurumsal'],
            'restaurant' => ['icon' => '🍽️', 'label' => 'Restoran'],
            'ecommerce'  => ['icon' => '🛍️', 'label' => 'E-Ticaret'],
            'news'       => ['icon' => '📰', 'label' => 'Haber'],
            'education'  => ['icon' => '🎓', 'label' => 'Eğitim'],
            'gym'        => ['icon' => '💪', 'label' => 'Spor Salonu'],
            'booking'    => ['icon' => '📅', 'label' => 'Randevu'],
        ];
    }

    /** Bir bağlam (site/mobile) için o sektöre uygun şablonlar. */
    public static function forSector(string $kind, string $sector): array
    {
        try {
            return Connection::select(
                "SELECT * FROM builder_templates
                 WHERE kind = ? AND sector = ? AND is_active = 1
                 ORDER BY sort_order ASC, id ASC",
                [$kind, $sector]
            );
        } catch (\Throwable) {
            return [];
        }
    }

    /** Tüm aktif şablonlar. */
    public static function all(string $kind): array
    {
        try {
            return Connection::select(
                "SELECT * FROM builder_templates WHERE kind = ? AND is_active = 1 ORDER BY sector, sort_order",
                [$kind]
            );
        } catch (\Throwable) {
            return [];
        }
    }

    public static function get(int $id): ?array
    {
        try {
            return Connection::selectOne("SELECT * FROM builder_templates WHERE id = ?", [$id]);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Şablon içeriğini varsayılan blok ağacı ile döndürür.
     * DB'deki tree_json boş ise sektöre göre başlangıç blok seti üretir.
     */
    public static function starterTree(string $kind, string $sector, array $settings = []): array
    {
        if ($kind === 'mobile') {
            return self::mobileStarterTree($sector, $settings);
        }
        return self::siteStarterTree($sector, $settings);
    }

    private static function siteStarterTree(string $sector, array $settings): array
    {
        $appName = $settings['app_name'] ?? 'Yeni Site';
        $tagline = $settings['tagline'] ?? 'İşinizi büyütmenin en akıllı yolu';

        $blocks = [
            [
                'type' => 'hero',
                'props' => [
                    'title'    => $appName,
                    'subtitle' => $tagline,
                    'cta_text' => 'Hemen Başla',
                    'cta_link' => '#',
                ],
            ],
        ];

        // Sektöre özel bloklar
        $blocks = array_merge($blocks, match ($sector) {
            'hosting' => [
                ['type' => 'pricing', 'props' => ['title' => 'Hosting Paketlerimiz']],
                ['type' => 'features', 'props' => ['title' => 'Neden Biz?', 'items' => ['NVMe SSD', 'Ücretsiz SSL', 'Günlük Yedek', '7/24 Destek']]],
                ['type' => 'cta', 'props' => ['title' => 'Hosting Almaya Hazır mısınız?', 'button' => 'Paketleri İncele']],
            ],
            'radio' => [
                ['type' => 'radio_player', 'props' => ['stream_url' => '', 'title' => 'Canlı Yayın']],
                ['type' => 'dj_schedule', 'props' => ['title' => 'Yayın Akışı']],
                ['type' => 'social_links', 'props' => []],
            ],
            'ecommerce' => [
                ['type' => 'product_grid', 'props' => ['title' => 'Ürünlerimiz', 'columns' => 4]],
                ['type' => 'categories', 'props' => []],
                ['type' => 'testimonials', 'props' => []],
            ],
            'restaurant' => [
                ['type' => 'menu', 'props' => ['title' => 'Menü']],
                ['type' => 'reservation_form', 'props' => []],
                ['type' => 'map', 'props' => []],
            ],
            'clinic' => [
                ['type' => 'services', 'props' => ['title' => 'Hizmetlerimiz']],
                ['type' => 'team', 'props' => ['title' => 'Doktorlarımız']],
                ['type' => 'appointment_form', 'props' => []],
            ],
            'portfolio' => [
                ['type' => 'gallery', 'props' => ['title' => 'Çalışmalarım']],
                ['type' => 'about', 'props' => []],
                ['type' => 'contact', 'props' => []],
            ],
            'saas' => [
                ['type' => 'features', 'props' => ['title' => 'Özellikler']],
                ['type' => 'pricing', 'props' => ['title' => 'Fiyatlandırma']],
                ['type' => 'faq', 'props' => []],
            ],
            default => [
                ['type' => 'features', 'props' => ['title' => 'Öne Çıkanlar']],
                ['type' => 'about', 'props' => []],
                ['type' => 'contact', 'props' => []],
            ],
        });

        $blocks[] = ['type' => 'footer', 'props' => ['copyright' => '© ' . date('Y') . ' ' . $appName]];

        return ['version' => 1, 'blocks' => $blocks];
    }

    private static function mobileStarterTree(string $sector, array $settings): array
    {
        $appName = $settings['app_name'] ?? 'Uygulamam';

        $screens = [
            [
                'name' => 'Ana Ekran',
                'blocks' => match ($sector) {
                    'radio' => [
                        ['type' => 'radio_player', 'props' => ['stream_url' => '', 'title' => 'Canlı Yayın']],
                        ['type' => 'now_playing', 'props' => []],
                        ['type' => 'dj_schedule', 'props' => []],
                    ],
                    'ecommerce' => [
                        ['type' => 'banner_slider', 'props' => []],
                        ['type' => 'categories_grid', 'props' => []],
                        ['type' => 'product_list', 'props' => ['title' => 'Popüler Ürünler']],
                    ],
                    'restaurant' => [
                        ['type' => 'hero', 'props' => ['title' => $appName]],
                        ['type' => 'menu_categories', 'props' => []],
                        ['type' => 'quick_call_button', 'props' => []],
                    ],
                    'news' => [
                        ['type' => 'featured_news', 'props' => []],
                        ['type' => 'category_tabs', 'props' => []],
                        ['type' => 'news_list', 'props' => []],
                    ],
                    'gym' => [
                        ['type' => 'schedule_grid', 'props' => []],
                        ['type' => 'trainers_list', 'props' => []],
                    ],
                    'booking' => [
                        ['type' => 'calendar', 'props' => []],
                        ['type' => 'service_selector', 'props' => []],
                    ],
                    default => [
                        ['type' => 'hero', 'props' => ['title' => $appName]],
                        ['type' => 'features', 'props' => []],
                        ['type' => 'contact', 'props' => []],
                    ],
                },
            ],
            [
                'name' => 'Hakkımızda',
                'blocks' => [['type' => 'about', 'props' => []]],
            ],
        ];

        // Sektöre özel ek ekranlar
        if ($sector === 'ecommerce') {
            $screens[] = ['name' => 'Sepet', 'blocks' => [['type' => 'cart', 'props' => []]]];
            $screens[] = ['name' => 'Sipariş Takip', 'blocks' => [['type' => 'order_tracking', 'props' => []]]];
        }
        if ($sector === 'radio') {
            $screens[] = ['name' => 'İstek Hattı', 'blocks' => [['type' => 'song_request', 'props' => []]]];
        }

        return [
            'version'    => 1,
            'kind'       => 'mobile',
            'app_name'   => $appName,
            'bottom_nav' => true,
            'screens'    => $screens,
        ];
    }
}
