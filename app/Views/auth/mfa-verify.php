<?php $flash=get_flash(); $pending=$_SESSION['mfa_pending'] ?? null; if(!$pending){ flash('error','Bekleyen doğrulama bulunamadı.'); redirect_to('client/login'); } $method=$pending['method'] ?? 'mail'; $label=['mail'=>'Mail OTP','sms'=>'SMS OTP','totp'=>'Google Authenticator'][$method] ?? strtoupper($method); $secret=''; $otpauth=''; if($method==='totp'){ $secret=ao_mfa_get_totp_secret($pending['user_type'], (int)$pending['user_id'], true); $issuer=urlencode(admin_setting('site_name','Ahost One')); $account=urlencode(($pending['user_type']==='admin'?'Admin':'Müşteri').':'.($pending['email']??$pending['user_id'])); $otpauth='otpauth://totp/'.$issuer.':'.$account.'?secret='.$secret.'&issuer='.$issuer; } ?>
<h1>Giriş Doğrulama</h1>
<p class="muted">Hesabınız için ek güvenlik doğrulaması gerekiyor.</p>
<?php if($flash): ?><div class="ao-alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
<div class="mfa-method-box"><b><?= e($label) ?></b><?php if($method==='mail'): ?><span><?= e($pending['email'] ?? '') ?> adresine kod gönderildi.</span><?php endif; ?><?php if($method==='sms'): ?><span><?= e($pending['phone'] ?? '') ?> numarasına kod gönderildi.</span><?php endif; ?></div>
<?php if($method==='totp'): ?>
  <div class="mfa-totp-setup">
    <p>Google Authenticator, Microsoft Authenticator veya benzeri uygulamaya aşağıdaki gizli anahtarı ekleyin.</p>
    <code><?= e($secret) ?></code>
    <small>URI: <?= e($otpauth) ?></small>
  </div>
<?php endif; ?>
<form method="post" action="<?= url('auth/mfa/verify') ?>" class="auth-form">
  <?= csrf_field() ?>
  <label>6 Haneli Kod<input name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" autofocus></label>
  <button class="auth-btn">Doğrula ve Giriş Yap</button>
</form>
<div class="mfa-actions">
  <?php if($method==='mail' || $method==='sms'): ?><a href="<?= url('auth/mfa/resend?csrf_token='.urlencode(csrf_token())) ?>">Kodu tekrar gönder</a><?php endif; ?>
  <a href="<?= url('auth/mfa/cancel') ?>">İptal et</a>
</div>
