<h1>Şifremi Unuttum</h1>
<p>Önce güvenlik sorusu cevabınızı deneyin. Cevabı hatırlamıyorsanız e-posta, SMS veya WhatsApp ile 1 saat geçerli şifre yenileme linki alabilirsiniz.</p>
<form method="post">
  <?= csrf_field() ?>
  <label>E-posta</label>
  <input type="email" name="email" placeholder="ornek@firma.com" required>
  <label>Güvenlik Sorusu Cevabı</label>
  <input name="security_answer" placeholder="Cevabı biliyorsanız yazın">
  <label>Link gönderim kanalı</label>
  <select name="channel"><option value="email">E-posta</option><option value="sms">SMS</option><option value="whatsapp">WhatsApp</option></select>
  <button type="submit">Şifre Yenileme Linki Gönder</button>
</form>
<div class="auth-links"><a href="<?= url('client/login') ?>">Giriş Yap</a>
  <a href="<?= url('client/register') ?>">Kayıt Ol</a>
  <a href="<?= url('') ?>">Siteye Dön</a></div>