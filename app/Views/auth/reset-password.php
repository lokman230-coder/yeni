<h1>Yeni Şifre Belirle</h1>
<p>Şifre değiştirme linkleri tek kullanımlıktır ve 1 saat geçerlidir.</p>
<form method="post">
  <?= csrf_field() ?>
  <input type="hidden" name="token" value="<?= e($_GET['token'] ?? $_POST['token'] ?? '') ?>">
  <label>Yeni Şifre</label>
  <input type="password" name="password" required minlength="6">
  <button type="submit">Şifreyi Değiştir</button>
</form>
<div class="auth-links"><a href="<?= url('client/login') ?>">Giriş Yap</a>
  <a href="<?= url('') ?>">Siteye Dön</a></div>