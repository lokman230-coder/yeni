<?php
$selectedProduct=null; $selectedSlug=trim((string)($_GET['product']??$_POST['product_slug']??''));
if($selectedSlug!==''){ try{$selectedProduct=ao_v2335_product_by_slug($selectedSlug);}catch(Throwable $e){} }
?>
<h1><?= $selectedProduct?'Satın Almaya Başlayın':'Kayıt Ol' ?></h1>
<p><?= $selectedProduct ? e($selectedProduct['name']).' için müşteri hesabınızı oluşturun. Ürün seçiminiz korunacaktır.' : 'Ahost One müşteri hesabınızı oluşturun.' ?></p>
<?php if($selectedProduct): ?><div class="auth-selected-product"><strong><?= e($selectedProduct['name']) ?></strong><span><?= e($selectedProduct['group_name']??'Ürün') ?></span><a href="<?= url('urun/'.$selectedProduct['slug']) ?>">Ürünü incele</a></div><?php endif; ?>
<?= function_exists('ao_social_login_buttons') ? ao_social_login_buttons('register') : '' ?>
<form method="post" class="auth-grid">
  <?= csrf_field() ?>
  <?php if($selectedProduct): ?><input type="hidden" name="product_slug" value="<?= e($selectedProduct['slug']) ?>"><?php endif; ?>
  <div><label>Ad</label><input name="first_name" required></div>
  <div><label>Soyad</label><input name="last_name" required></div>
  <div class="full"><label>E-posta</label><input type="email" name="email" required></div>
  <div class="full"><label>Telefon</label><input name="phone" required></div>
  <div><label>TC Kimlik No</label><input name="tc_identity_no" maxlength="11" pattern="[0-9]{11}" required></div>
  <div><label>Doğum Tarihi</label><input type="date" name="birth_date" required></div>
  <div class="full"><small>Ön yüz kayıtlarında TC + ad + soyad + doğum yılı doğrulaması zorunludur. Doğrulama başarısızsa kayıt oluşturulmaz.</small></div>
  <div class="full"><label>Şifre</label><input type="password" name="password" minlength="6" required></div>
  <div class="full"><button type="submit">Hesap Oluştur</button></div>
</form>
<div class="auth-links"><a href="<?= url('client/login') ?>">Giriş Yap</a>
  <a href="<?= url('client/forgot-password') ?>">Şifremi Unuttum</a>
  <a href="<?= url('') ?>">Siteye Dön</a></div>
