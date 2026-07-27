<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-customer-auth" style="padding:40px 0">
    <div class="aho-container" style="max-width:420px">
        <div class="aho-card" style="padding:36px;text-align:center">
            <div style="font-size:48px">🔐</div>
            <h1 style="margin:12px 0 6px;font-size:22px">İki Faktörlü Doğrulama</h1>
            <p style="color:var(--aho-color-ink-600);font-size:14px;margin:0 0 24px">
                Authenticator uygulamanızdaki 6 haneli kodu girin.
            </p>

            <?php if (!empty($error)): ?>
                <div class="aho-alert aho-alert--danger" style="margin-bottom:16px"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="/giris/2fa">
                <?= csrf() ?>
                <input type="text" name="code" required autocomplete="one-time-code"
                       inputmode="numeric" pattern="[0-9\-]{6,10}" maxlength="10"
                       placeholder="123456"
                       autofocus
                       style="width:100%;padding:14px;border:2px solid var(--aho-color-border);border-radius:8px;font-size:22px;text-align:center;letter-spacing:8px;font-family:monospace;box-sizing:border-box;margin-bottom:16px">

                <button type="submit" class="aho-btn aho-btn--primary aho-btn--lg" style="width:100%">Doğrula</button>
            </form>

            <div style="margin-top:20px;font-size:13px;color:var(--aho-color-ink-500)">
                Kodu alamıyor musunuz? Kurtarma kodlarınızdan birini de kullanabilirsiniz (örn: <code>AWZ8-DPDB</code>).
            </div>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
