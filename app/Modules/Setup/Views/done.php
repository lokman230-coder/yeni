<?php
/** @var \App\Core\View $view */
$view->extend('layouts.blank');
$view->section('content');
?>
<div style="min-height:100vh;background:linear-gradient(135deg,#059669 0%,#0891b2 100%);padding:40px 20px;display:flex;align-items:center;justify-content:center;font-family:system-ui,sans-serif">
    <div style="max-width:560px;text-align:center;color:#fff">
        <div style="font-size:96px">🎉</div>
        <h1 style="font-size:36px;margin:16px 0 8px">Kurulum Tamamlandı!</h1>
        <p style="opacity:.9;font-size:16px;margin:0 0 32px">
            Ahost Bilişim platformunuz kullanıma hazır. Artık müşteri kayıtları alabilir, hizmet satabilirsiniz.
        </p>

        <div style="background:rgba(255,255,255,.15);border-radius:12px;padding:24px;margin-bottom:24px;text-align:left">
            <h3 style="margin:0 0 12px;font-size:16px">🚀 Sıradaki adımlar:</h3>
            <ul style="margin:0;padding-left:20px;line-height:1.9;font-size:14px">
                <li>Admin panelinden ürünlerinizi yapılandırın (hosting, domain fiyatları)</li>
                <li>Ödeme sağlayıcı bilgilerini (PayTR/iyzico/Papara) girin</li>
                <li>Kur yönetiminden marj oranlarınızı ayarlayın</li>
                <li>Referans programı ayarlarını yapın</li>
                <li>SMTP ayarlarını tamamlayın (mail gönderimi için)</li>
                <li>DNS'inizi yapılandırın ve https sertifikanızı kurun</li>
            </ul>
        </div>

        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
            <a href="/admin" style="padding:14px 28px;background:#fff;color:#0891b2;text-decoration:none;border-radius:8px;font-weight:700;font-size:15px">
                🎛️ Admin Panele Git
            </a>
            <a href="/" style="padding:14px 28px;background:rgba(255,255,255,.2);color:#fff;text-decoration:none;border-radius:8px;font-weight:600;font-size:15px;border:1px solid rgba(255,255,255,.3)">
                🏠 Ana Sayfa
            </a>
        </div>

        <p style="margin-top:24px;opacity:.7;font-size:12px">
            Kurulumu yeniden çalıştırmak için <code>storage/installed.lock</code> dosyasını silin.
        </p>
    </div>
</div>
<?php $view->endSection(); ?>
