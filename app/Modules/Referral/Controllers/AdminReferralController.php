<?php

declare(strict_types=1);

namespace App\Modules\Referral\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Modules\Referral\Services\PayoutService;
use App\Modules\Referral\Services\ReferralService;
use App\Services\Auth\AuthService;

/**
 * Admin — Referans / Affiliate yönetimi.
 * URL: /admin/referral
 */
final class AdminReferralController
{
    public function index(Request $request): Response
    {
        ReferralService::ensureDefaultSettings();
        $settings = ReferralService::settings();

        // Top 10 affiliate
        $top = Connection::select(
            "SELECT rc.*, c.email
             FROM referral_codes rc
             LEFT JOIN customers c ON c.id = rc.customer_id
             ORDER BY rc.total_earned DESC, rc.total_conversions DESC
             LIMIT 10"
        );

        // Bekleyen komisyonlar
        $pending = Connection::select(
            "SELECT rc.*, c.email AS referrer_email, c2.email AS referred_email
             FROM referral_commissions rc
             LEFT JOIN customers  c  ON c.id  = rc.referrer_customer_id
             LEFT JOIN referrals  r  ON r.id  = rc.referral_id
             LEFT JOIN customers  c2 ON c2.id = r.referred_customer_id
             WHERE rc.status = 'pending'
             ORDER BY rc.created_at DESC LIMIT 100"
        );

        // Genel metrikler
        $metrics = [
            'total_codes'      => (int) (Connection::selectOne("SELECT COUNT(*) c FROM referral_codes")['c'] ?? 0),
            'total_visits'     => (int) (Connection::selectOne("SELECT COALESCE(SUM(total_visits),0) c FROM referral_codes")['c'] ?? 0),
            'total_signups'    => (int) (Connection::selectOne("SELECT COUNT(*) c FROM referrals")['c'] ?? 0),
            'total_conversions'=> (int) (Connection::selectOne("SELECT COUNT(*) c FROM referrals WHERE status='converted'")['c'] ?? 0),
            'pending_amount'   => (float) (Connection::selectOne("SELECT COALESCE(SUM(commission_amount),0) c FROM referral_commissions WHERE status='pending'")['c'] ?? 0),
            'approved_amount'  => (float) (Connection::selectOne("SELECT COALESCE(SUM(commission_amount),0) c FROM referral_commissions WHERE status IN ('approved','paid')")['c'] ?? 0),
        ];

        $view = new View();
        return Response::html($view->render('referral::admin.index', [
            'title'    => 'Referans / Affiliate Programı',
            'settings' => $settings,
            'top'      => $top,
            'pending'  => $pending,
            'metrics'  => $metrics,
            'payouts'  => PayoutService::pendingForAdmin(50),
            'success'  => flash('success'),
            'error'    => flash('error'),
        ]));
    }

    public function approvePayout(Request $request): Response
    {
        $admin = AuthService::admin();
        $ok = PayoutService::approve((int) $request->param('id'), (int) ($admin['id'] ?? 0));
        SessionManager::flash($ok ? 'success' : 'error', $ok ? 'Payout onaylandı — havaleyi yapıp "Ödendi" olarak işaretleyin.' : 'Onaylama başarısız.');
        return Response::redirect('/admin/referral');
    }

    public function markPayoutPaid(Request $request): Response
    {
        $admin = AuthService::admin();
        $note = (string) $request->input('note', '');
        $ok = PayoutService::markPaid((int) $request->param('id'), (int) ($admin['id'] ?? 0), $note);
        SessionManager::flash($ok ? 'success' : 'error', $ok ? 'Payout ödendi olarak işaretlendi.' : 'İşlem başarısız.');
        return Response::redirect('/admin/referral');
    }

    public function rejectPayout(Request $request): Response
    {
        $admin = AuthService::admin();
        $note = (string) $request->input('note', 'Reddedildi');
        $ok = PayoutService::reject((int) $request->param('id'), (int) ($admin['id'] ?? 0), $note);
        SessionManager::flash($ok ? 'success' : 'error', $ok ? 'Payout reddedildi — bakiye iade edildi.' : 'İşlem başarısız.');
        return Response::redirect('/admin/referral');
    }

    public function saveSettings(Request $request): Response
    {
        $data = [
            'commission_percent' => max(0, min(50, (float) str_replace(',', '.', (string) $request->input('commission_percent', 10)))),
            'cookie_days'        => max(1, min(365, (int) $request->input('cookie_days', 60))),
            'min_payout'         => max(0, (float) str_replace(',', '.', (string) $request->input('min_payout', 100))),
            'first_order_only'   => $request->input('first_order_only') ? 1 : 0,
            'is_active'          => $request->input('is_active') ? 1 : 0,
            'updated_at'         => date('Y-m-d H:i:s'),
        ];
        try {
            $row = Connection::selectOne("SELECT id FROM referral_settings ORDER BY id ASC LIMIT 1");
            if ($row) {
                Connection::update('referral_settings', $data, 'id = ?', [$row['id']]);
            } else {
                Connection::insert('referral_settings', array_merge(['name' => 'Varsayılan Program'], $data));
            }
            SessionManager::flash('success', 'Program ayarları kaydedildi.');
        } catch (\Throwable $e) {
            SessionManager::flash('error', 'Kayıt hatası: ' . $e->getMessage());
        }
        return Response::redirect('/admin/referral');
    }

    public function approve(Request $request): Response
    {
        $id = (int) $request->param('id');
        if (ReferralService::approveCommission($id)) {
            SessionManager::flash('success', "Komisyon #$id onaylandı ve referrer bakiyesine eklendi.");
        } else {
            SessionManager::flash('error', 'Onaylama başarısız (durum uygun değil olabilir).');
        }
        return Response::redirect('/admin/referral');
    }

    public function reject(Request $request): Response
    {
        $id = (int) $request->param('id');
        $note = (string) $request->input('note', '');
        if (ReferralService::rejectCommission($id, $note)) {
            SessionManager::flash('success', "Komisyon #$id reddedildi.");
        } else {
            SessionManager::flash('error', 'Red işlemi başarısız.');
        }
        return Response::redirect('/admin/referral');
    }
}
