<?php
/** @var \App\Core\View $view */
$view->extend('layouts.blank');
$view->section('content');
$error = flash('error');
$old = \App\Core\SessionManager::getFlash('_old', []);
?>
<div class="aho-admin-login">
    <div class="aho-admin-login__card">
        <div class="aho-admin-login__brand">
            <img src="<?= asset('img/logo-icon.png') ?>" alt="" width="48" height="48">
            <div>
                <h1>Ahost <b>Bilişim</b></h1>
                <p>Admin Paneli</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="aho-alert aho-alert--danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="/admin/giris" class="aho-form">
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
            <button type="submit" class="aho-btn aho-btn--primary aho-btn--lg aho-btn--block">Giriş Yap</button>
        </form>

        <div class="aho-admin-login__footer">
            <a href="/">← Ana sayfaya dön</a>
        </div>
    </div>
</div>
<?php $view->endSection(); ?>
