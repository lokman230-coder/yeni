<h1>Admin Şifremi Unuttum</h1>
<p>Admin e-posta adresinizi yazın. Hesap bulunursa güvenlik sorusu ekranı açılır.</p>
<form method="post" action="<?= url('admin/forgot-password') ?>">
  <?= csrf_field() ?>
  <label>Admin E-posta</label>
  <input type="email" name="email" required placeholder="admin@site.com">
  <button type="submit">Güvenlik Sorusuna Geç</button>
</form>
<div class="auth-links"><a href="<?= url('admin/login') ?>">Admin Girişine Dön</a></div>
