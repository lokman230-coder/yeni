<?php
// v24.1.2 MFA verification and resend routes.
if ($route === 'auth/mfa') {
    auth_view('mfa-verify', ['pageTitle'=>'Giriş Doğrulama']);
    exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route === 'auth/mfa/verify') {
    verify_csrf();
    $res=ao_mfa_verify_pending(trim($_POST['code'] ?? ''));
    if(!empty($res['ok'])) { flash('success','Giriş doğrulandı.'); redirect_to($res['redirect'] ?? ''); }
    flash('error',$res['message'] ?? 'Doğrulama başarısız.'); redirect_to('auth/mfa');
}
if ($route === 'auth/mfa/resend') {
    verify_csrf();
    $p=$_SESSION['mfa_pending'] ?? null;
    if($p && in_array($p['method'], ['mail','sms'], true)) {
        ao_mfa_generate_otp($p['user_type'], (int)$p['user_id'], $p['method'], $p['method']==='mail'?($p['email']??''):($p['phone']??''));
        flash('success','Yeni doğrulama kodu gönderildi.');
    } else { flash('error','Bu yöntem için yeniden kod gönderilemez.'); }
    redirect_to('auth/mfa');
}
if ($route === 'auth/mfa/cancel') {
    unset($_SESSION['mfa_pending']);
    flash('success','Doğrulama iptal edildi.');
    redirect_to('client/login');
}
