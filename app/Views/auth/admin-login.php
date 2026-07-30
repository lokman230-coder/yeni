<h1>Admin Girişi</h1>
<p>Ahost One yönetim paneline güvenli oturumla giriş yapın.</p>
<form method="post" action="<?= url('admin/login') ?>">
  <?= csrf_field() ?>
  <label>E-posta</label>
  <input type="email" name="email" required placeholder="admin@site.com">
  <label>Şifre</label>
  <input type="password" name="password" required placeholder="••••••••">
  <button type="submit">Admin Paneline Gir</button>
</form>
<div class="auth-links">
  <a href="<?= url('admin/forgot-password') ?>">Şifremi Unuttum</a>
  <a href="<?= url('') ?>">Siteye Dön</a>
  <a href="<?= url('client/login') ?>">Müşteri Paneline Git</a>
</div>
