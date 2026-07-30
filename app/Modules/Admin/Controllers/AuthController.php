<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Services\Auth\AuthService;

final class AuthController
{
    public function showLogin(Request $request): Response
    {
        if (AuthService::isAdmin()) {
            return Response::redirect('/admin');
        }
        $view = new View();
        return Response::html($view->render('admin::auth.login', [
            'title' => 'Admin Girişi',
        ]));
    }

    public function login(Request $request): Response
    {
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');

        if ($email === '' || $password === '') {
            SessionManager::flash('error', 'E-posta ve şifre zorunludur.');
            return Response::redirect('/admin/giris');
        }

        $result = AuthService::attemptAdmin($email, $password);
        if ($result === 'fail') {
            SessionManager::flash('error', 'E-posta veya şifre hatalı.');
            SessionManager::flash('_old', ['email' => $email]);
            return Response::redirect('/admin/giris');
        }
        if ($result === '2fa') {
            return Response::redirect('/admin/2fa');
        }

        SessionManager::flash('success', 'Hoş geldiniz.');
        return Response::redirect('/admin');
    }

    public function show2fa(Request $request): Response
    {
        if (!SessionManager::get('pending_2fa_admin_id')) {
            return Response::redirect('/admin/giris');
        }
        $view = new View();
        return Response::html($view->render('admin::auth.twofactor', [
            'title' => 'İki Faktörlü Doğrulama',
            'error' => flash('error'),
        ]));
    }

    public function verify2fa(Request $request): Response
    {
        $adminId = (int) SessionManager::get('pending_2fa_admin_id', 0);
        if ($adminId === 0) {
            return Response::redirect('/admin/giris');
        }
        $code = trim((string) $request->input('code', ''));
        if ($code === '' || !\App\Services\Auth\TwoFactorService::verify('admin', $adminId, $code)) {
            SessionManager::flash('error', 'Kod doğrulanamadı. Tekrar deneyin.');
            return Response::redirect('/admin/2fa');
        }
        if (!AuthService::completeTwoFactorAdmin($adminId)) {
            SessionManager::flash('error', 'Oturum başlatılamadı.');
            return Response::redirect('/admin/giris');
        }
        SessionManager::flash('success', 'Hoş geldiniz.');
        return Response::redirect('/admin');
    }

    public function logout(Request $request): Response
    {
        AuthService::logoutAdmin();
        return Response::redirect('/admin/giris');
    }
}
