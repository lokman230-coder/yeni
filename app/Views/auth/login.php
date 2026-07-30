<h1>Müşteri Girişi</h1>
<p>Hizmetlerinizi, domainlerinizi, faturalarınızı ve destek taleplerinizi yönetmek için giriş yapın.</p>
<form method="post" action="<?= url('client/login') ?>" class="auth-login-form">
  <?= function_exists('csrf_field') ? csrf_field() : '' ?>
  <label>E-posta</label>
  <input type="email" name="email" placeholder="ornek@firma.com" autocomplete="email" required>
  <label>Şifre</label>
  <input type="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
  <button type="submit">Giriş Yap</button>
</form>
<?= function_exists('ao_social_login_buttons') ? ao_social_login_buttons('login') : '' ?>
