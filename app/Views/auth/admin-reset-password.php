<?php if (empty($_SESSION['admin_reset_verified'])) { redirect_to('admin/forgot-password'); } ?>
<h1>Yeni Admin Şifresi</h1>
<p>Güvenlik sorusu doğrulandı. Yeni şifrenizi belirleyin.</p>
<form method="post" action="<?= url('admin/reset-password') ?>">
  <?= csrf_field() ?>
  <label>Yeni Şifre</label>
  <input type="password" name="password" required minlength="8" placeholder="En az 8 karakter">
  <label>Yeni Şifre Tekrar</label>
  <input type="password" name="password_confirm" required minlength="8">
  <button type="submit">Şifreyi Değiştir</button>
</form>
<div class="auth-links"><a href="<?= url('admin/login') ?>">Admin Girişine Dön</a></div>
