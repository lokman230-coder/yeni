<?php
/** @var \App\Core\View $view */
$view->extend('layouts.blank');
$view->section('content');
?>
<div style="min-height:100vh;background:linear-gradient(135deg,#0ea5e9 0%,#8b5cf6 100%);display:flex;align-items:center;justify-content:center;padding:20px;font-family:system-ui,sans-serif">
    <div style="background:#fff;border-radius:16px;padding:36px;max-width:400px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.25);text-align:center">
        <div style="font-size:48px">🔐</div>
        <h1 style="margin:12px 0 6px;font-size:22px">Yönetici 2FA</h1>
        <p style="color:#6b7280;font-size:14px;margin:0 0 24px">Authenticator kodunu girin.</p>

        <?php if (!empty($error)): ?>
            <div style="padding:10px;background:#fee2e2;color:#991b1b;border-radius:8px;margin-bottom:16px;font-size:14px"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="/admin/2fa">
            <?= csrf() ?>
            <input type="text" name="code" required autocomplete="one-time-code"
                   inputmode="numeric" pattern="[0-9\-A-Za-z]{6,10}" maxlength="10"
                   placeholder="123456"
                   autofocus
                   style="width:100%;padding:14px;border:2px solid #e5e7eb;border-radius:8px;font-size:22px;text-align:center;letter-spacing:8px;font-family:monospace;box-sizing:border-box;margin-bottom:16px">
            <button type="submit" style="width:100%;padding:14px;background:#0ea5e9;color:#fff;border:0;border-radius:8px;font-weight:600;font-size:15px;cursor:pointer">Doğrula & Giriş Yap</button>
        </form>

        <div style="margin-top:20px;font-size:12px;color:#6b7280">
            Kurtarma kodu da kullanabilirsiniz (XXXX-XXXX)
        </div>
        <div style="margin-top:16px">
            <a href="/admin/giris" style="color:#6b7280;font-size:13px;text-decoration:none">← Farklı hesapla giriş</a>
        </div>
    </div>
</div>
<?php $view->endSection(); ?>
