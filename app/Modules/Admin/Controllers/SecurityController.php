<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Services\Auth\AuthService;
use App\Services\Auth\TwoFactorService;

/**
 * Admin > Güvenlik ekranı — 2FA kur/kaldır.
 * URL: /admin/guvenlik
 */
final class SecurityController
{
    public function index(Request $request): Response
    {
        $admin = AuthService::admin();
        $enabled = TwoFactorService::isEnabled('admin', (int) $admin['id']);
        $view = new View();
        return Response::html($view->render('admin::security.index', [
            'title'         => 'Güvenlik',
            'admin'         => $admin,
            'twofa_enabled' => $enabled,
            'success'       => flash('success'),
            'error'         => flash('error'),
            'setup'         => flash('setup'),
            'recovery'      => flash('recovery'),
        ]));
    }

    public function setupStart(Request $request): Response
    {
        $admin = AuthService::admin();
        $secret = TwoFactorService::generateSecret();
        TwoFactorService::saveSecret('admin', (int) $admin['id'], $secret);
        $qrSvg = TwoFactorService::qrCodeSvg(
            (string) env('APP_NAME', 'Ahost Bilişim') . ' Admin',
            (string) $admin['email'],
            $secret
        );
        SessionManager::flash('setup', ['secret' => $secret, 'qr_svg' => $qrSvg]);
        return Response::redirect('/admin/guvenlik');
    }

    public function setupConfirm(Request $request): Response
    {
        $admin = AuthService::admin();
        $code = trim((string) $request->input('code', ''));
        $recovery = TwoFactorService::confirm('admin', (int) $admin['id'], $code);
        if (!$recovery) {
            SessionManager::flash('error', 'Kod doğrulanamadı. Yeni kod deneyin.');
            return Response::redirect('/admin/guvenlik');
        }
        SessionManager::flash('success', '✓ 2FA aktif! Kurtarma kodlarını güvenli bir yere kaydet.');
        SessionManager::flash('recovery', $recovery);
        return Response::redirect('/admin/guvenlik');
    }

    public function disable(Request $request): Response
    {
        $admin = AuthService::admin();
        TwoFactorService::disable('admin', (int) $admin['id']);
        SessionManager::flash('success', '2FA devre dışı bırakıldı.');
        return Response::redirect('/admin/guvenlik');
    }
}
