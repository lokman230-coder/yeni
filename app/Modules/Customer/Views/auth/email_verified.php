<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-customer-auth" style="padding:60px 0">
    <div class="aho-container" style="max-width:520px">
        <div class="aho-card" style="padding:40px;text-align:center">
            <?php if ($ok): ?>
                <div style="font-size:72px">✅</div>
                <h1 style="margin:16px 0 8px;font-size:24px;color:#059669">E-postanız Doğrulandı</h1>
                <p style="color:var(--aho-color-ink-600);margin:0 0 24px">
                    Teşekkürler! Hesabınız tam olarak aktif hale geldi.
                </p>
                <a href="/panel" class="aho-btn aho-btn--primary aho-btn--lg">Panele Git →</a>
            <?php else: ?>
                <div style="font-size:72px">⚠️</div>
                <h1 style="margin:16px 0 8px;font-size:22px;color:#dc2626">Doğrulama Başarısız</h1>
                <p style="color:var(--aho-color-ink-600);margin:0 0 24px">
                    <?= e($message ?? 'Bağlantı geçersiz veya süresi dolmuş olabilir.') ?>
                </p>
                <div style="display:flex;gap:12px;justify-content:center">
                    <a href="/giris" class="aho-btn aho-btn--ghost">Giriş Yap</a>
                    <a href="/kayit" class="aho-btn aho-btn--primary">Yeniden Kayıt</a>
                </div>
                <div style="margin-top:16px;font-size:13px;color:var(--aho-color-ink-500)">
                    Hesabınız varsa, panele girip "Doğrulama linkini yeniden gönder" butonunu kullanabilirsiniz.
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
