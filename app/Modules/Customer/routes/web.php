<?php

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Router;
use App\Core\SessionManager;
use App\Core\View;
use App\Services\Auth\AuthService;

/** @var Router $router */

// Public: giriş & kayıt
$router->group(['middleware' => ['locale']], function (Router $router) {
    $router->get('/giris', function () {
        if (AuthService::isCustomer()) return Response::redirect('/panel');
        $view = new View();
        return Response::html($view->render('customer::auth.login', ['title' => 'Giriş']));
    })->name('customer.login');

    $router->post('/giris', function (Request $r) {
        $email = trim((string)$r->input('email', ''));
        $password = (string)$r->input('password', '');

        if ($email === '' || $password === '') {
            SessionManager::flash('error', 'E-posta ve şifre zorunludur.');
            return Response::redirect('/giris');
        }
        $result = AuthService::attemptCustomer($email, $password);
        if ($result === 'fail') {
            SessionManager::flash('error', 'E-posta veya şifre hatalı.');
            SessionManager::flash('_old', ['email' => $email]);
            return Response::redirect('/giris');
        }
        if ($result === '2fa') {
            return Response::redirect('/giris/2fa');
        }
        return Response::redirect('/panel');
    })->middleware(['csrf', 'ratelimit']);

    // Customer 2FA
    $router->get('/giris/2fa', function () {
        if (!SessionManager::get('pending_2fa_customer_id')) return Response::redirect('/giris');
        $view = new View();
        return Response::html($view->render('customer::auth.twofactor', [
            'title' => 'İki Faktörlü Doğrulama',
            'error' => flash('error'),
        ]));
    });
    $router->post('/giris/2fa', function (Request $r) {
        $customerId = (int) SessionManager::get('pending_2fa_customer_id', 0);
        if ($customerId === 0) return Response::redirect('/giris');
        $code = trim((string) $r->input('code', ''));
        if ($code === '' || !\App\Services\Auth\TwoFactorService::verify('customer', $customerId, $code)) {
            SessionManager::flash('error', 'Kod doğrulanamadı.');
            return Response::redirect('/giris/2fa');
        }
        AuthService::completeTwoFactorCustomer($customerId);
        return Response::redirect('/panel');
    })->middleware(['csrf', 'ratelimit']);

    // ---- SMS/OTP ile giriş — Rapor 6.1 ----
    $router->get('/giris/sms', function () {
        if (AuthService::isCustomer()) return Response::redirect('/panel');
        if (!\App\Services\Settings\SettingsManager::get('sms.otp_enabled', '0')) {
            SessionManager::flash('error', 'SMS ile giriş şu an devre dışı.');
            return Response::redirect('/giris');
        }
        return Response::html((new View())->render('customer::auth.otp_request', ['title' => 'SMS ile Giriş']));
    });

    $router->post('/giris/sms/kod-gonder', function (Request $r) {
        $phone = (string) $r->input('phone', '');
        if ($phone === '') {
            SessionManager::flash('error', 'Telefon numarası boş olamaz.');
            return Response::redirect('/giris/sms');
        }
        $result = \App\Services\Auth\OtpService::issue($phone, 'login', 'sms');
        if (!$result['ok']) {
            SessionManager::flash('error', $result['error'] ?? 'Kod gönderilemedi.');
            return Response::redirect('/giris/sms');
        }
        SessionManager::set('otp_phone', $phone);
        if (!empty($result['dev_code'])) {
            SessionManager::flash('info', 'DEV kodu: ' . $result['dev_code']);
        } else {
            SessionManager::flash('success', 'Kod telefonunuza gönderildi.');
        }
        return Response::redirect('/giris/sms/kod-dogrula');
    })->middleware(['csrf', 'ratelimit']);

    $router->get('/giris/sms/kod-dogrula', function () {
        if (!SessionManager::get('otp_phone')) return Response::redirect('/giris/sms');
        return Response::html((new View())->render('customer::auth.otp_verify', [
            'title' => 'Kod Doğrula',
            'phone' => (string) SessionManager::get('otp_phone', ''),
        ]));
    });

    $router->post('/giris/sms/kod-dogrula', function (Request $r) {
        $phone = (string) SessionManager::get('otp_phone', '');
        $code  = (string) $r->input('code', '');
        if ($phone === '') return Response::redirect('/giris/sms');

        if (!\App\Services\Auth\OtpService::verify($phone, $code, 'login')) {
            SessionManager::flash('error', 'Kod hatalı veya süresi doldu.');
            return Response::redirect('/giris/sms/kod-dogrula');
        }
        $login = AuthService::loginCustomerByPhone($phone);
        if (!$login['ok']) {
            SessionManager::flash('error', $login['error'] ?? 'Giriş başarısız.');
            return Response::redirect('/giris/sms');
        }
        SessionManager::remove('otp_phone');
        return Response::redirect('/panel');
    })->middleware(['csrf', 'ratelimit']);

    $router->get('/kayit', function () {
        if (AuthService::isCustomer()) return Response::redirect('/panel');
        $view = new View();
        return Response::html($view->render('customer::auth.register', [
            'title'  => 'Kayıt Ol',
            'errors' => SessionManager::getFlash('_form_errors', []),
            'old'    => SessionManager::getFlash('_form_old', []),
        ]));
    })->name('customer.register');

    $router->post('/kayit', function (Request $r) {
        if (AuthService::isCustomer()) return Response::redirect('/panel');

        $data = [
            'email'            => (string) $r->input('email', ''),
            'password'         => (string) $r->input('password', ''),
            'password_confirm' => (string) $r->input('password_confirm', ''),
            'first_name'       => (string) $r->input('first_name', ''),
            'last_name'        => (string) $r->input('last_name',  ''),
            'phone'            => (string) $r->input('phone',   ''),
            'company'          => (string) $r->input('company', ''),
            'kvkk'             => $r->input('kvkk') ? 1 : 0,
        ];

        $result = AuthService::registerCustomer($data);
        if (!$result['ok']) {
            SessionManager::flash('_form_errors', $result['errors'] ?? ['general' => $result['error'] ?? 'Bilinmeyen hata']);
            SessionManager::flash('_form_old', array_intersect_key($data, array_flip(['email','first_name','last_name','phone','company'])));
            SessionManager::flash('error', 'Kayıt başarısız. Formu kontrol edin.');
            return Response::redirect('/kayit');
        }

        SessionManager::flash('success', 'Hoş geldiniz! Hesabınız oluşturuldu.');
        $after = SessionManager::get('after_login_redirect');
        if ($after) {
            SessionManager::remove('after_login_redirect');
            return Response::redirect((string) $after);
        }
        return Response::redirect('/panel');
    })->middleware(['csrf', 'ratelimit']);

    $router->post('/cikis', function () {
        AuthService::logoutCustomer();
        return Response::redirect('/');
    })->middleware(['csrf'])->name('customer.logout');

    // E-posta doğrulama
    $router->get('/email-dogrula', function (Request $r) {
        $token = (string) $r->query('token', '');
        $result = \App\Services\Auth\EmailVerificationService::verify($token);
        $view = new View();
        return Response::html($view->render('customer::auth.email_verified', [
            'title'  => 'E-posta Doğrulama',
            'ok'     => $result['ok'],
            'message'=> $result['message'] ?? null,
        ]));
    });

    // Doğrulama linkini yeniden gönder (login gerektirir)
    $router->post('/email-dogrula/tekrar-gonder', function () {
        if (!AuthService::isCustomer()) return Response::redirect('/giris');
        $c = AuthService::customer();
        $ok = \App\Services\Auth\EmailVerificationService::resend('customer', (int) $c['id']);
        SessionManager::flash($ok ? 'success' : 'info', $ok
            ? '✓ Doğrulama linki tekrar gönderildi. Lütfen e-posta kutunuzu kontrol edin.'
            : 'E-postanız zaten doğrulanmış görünüyor.'
        );
        return Response::redirect('/panel');
    })->middleware(['csrf', 'ratelimit']);

    // Şifremi unuttum
    $router->get('/sifremi-unuttum', function () {
        $view = new View();
        return Response::html($view->render('customer::auth.forgot', [
            'title' => 'Şifremi Unuttum',
            'sent'  => (bool) SessionManager::getFlash('_pwreset_sent'),
        ]));
    })->name('customer.forgot');

    $router->post('/sifremi-unuttum', function (Request $r) {
        $email = trim((string) $r->input('email', ''));
        \App\Services\Auth\PasswordResetService::request($email, 'customer');
        SessionManager::flash('_pwreset_sent', true);
        return Response::redirect('/sifremi-unuttum');
    })->middleware(['csrf', 'ratelimit']);

    $router->get('/sifre-sifirla', function (Request $r) {
        $token = (string) $r->query('token', '');
        $t = \App\Services\Auth\PasswordResetService::validate($token);
        $view = new View();
        return Response::html($view->render('customer::auth.reset', [
            'title'  => 'Yeni Şifre Belirle',
            'token'  => $token,
            'valid'  => (bool) $t,
            'errors' => SessionManager::getFlash('_form_errors', []),
        ]));
    })->name('customer.reset');

    $router->post('/sifre-sifirla', function (Request $r) {
        $token = (string) $r->input('token', '');
        $pwd = (string) $r->input('password', '');
        $confirm = (string) $r->input('password_confirm', '');
        if ($pwd !== $confirm) {
            SessionManager::flash('_form_errors', ['password_confirm' => 'Şifreler eşleşmiyor.']);
            return Response::redirect('/sifre-sifirla?token=' . urlencode($token));
        }
        $r2 = \App\Services\Auth\PasswordResetService::reset($token, $pwd);
        if (!$r2['ok']) {
            SessionManager::flash('_form_errors', ['general' => $r2['error']]);
            return Response::redirect('/sifre-sifirla?token=' . urlencode($token));
        }
        SessionManager::flash('success', '✓ Şifreniz güncellendi. Yeni şifrenizle giriş yapabilirsiniz.');
        return Response::redirect('/giris');
    })->middleware(['csrf', 'ratelimit']);
});

// Korumalı: müşteri paneli
$router->group(['prefix' => 'panel', 'middleware' => ['locale', 'customer.auth']], function (Router $router) {
    $pc = \App\Modules\Customer\Controllers\PanelController::class;

    $router->get('/',                [$pc, 'dashboard'])->name('customer.dashboard');
    $router->get('',                 [$pc, 'dashboard']);
    $router->get('/hizmetlerim',     [$pc, 'services'])->name('customer.services');
    $router->get('/faturalarim',     [$pc, 'invoices'])->name('customer.invoices');
    $router->get('/domainlerim',     [$pc, 'domains'])->name('customer.domains');
    $router->get('/bakiye',                         [$pc, 'credit'])->name('customer.credit');
    $router->post('/bakiye/yukle',                  [$pc, 'creditTopUp'])->middleware(['csrf']);
    $router->get('/odemelerim',                     [$pc, 'payments'])->name('customer.payments');
    $router->get('/kartlar',                        [$pc, 'cards'])->name('customer.cards');
    $router->post('/kartlar/{id}/otomatik-tahsilat', [$pc, 'toggleAutoBilling'])->middleware(['csrf']);
    $router->post('/kartlar/{id}/varsayilan',        [$pc, 'setDefaultCard'])->middleware(['csrf']);
    $router->post('/kartlar/{id}/sil',                [$pc, 'deleteCard'])->middleware(['csrf']);

    $router->get('/domain/{id}',                    [$pc, 'domainDetail'])->name('customer.domain.detail');
    $router->post('/domain/{id}/nameserver',        [$pc, 'updateNameservers'])->middleware(['csrf']);
    $router->post('/domain/{id}/auto-renew',        [$pc, 'toggleAutoRenew'])->middleware(['csrf']);
    $router->post('/domain/{id}/transfer-lock',     [$pc, 'toggleTransferLock'])->middleware(['csrf']);
    $router->post('/domain/{id}/whois-privacy',     [$pc, 'toggleWhoisPrivacy'])->middleware(['csrf']);
    $router->post('/domain/{id}/epp-al',            [$pc, 'requestEpp'])->middleware(['csrf']);
    $router->post('/domain/{id}/yenile',            [$pc, 'renewDomain'])->middleware(['csrf']);
    $router->get('/domain/{id}/belgeler',            [$pc, 'domainDocuments'])->name('customer.domain.docs');
    $router->post('/domain/{id}/belge-yukle',        [$pc, 'uploadDomainDocument'])->middleware(['csrf']);

    // Backorder
    $router->get('/backorder',                       [$pc, 'backorderList'])->name('customer.backorder');
    $router->post('/backorder/ekle',                 [$pc, 'backorderAdd'])->middleware(['csrf']);
    $router->post('/backorder/{id}/iptal',           [$pc, 'backorderCancel'])->middleware(['csrf']);

    // Vendor (Marketplace satıcısı)
    $router->get('/satici',                          [$pc, 'vendorPanel'])->name('customer.vendor');
    $router->post('/satici/basvur',                  [$pc, 'vendorApply'])->middleware(['csrf']);
    $router->post('/satici/payout-iste',             [$pc, 'vendorPayoutRequest'])->middleware(['csrf']);
    $router->get('/siparislerim',    [$pc, 'orders'])->name('customer.orders');
    $router->get('/hizmet/{id}',     [$pc, 'serviceDetail'])->name('customer.service.detail');
    $router->post('/hizmet/{id}/sifre-goster', [$pc, 'revealPassword'])->middleware(['csrf']);

    // Güvenlik (2FA)
    $sc = \App\Modules\Customer\Controllers\SecurityController::class;
    $router->get('/guvenlik',                [$sc, 'index'])->name('customer.security');
    $router->post('/guvenlik/2fa-baslat',    [$sc, 'setupStart'])->middleware(['csrf']);
    $router->post('/guvenlik/2fa-onayla',    [$sc, 'setupConfirm'])->middleware(['csrf']);
    $router->post('/guvenlik/2fa-kapat',     [$sc, 'disable'])->middleware(['csrf']);
    $router->post('/guvenlik/sifre-degistir', [$sc, 'changePassword'])->middleware(['csrf']);
});
