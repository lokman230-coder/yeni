<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
$isMobile = $project['kind'] === 'mobile';
$isGuest = !empty($is_guest);

$exportTypes = $isMobile ? [

    ['mobile_apk',          '📱 Android APK',       'Play Store dışı dağıtım için hazır APK dosyası.',   299],
    ['mobile_aab',          '📦 Android AAB Bundle', 'Play Store\'a yüklemeye hazır App Bundle.',        499],
    ['pwa',                '🌐 PWA Web App',       'VPS olmadan tarayıcıda ve ana ekranda çalışan mobil uygulama.', 0],
    ['flutter_source',      '🎯 Flutter Kaynak Kod', 'Tam düzenlenebilir Flutter kaynak kodu (Dart).',   999],
    ['react_native_source', '⚛️ React Native Kaynak', 'React Native + Expo kaynak kodu.',                 999],
    ['android_source',      '🤖 Native Android Kaynak','Kotlin + Jetpack Compose kaynak kodu.',         1499],
] : [
    ['site_zip', '📦 HTML/CSS/JS ZIP', 'Sitenin tam paketi — herhangi bir hostinge yükleyebilirsin.', 0],
];
if ($isMobile) {
    if (!site_setting('mobile.enable_pwa', true)) $exportTypes = array_values(array_filter($exportTypes, fn($e) => $e[0] !== 'pwa'));
    if (!site_setting('mobile.enable_apk', false)) $exportTypes = array_values(array_filter($exportTypes, fn($e) => $e[0] !== 'mobile_apk'));
    if (!site_setting('mobile.enable_aab', false)) $exportTypes = array_values(array_filter($exportTypes, fn($e) => $e[0] !== 'mobile_aab'));
    if (!site_setting('mobile.enable_source', true)) $exportTypes = array_values(array_filter($exportTypes, fn($e) => !in_array($e[0], ['flutter_source','react_native_source','android_source'], true)));
}
?>
<section style="padding:32px 0;background:#f9fafb;min-height:calc(100vh - 200px)">
    <div class="aho-container" style="max-width:900px">
        <a href="/panel/builder/<?= (int)$project['id'] ?>/editor" style="color:#6b7280;font-size:13px;text-decoration:none">← Editör</a>
        <h1 style="margin:8px 0 16px;font-size:26px">📤 Export & İndirme — <?= e($project['name']) ?></h1>

        <?php if ($success = flash('success')): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
        <?php if ($info = flash('info')): ?><div class="aho-alert aho-alert--info"><?= e($info) ?></div><?php endif; ?>
        <?php if ($error = flash('error')): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

        <?php if ($isGuest): ?>
            <div class="aho-card" style="padding:22px;margin-bottom:24px">
                <h2 style="margin:0 0 8px;font-size:20px">Paket secerek devam et</h2>
                <p style="margin:0;color:#6b7280;line-height:1.5">Giris yapmadan demo hazirlayabilirsin. Kaynak kod, APK veya AAB almak icin uygun paketi satin alip hesabinla devam etmen gerekir.</p>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;margin-bottom:24px">
                <?php foreach (($isMobile ? [
                    ['Mobile PWA Paketi', 'Tarayicida calisan mobil uygulama paketi.', '/urun/mobile-builder-pwa-paketi'],
                    ['Android APK Paketi', 'Play Store disi dagitim icin APK.', '/urun/mobile-builder-apk-paketi'],
                    ['Android AAB Paketi', 'Play Store yuklemesi icin AAB.', '/urun/mobile-builder-aab-paketi'],
                    ['Mobil Kaynak Kod', 'Tam duzenlenebilir kaynak kod.', '/urun/mobile-builder-kaynak-kod-paketi'],
                ] : [
                    ['Site Builder Baslangic', 'Site Builder ile yayinlama ve temel ciktilar.', '/urun/site-builder-baslangic'],
                    ['Site Builder Pro', 'Gelismis site builder paketi.', '/urun/site-builder-pro'],
                    ['Kaynak Kod Paketi', 'HTML/CSS/JS kaynak kod cikisi.', '/urun/site-builder-kaynak-kod-paketi'],
                ]) as $pkg): ?>
                    <a href="<?= e($pkg[2]) ?>" class="aho-card aho-card--hover" style="text-decoration:none;color:inherit;padding:20px">
                        <h3 style="margin:0 0 8px"><?= e($pkg[0]) ?></h3>
                        <p style="margin:0 0 14px;color:#6b7280;font-size:13px;line-height:1.5"><?= e($pkg[1]) ?></p>
                        <span class="aho-btn aho-btn--primary aho-btn--sm">Paketi Incele</span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:14px;margin-bottom:24px">
            <?php foreach ($exportTypes as $ex): ?>
                <div class="aho-card" style="padding:20px;display:flex;flex-direction:column">
                    <div style="font-size:24px;margin-bottom:8px"><?= $ex[1] ?></div>
                    <p style="font-size:13px;color:#6b7280;flex:1;line-height:1.5"><?= e($ex[2]) ?></p>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px">
                        <strong style="font-size:18px;color:#0ea5e9"><?= $ex[3] > 0 ? number_format($ex[3], 0) . ' ₺' : 'Ücretsiz' ?></strong>
                        <form method="post" action="/panel/builder/<?= (int)$project['id'] ?>/export/talep">
                            <?= csrf() ?>
                            <input type="hidden" name="export_type" value="<?= e($ex[0]) ?>">
                            <button type="submit" class="aho-btn aho-btn--primary aho-btn--sm">
                                <?= $ex[3] > 0 ? '🛒 Satın Al' : '⬇ İndir' ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="aho-card" style="padding:0;overflow:hidden">
            <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb"><h3 style="margin:0;font-size:16px">📋 Export Geçmişi</h3></div>
            <table style="width:100%;font-size:14px">
                <thead style="background:#f9fafb;text-align:left">
                    <tr><th style="padding:10px 20px">Tip</th><th style="padding:10px">Fiyat</th><th style="padding:10px">Durum</th><th style="padding:10px">Tarih</th><th style="padding:10px"></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($jobs as $j):
                        $badge = match($j['status']) {
                            'ready'      => ['✓ Hazır', '#059669', '#d1fae5'],
                            'building'   => ['⚙ Hazırlanıyor', '#d97706', '#fef3c7'],
                            'pending'    => ['⏳ Ödeme bekliyor', '#0891b2', '#cffafe'],
                            'downloaded' => ['✓ İndirildi', '#6b7280', '#f3f4f6'],
                            'failed'     => ['✗ Başarısız', '#dc2626', '#fee2e2'],
                            'completed'  => ['✓ Hazır', '#059669', '#d1fae5'],
                            'running'    => ['⚙ Çalışıyor', '#d97706', '#fef3c7'],
                            'waiting_worker' => ['⏳ Worker bekliyor', '#0891b2', '#cffafe'],
                            default      => [$j['status'], '#6b7280', '#f3f4f6'],
                        };
                    ?>
                        <tr style="border-top:1px solid #f3f4f6">
                            <td style="padding:12px 20px"><?= e($j['export_type']) ?></td>
                            <td style="padding:12px"><?= (float)$j['price'] > 0 ? number_format((float)$j['price'], 2) . ' ' . e($j['currency']) : 'Ücretsiz' ?></td>
                            <td style="padding:12px"><span style="padding:3px 10px;border-radius:10px;font-size:12px;color:<?= $badge[1] ?>;background:<?= $badge[2] ?>"><?= e($badge[0]) ?></span></td>
                            <td style="padding:12px;font-size:12px;color:#6b7280"><?= e(date('d.m.Y H:i', strtotime((string)$j['created_at']))) ?></td>
                            <td style="padding:12px">
                                <?php if (in_array($j['status'], ['ready','downloaded','completed'], true)): ?>
                                    <?php if (in_array($j['export_type'], ['apk','aab'], true)): ?>
                                        <a href="/mobile-build/<?= (int)$j['id'] ?>/indir" class="aho-btn aho-btn--sm aho-btn--primary">⬇ İndir</a>
                                    <?php else: ?>
                                        <a href="/panel/builder/export/<?= (int)$j['id'] ?>/indir" class="aho-btn aho-btn--sm aho-btn--primary">⬇ İndir</a>
                                    <?php endif; ?>
                                <?php elseif ($j['status'] === 'pending' && $j['invoice_id']): ?>
                                    <a href="/odeme/<?= (int)$j['invoice_id'] ?>" class="aho-btn aho-btn--sm aho-btn--outline">💳 Öde</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$jobs): ?><tr><td colspan="5" style="text-align:center;padding:30px;color:#6b7280">Henüz export talebi yok.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php $view->endSection(); ?>
