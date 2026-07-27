<?php

declare(strict_types=1);

namespace App\Modules\Referral\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View;
use App\Core\SessionManager;
use App\Modules\Referral\Services\PayoutService;
use App\Modules\Referral\Services\ReferralService;
use App\Services\Auth\AuthService;

/**
 * Müşteri panelindeki "Referans Programı" ekranı.
 * URL: /panel/referanslarim
 */
final class ReferralController
{
    public function index(Request $request): Response
    {
        if (!AuthService::isCustomer()) {
            return Response::redirect('/giris');
        }
        $customer = AuthService::customer();
        ReferralService::ensureDefaultSettings();
        $stats = ReferralService::statsFor((int) $customer['id']);
        $settings = ReferralService::settings();

        $view = new View();
        return Response::html($view->render('referral::customer.index', [
            'title'    => 'Referans Programım',
            'customer' => $customer,
            'stats'    => $stats,
            'settings' => $settings,
            'shareUrl' => $stats['code'] ? ReferralService::shareUrl((string) $stats['code']['code']) : '',
            'payouts'  => PayoutService::forCustomer((int) $customer['id']),
            'success'  => flash('success'),
            'error'    => flash('error'),
        ]));
    }

    public function requestPayout(Request $request): Response
    {
        if (!AuthService::isCustomer()) return Response::redirect('/giris');
        $customer = AuthService::customer();
        $r = PayoutService::request((int) $customer['id'], [
            'amount'         => (string) $request->input('amount'),
            'iban'           => (string) $request->input('iban'),
            'account_holder' => (string) $request->input('account_holder'),
            'bank_name'      => (string) $request->input('bank_name'),
            'note'           => (string) $request->input('note'),
        ]);
        SessionManager::flash($r['ok'] ? 'success' : 'error', $r['ok']
            ? '✓ Çekim isteğiniz alındı. Admin onayı sonrası havalesi yapılacak.'
            : $r['error']
        );
        return Response::redirect('/panel/referanslarim');
    }

    public function cancelPayout(Request $request): Response
    {
        if (!AuthService::isCustomer()) return Response::redirect('/giris');
        $customer = AuthService::customer();
        $ok = PayoutService::cancel((int) $request->param('id'), (int) $customer['id']);
        SessionManager::flash($ok ? 'success' : 'error', $ok ? 'İstek iptal edildi, bakiye iade edildi.' : 'İptal başarısız.');
        return Response::redirect('/panel/referanslarim');
    }
}
