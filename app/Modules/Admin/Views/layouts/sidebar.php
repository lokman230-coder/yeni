<?php
$groups = [
    'Ana Panel' => [
        ['icon' => '📊', 'label' => 'Kontrol Paneli', 'url' => '/admin'],
        ['icon' => '✅', 'label' => 'QA Scan', 'url' => '/admin/qa-scan-center'],
        ['icon' => '❤', 'label' => 'Health Center', 'url' => '/admin/health-center'],
    ],
    'Satış ve Finans' => [
        ['icon' => '📦', 'label' => 'Siparişler', 'url' => '/admin/siparisler'],
        ['icon' => '🧾', 'label' => 'Faturalar', 'url' => '/admin/faturalar'],
        ['icon' => '💳', 'label' => 'Ödemeler', 'url' => '/admin/odemeler'],
        ['icon' => '🎟', 'label' => 'Kuponlar', 'url' => '/admin/kuponlar'],
        ['icon' => '💱', 'label' => 'Kur Yönetimi', 'url' => '/admin/kur-yonetimi'],
        ['icon' => '🎁', 'label' => 'Referans / Affiliate', 'url' => '/admin/referral'],
        ['icon' => '📈', 'label' => 'Finans', 'url' => '/admin/finans'],
    ],
    'Ürünler ve Altyapı' => [
        ['icon' => '🛒', 'label' => 'Ürün Merkezi', 'url' => '/admin/urun-merkezi'],
        ['icon' => '🎛', 'label' => 'Paket Opsiyonları', 'url' => '/admin/paket-opsiyonlari'],
        ['icon' => '🌐', 'label' => 'Domain Center', 'url' => '/admin/domain-center'],
        ['icon' => '🌐', 'label' => 'TLD Yönetimi', 'url' => '/admin/tld-yonetimi'],
        ['icon' => '🖥', 'label' => 'Hosting & Sunucu', 'url' => '/admin/hosting-sunucu'],
        ['icon' => '🔑', 'label' => 'Lisanslar', 'url' => '/admin/lisanslar'],
    ],
    'Tasarım ve Builder' => [
        ['icon' => '🎨', 'label' => 'Temalar', 'url' => '/admin/temalar'],
        ['icon' => '🧩', 'label' => 'Tema Blokları', 'url' => '/admin/tema-bloklari'],
        ['icon' => '📋', 'label' => 'Menü Yönetimi', 'url' => '/admin/menu-yonetimi'],
        ['icon' => '🎨', 'label' => 'Site Builder', 'url' => '/admin/site-builder'],
        ['icon' => '📱', 'label' => 'Mobile Builder', 'url' => '/admin/mobile-builder'],
        ['icon' => '⚙', 'label' => 'Mobile Build İşleri', 'url' => '/admin/mobile-buildler'],
        ['icon' => '🛍', 'label' => 'Marketplace', 'url' => '/admin/marketplace'],
        ['icon' => '🏪', 'label' => 'Vendorlar', 'url' => '/admin/vendorlar'],
    ],
    'Müşteri ve İçerik' => [
        ['icon' => '👥', 'label' => 'Müşteriler', 'url' => '/admin/musteriler'],
        ['icon' => '🎧', 'label' => 'Destek Merkezi', 'url' => '/admin/destek-merkezi'],
        ['icon' => '📄', 'label' => 'Sayfalar', 'url' => '/admin/sayfalar'],
        ['icon' => '✍', 'label' => 'Blog', 'url' => '/admin/blog'],
        ['icon' => '📢', 'label' => 'Duyurular', 'url' => '/admin/duyurular'],
        ['icon' => '🎨', 'label' => 'Portfolio', 'url' => '/admin/portfolio'],
    ],
    'Araçlar ve Otomasyon' => [
        ['icon' => '🔌', 'label' => 'Veri Aktarımı', 'url' => '/admin/veri-aktarimi'],
        ['icon' => '🛠', 'label' => 'Site Araçları', 'url' => '/admin/site-araclari'],
        ['icon' => '🤖', 'label' => 'AI Center', 'url' => '/admin/ai-center'],
        ['icon' => '💬', 'label' => 'AI Asistan', 'url' => '/admin/ai-asistan'],
        ['icon' => '🍪', 'label' => 'Çerez Analizi', 'url' => '/admin/cerez-analizi'],
    ],
    'Sistem' => [
        ['icon' => '🔐', 'label' => 'Güvenlik / 2FA', 'url' => '/admin/guvenlik'],
        ['icon' => '💾', 'label' => 'Yedekleme', 'url' => '/admin/yedekleme'],
        ['icon' => '⚙', 'label' => 'Ayarlar', 'url' => '/admin/ayarlar'],
        ['icon' => '🔌', 'label' => 'Modül Merkezi', 'url' => '/admin/modul-merkezi'],
        ['icon' => '📜', 'label' => 'Loglar', 'url' => '/admin/loglar'],
        ['icon' => '⚡', 'label' => 'Cache Center', 'url' => '/admin/cache-center'],
    ],
];

$current = rtrim(current_url(), '/') ?: '/admin';
$isActive = static function (string $url) use ($current): bool {
    $url = rtrim($url, '/') ?: '/admin';
    if ($url === '/admin') {
        return $current === '/admin';
    }
    return $current === $url || str_starts_with($current . '/', $url . '/');
};
?>
<aside class="aho-admin-sidebar" id="ahoAdminSidebar">
    <a href="/admin" class="aho-admin-sidebar__brand">
        <img src="<?= asset('img/logo-icon.png') ?>" alt="" width="32" height="32">
        <span>Ahost <b>Bilişim</b></span>
    </a>
    <nav class="aho-admin-sidebar__nav" aria-label="Admin menü">
        <?php foreach ($groups as $groupLabel => $items):
            $groupActive = false;
            foreach ($items as $item) {
                if ($isActive($item['url'])) { $groupActive = true; break; }
            }
        ?>
            <details class="aho-admin-sidebar__group" data-admin-menu-group="<?= e($groupLabel) ?>" <?= $groupActive ? 'open' : '' ?>>
                <summary class="aho-admin-sidebar__group-title">
                    <span><?= e($groupLabel) ?></span>
                    <span class="aho-admin-sidebar__chevron">▾</span>
                </summary>
                <div class="aho-admin-sidebar__group-links">
                    <?php foreach ($items as $item): $active = $isActive($item['url']); ?>
                        <a href="<?= e($item['url']) ?>" class="aho-admin-sidebar__link <?= $active ? 'is-active' : '' ?>">
                            <span class="aho-admin-sidebar__icon"><?= $item['icon'] ?></span>
                            <span><?= e($item['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </details>
        <?php endforeach; ?>
    </nav>
</aside>
