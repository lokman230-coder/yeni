<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
$errors = $errors ?? [];
?>
<section class="aho-customer-auth" style="padding:40px 0">
    <div class="aho-container" style="max-width:440px">
        <div class="aho-card" style="padding:36px">
            <h1 style="margin:0 0 6px;font-size:22px">🔐 Yeni Şifre Belirle</h1>

            <?php if (!$valid): ?>
                <div class="aho-alert aho-alert--danger" style="margin:16px 0">
                    ⚠️ Bu bağlantı geçersiz veya süresi dolmuş.
                </div>
                <div style="text-align:center">
                    <a href="/sifremi-unuttum" class="aho-btn aho-btn--primary">Yeni Link İste</a>
                </div>
            <?php else: ?>
                <p style="color:var(--aho-color-ink-600);font-size:14px;margin:0 0 20px">
                    En az 8 karakterli yeni şifrenizi belirleyin.
                </p>
                <?php if (!empty($errors['general'])): ?>
                    <div class="aho-alert aho-alert--danger" style="margin-bottom:16px"><?= e($errors['general']) ?></div>
                <?php endif; ?>
                <form method="post" action="/sifre-sifirla">
                    <?= csrf() ?>
                    <input type="hidden" name="token" value="<?= e($token) ?>">
                    <div style="margin-bottom:14px">
                        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px">Yeni Şifre</label>
                        <input type="password" name="password" required minlength="8" autocomplete="new-password"
                               style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:8px;box-sizing:border-box">
                    </div>
                    <div style="margin-bottom:20px">
                        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px">Şifre Tekrar</label>
                        <input type="password" name="password_confirm" required minlength="8" autocomplete="new-password"
                               style="width:100%;padding:10px;border:1px solid <?= !empty($errors['password_confirm']) ? '#dc2626' : 'var(--aho-color-border)' ?>;border-radius:8px;box-sizing:border-box">
                        <?php if (!empty($errors['password_confirm'])): ?>
                            <div style="color:#dc2626;font-size:12px;margin-top:2px"><?= e($errors['password_confirm']) ?></div>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="aho-btn aho-btn--primary aho-btn--lg" style="width:100%">Şifreyi Güncelle</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
