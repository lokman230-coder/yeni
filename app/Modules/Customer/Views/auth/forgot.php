<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-customer-auth" style="padding:40px 0">
    <div class="aho-container" style="max-width:440px">
        <div class="aho-card" style="padding:36px">
            <h1 style="margin:0 0 6px;font-size:22px">🔑 Şifremi Unuttum</h1>
            <p style="color:var(--aho-color-ink-600);font-size:14px;margin:0 0 20px">
                E-posta adresinizi girin, size şifre sıfırlama bağlantısı gönderelim.
            </p>

            <?php if (!empty($sent)): ?>
                <div class="aho-alert aho-alert--success" style="margin-bottom:16px;background:#d1fae5;color:#065f46">
                    ✓ İşlem tamam. Hesabınız varsa, birazdan e-posta kutunuza bir sıfırlama bağlantısı düşecek.
                    <br><small style="opacity:.85">Güvenlik nedeniyle hesabın var olup olmadığını bildirmiyoruz.</small>
                </div>
                <div style="text-align:center;margin-top:12px">
                    <a href="/giris" style="font-weight:600">← Giriş sayfasına dön</a>
                </div>
            <?php else: ?>
                <form method="post" action="/sifremi-unuttum">
                    <?= csrf() ?>
                    <div style="margin-bottom:16px">
                        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px">E-posta</label>
                        <input type="email" name="email" required autocomplete="email"
                               style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:8px;box-sizing:border-box">
                    </div>
                    <button type="submit" class="aho-btn aho-btn--primary aho-btn--lg" style="width:100%">Sıfırlama Linki Gönder</button>
                </form>
                <div style="text-align:center;margin-top:16px;font-size:14px">
                    <a href="/giris" style="color:var(--aho-color-ink-600)">← Giriş yap</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
