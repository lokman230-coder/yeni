<?php
// Admin authentication route handlers.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/login') {
    verify_csrf();
    $email = trim($_POST['email'] ?? ''); $pass = $_POST['password'] ?? '';
    ao_mfa_ensure_schema();
    try { $s=db()->prepare('SELECT * FROM admins WHERE email=? LIMIT 1'); $s->execute([$email]); $a=$s->fetch(); }
    catch(Throwable $e) { $a=null; }
    if ($a && password_verify($pass, $a['password_hash'])) {
        ao_mfa_start_challenge('admin', $a, 'admin');
    }
    ao_mfa_log('admin', null, $email, 'login', 'password', 'failed', 'Admin e-posta veya şifre hatalı.');
    flash('error','Admin e-posta veya şifre hatalı.'); redirect_to('admin/login');
}
if ($route === 'admin/logout') { if(function_exists('ao_session_clear_user')) ao_session_clear_user('admin'); unset($_SESSION['mfa_pending']); flash('success','Admin çıkışı yapıldı.'); redirect_to('admin/login'); }



