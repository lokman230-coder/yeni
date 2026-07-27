<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>🔌 <?= e($driver->label()) ?> Bağlantısı</h1>
            <p>MySQL DB bilgilerini girin, bağlantıyı test edin, sonra hangi verileri alacağınızı seçin.</p>
        </div>
        <a href="/admin/veri-aktarimi" class="aho-btn aho-btn--ghost">← Kaynaklar</a>
    </div>

    <?php if (!empty($test)): ?>
        <div class="aho-alert aho-alert--<?= $test['ok'] ? 'success' : 'danger' ?>"><?= e($test['message']) ?></div>
    <?php endif; ?>

    <form method="post" action="/admin/veri-aktarimi/baglan/<?= e($source) ?>/test">
        <?= csrf() ?>
        <div class="aho-card" style="padding:24px;margin-bottom:16px">
            <h3 style="margin-top:0;font-size:15px">🔐 Bağlantı Bilgileri</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px">
                <?php foreach ($fields as $key => $spec): ?>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">
                            <?= e($spec['label']) ?><?= !empty($spec['required']) ? ' *' : '' ?>
                        </label>
                        <input type="<?= e($spec['type'] ?? 'text') ?>"
                               name="config_<?= e($key) ?>"
                               value="<?= e((string)($config[$key] ?? ($spec['default'] ?? ''))) ?>"
                               <?= !empty($spec['required']) ? 'required' : '' ?>
                               style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:8px;font-family:<?= ($spec['type']??'')==='password' ? 'monospace' : 'inherit' ?>;box-sizing:border-box">
                        <?php if (!empty($spec['hint'])): ?>
                            <div style="font-size:11px;color:var(--aho-color-ink-500);margin-top:2px"><?= e($spec['hint']) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="display:flex;gap:8px;margin-top:16px">
                <button type="submit" class="aho-btn aho-btn--primary">🔍 Bağlantıyı Test Et</button>
            </div>
        </div>
    </form>

    <?php if (!empty($counts) && !empty($test) && $test['ok']): ?>
    <form method="post" action="/admin/veri-aktarimi/baglan/<?= e($source) ?>/baslat">
        <?= csrf() ?>
        <?php foreach ($config as $k => $v): ?>
            <input type="hidden" name="config_<?= e($k) ?>" value="<?= e((string)$v) ?>">
        <?php endforeach; ?>

        <div class="aho-card" style="padding:24px">
            <h3 style="margin-top:0;font-size:15px">📦 Neyi Aktaralım?</h3>
            <p style="color:var(--aho-color-ink-600);font-size:13px">Aktarılacak veri tiplerini seçin. Sipariş/fatura için önce müşteriler aktarılmalı. Sistem sıralamayı otomatik yapar.</p>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;margin-top:16px">
                <?php
                $meta = [
                    // Yapılandırma (önce çekmek mantıklı)
                    'settings'      => ['icon'=>'⚙️','label'=>'Sistem Ayarları','recommend'=>false, 'group'=>'Yapılandırma'],
                    'servers'       => ['icon'=>'🖥️','label'=>'Sunucular','recommend'=>true,  'group'=>'Yapılandırma'],
                    'registrars'    => ['icon'=>'🔗','label'=>'Registrar Ayarları','recommend'=>true, 'group'=>'Yapılandırma'],
                    // Katalog
                    'products'      => ['icon'=>'🛒','label'=>'Ürünler','recommend'=>true,  'group'=>'Katalog'],
                    'addons'        => ['icon'=>'📦','label'=>'Ek Paketler','recommend'=>true, 'group'=>'Katalog'],
                    'custom_fields' => ['icon'=>'📝','label'=>'Özel Alanlar','recommend'=>true, 'group'=>'Katalog'],
                    // Müşteri verisi
                    'customers'     => ['icon'=>'👥','label'=>'Müşteriler','recommend'=>true, 'group'=>'Müşteri'],
                    'orders'        => ['icon'=>'📄','label'=>'Siparişler','recommend'=>false,'group'=>'Müşteri'],
                    'invoices'      => ['icon'=>'🧾','label'=>'Faturalar','recommend'=>false,'group'=>'Müşteri'],
                    'domains'       => ['icon'=>'🌐','label'=>'Domainler','recommend'=>true, 'group'=>'Müşteri'],
                    'hosting'       => ['icon'=>'🌍','label'=>'Hosting Hesapları','recommend'=>true, 'group'=>'Müşteri'],
                    'tickets'       => ['icon'=>'🎧','label'=>'Destek Talepleri','recommend'=>false,'group'=>'Müşteri'],
                ];
                foreach ($meta as $key => $m):
                    $cnt = (int) ($counts[$key] ?? 0);
                    $disabled = $cnt === 0;
                ?>
                    <label style="display:block;padding:14px;background:<?= $disabled ? '#f9fafb' : '#fff' ?>;border:2px solid #e5e7eb;border-radius:10px;cursor:<?= $disabled ? 'not-allowed' : 'pointer' ?>;opacity:<?= $disabled ? 0.5 : 1 ?>">
                        <div style="display:flex;gap:8px;align-items:center;margin-bottom:6px">
                            <input type="checkbox" name="types[]" value="<?= e($key) ?>" <?= $m['recommend'] && !$disabled ? 'checked' : '' ?> <?= $disabled ? 'disabled' : '' ?>>
                            <span style="font-size:20px"><?= $m['icon'] ?></span>
                            <strong style="font-size:14px"><?= e($m['label']) ?></strong>
                        </div>
                        <div style="font-size:12px;color:var(--aho-color-ink-500);margin-left:26px">
                            <?= number_format($cnt) ?> kayıt<?= $disabled ? ' (boş)' : '' ?>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>

            <div style="margin-top:20px;text-align:right">
                <button type="submit" class="aho-btn aho-btn--primary aho-btn--lg">🚀 Aktarım İşlerini Oluştur</button>
            </div>
        </div>
    </form>
    <?php endif; ?>

    <div class="aho-card" style="padding:16px;margin-top:16px;background:#fef3c7;border-left:4px solid #d97706;font-size:13px;color:#78350f;line-height:1.6">
        ⚠️ <strong>Önemli:</strong> Kaynak DB'ye <strong>READ-ONLY</strong> kullanıcı ile bağlanın. Aktarım kaynak sistemi değiştirmez ama yazma erişimi verirseniz kaza riski vardır.
        Şifreler AES-256-GCM ile şifreli saklanır. Aktarım sonrası tekrar test etmek için config'i tekrar girebilirsiniz.
    </div>
</div>
<?php $view->endSection(); ?>
