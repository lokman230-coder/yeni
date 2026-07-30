<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
$error = flash('error');
$old = \App\Core\SessionManager::getFlash('_old', []);
?>
<section class="aho-customer-auth">
    <div class="aho-container">
        <div class="aho-customer-auth__card">
            <h1 class="aho-customer-auth__title">Müşteri Girişi</h1>
            <p class="aho-customer-auth__subtitle">Hesabınıza erişmek için giriş yapın.</p>

            <?php if ($error): ?>
                <div class="aho-alert aho-alert--danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="/giris">
                <?= csrf() ?>
                <div class="aho-form-group">
                    <label class="aho-form-label aho-form-label--required" for="email">E-posta</label>
                    <input type="email" id="email" name="email" class="aho-form-input"
                           value="<?= e($old['email'] ?? '') ?>" required autofocus autocomplete="email">
                </div>
                <div class="aho-form-group">
                    <label class="aho-form-label aho-form-label--required" for="password">Şifre</label>
                    <input type="password" id="password" name="password" class="aho-form-input" required autocomplete="current-password">
                </div>
                <div class="aho-customer-auth__forgot">
                    <a href="/sifremi-unuttum">Şifremi unuttum</a>
                </div>
                <button type="submit" class="aho-btn aho-btn--primary aho-btn--block aho-btn--lg">Giriş Yap</button>
            </form>

            <?php if (\App\Services\Settings\SettingsManager::get('sms.otp_enabled', '0')): ?>
                <div style="margin-top:16px;text-align:center">
                    <a href="/giris/sms" class="aho-btn aho-btn--outline aho-btn--block">📱 SMS ile Giriş</a>
                </div>
            <?php endif; ?>

            <div class="aho-customer-auth__footer">
                Hesabınız yok mu? <a href="/kayit">Kayıt olun</a>
            </div>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
