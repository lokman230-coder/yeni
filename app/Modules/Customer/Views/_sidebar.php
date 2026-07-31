<?php
$currentPath = function_exists('ao_request_path_no_base') ? ao_request_path_no_base() : ($_SERVER['REQUEST_URI'] ?? '/');
$currentPath = strtok($currentPath, '?');
$menu = [
    ['url' => '/panel',              'label' => 'Kontrol Paneli',    'icon' => '📊'],
    ['url' => '/panel/hizmetlerim',  'label' => 'Hizmetlerim',       'icon' => '🖥️'],
    ['url' => '/panel/domainlerim',  'label' => 'Domainlerim',       'icon' => '🌐'],
    ['url' => '/panel/faturalarim',  'label' => 'Faturalarım',       'icon' => '🧾'],
    ['url' => '/panel/odemelerim',   'label' => 'Ödemelerim',        'icon' => '💳'],
    ['url' => '/panel/bakiye',       'label' => 'Bakiyem',           'icon' => '💰'],
    ['url' => '/panel/kartlar',      'label' => 'Kartlarım',         'icon' => '💳'],
    ['url' => '/panel/siparislerim', 'label' => 'Siparişlerim',      'icon' => '📦'],
    ['url' => '/panel/referanslarim','label' => 'Referans Programım','icon' => '🎁'],
    ['url' => '/panel/backorder',    'label' => 'Backorder',         'icon' => '🎯'],
    ['url' => '/panel/satici',       'label' => 'Satıcı Paneli',     'icon' => '🏪'],
    ['url' => '/panel/site-builder', 'label' => 'Site Builder',      'icon' => '🎨'],
    ['url' => '/panel/guvenlik',     'label' => 'Güvenlik / 2FA',    'icon' => '🔐'],
    ['url' => '/destek',             'label' => 'Destek',            'icon' => '🎧'],
];
?>
<aside class="aho-card" style="padding:12px;height:fit-content;position:sticky;top:20px">
    <nav>
        <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:2px">
            <?php foreach ($menu as $m):
                $active = $m['url'] === $currentPath || ($m['url'] !== '/panel' && str_starts_with($currentPath, $m['url']));
            ?>
                <li>
                    <a href="<?= e($m['url']) ?>"
                       style="display:flex;gap:10px;align-items:center;padding:10px 12px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:500;
                              color:<?= $active ? '#fff' : 'var(--aho-color-ink-700)' ?>;
                              background:<?= $active ? 'var(--aho-color-primary-600, #0ea5e9)' : 'transparent' ?>;">
                        <span style="font-size:16px"><?= $m['icon'] ?></span>
                        <?= e($m['label']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--aho-color-border)">
        <form method="post" action="/cikis" style="margin:0">
            <?= csrf() ?>
            <button type="submit" style="width:100%;padding:8px;background:none;border:1px solid var(--aho-color-border);border-radius:8px;color:#dc2626;cursor:pointer;font-size:13px;font-weight:600">
                🚪 Çıkış Yap
            </button>
        </form>
    </div>
</aside>
