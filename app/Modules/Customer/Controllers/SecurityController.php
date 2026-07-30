<?php

declare(strict_types=1);

namespace App\Modules\Customer\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Core\Database\Connection;
use App\Services\Auth\AuthService;
use App\Services\Auth\PasswordHasher;
use App\Services\Auth\TwoFactorService;

/**
 * Customer > Güvenlik ekranı — 2FA kur/kaldır, kurtarma kodlarını gör.
 */
final class SecurityController
{
    public function index(Request $request): Response
    {
        $customer = AuthService::customer();
        $enabled = TwoFactorService::isEnabled('customer', (int) $customer['id']);
        $view = new View();
        return Response::html($view->render('customer::security', [
            'title'       => 'Güvenlik',
            'customer'    => $customer,
            'twofa_enabled' => $enabled,
            'success'     => flash('success'),
            'error'       => flash('error'),
            'setup'       => flash('setup'),
            'recovery'    => flash('recovery'),
        ]));
    }

    /** 2FA setup başlat — secret üret, QR göster, kod iste */
    public function setupStart(Request $request): Response
    {
        $customer = AuthService::customer();
        $secret = TwoFactorService::generateSecret();
        TwoFactorService::saveSecret('customer', (int) $customer['id'], $secret);
        $qrSvg = TwoFactorService::qrCodeSvg((string) env('APP_NAME', 'Ahost Bilişim'), (string) $customer['email'], $secret);

        SessionManager::flash('setup', [
            'secret' => $secret,
            'qr_svg' => $qrSvg,
        ]);
        return Response::redirect('/panel/guvenlik');
    }

    /** Kullanıcı 6 haneli kodu girdi — doğrula ve aktif et */
    public function setupConfirm(Request $request): Response
    {
        $customer = AuthService::customer();
        $code = trim((string) $request->input('code', ''));
        $recovery = TwoFactorService::confirm('customer', (int) $customer['id'], $code);
        if (!$recovery) {
            SessionManager::flash('error', 'Kod doğrulanamadı. QR\'ı tekrar okutup yeni kod deneyin.');
            return Response::redirect('/panel/guvenlik');
        }
        SessionManager::flash('success', '✓ 2FA aktif! Kurtarma kodlarınızı güvenli bir yere kaydedin — tekrar gösterilmez.');
        SessionManager::flash('recovery', $recovery);
        return Response::redirect('/panel/guvenlik');
    }

    public function disable(Request $request): Response
    {
        $customer = AuthService::customer();
        TwoFactorService::disable('customer', (int) $customer['id']);
        SessionManager::flash('success', '2FA devre dışı bırakıldı.');
        return Response::redirect('/panel/guvenlik');
    }

    /** Şifre değiştirme (mevcut şifre ile doğrulama zorunlu) */
    public function changePassword(Request $request): Response
    {
        $customer = AuthService::customer();
        $current = (string) $request->input('current_password', '');
        $new     = (string) $request->input('new_password', '');
        $confirm = (string) $request->input('new_password_confirm', '');

        $row = Connection::selectOne("SELECT password_hash FROM customers WHERE id = ?", [$customer['id']]);
        if (!$row || !PasswordHasher::verify($current, $row['password_hash'])) {
            SessionManager::flash('error', 'Mevcut şifreniz yanlış.');
            return Response::redirect('/panel/guvenlik');
        }
        $check = \App\Services\Auth\PasswordPolicy::validate($new);
        if (!$check['ok']) {
            SessionManager::flash('error', implode(' ', $check['errors']));
            return Response::redirect('/panel/guvenlik');
        }
        if ($new !== $confirm) {
            SessionManager::flash('error', 'Yeni şifreler eşleşmiyor.');
            return Response::redirect('/panel/guvenlik');
        }
        Connection::update('customers', ['password_hash' => PasswordHasher::hash($new)], 'id = ?', [$customer['id']]);
        SessionManager::flash('success', '✓ Şifreniz güncellendi.');
        return Response::redirect('/panel/guvenlik');
    }
}
