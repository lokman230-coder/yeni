<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View;

final class DashboardController
{
    public function index(Request $request): Response
    {
        $stats = self::stats();
        $recentOrders = self::safeSelect(
            "SELECT o.id, o.order_number, o.total, o.currency, o.status, o.created_at,
                    c.email, CONCAT(COALESCE(c.first_name,''),' ',COALESCE(c.last_name,'')) AS customer_name
             FROM orders o LEFT JOIN customers c ON c.id = o.customer_id
             ORDER BY o.created_at DESC LIMIT 8"
        );
        $recentTickets = self::safeSelect(
            "SELECT t.id, t.ticket_number, t.subject, t.priority, t.status, t.created_at,
                    c.email AS customer_email
             FROM tickets t LEFT JOIN customers c ON c.id = t.customer_id
             WHERE t.status IN ('open','customer_reply')
             ORDER BY t.last_reply_at DESC, t.created_at DESC LIMIT 5"
        );

        $view = new View();
        $onboarding = \App\Services\Onboarding\OnboardingChecklist::items();
        $onboardingDone = \App\Services\Onboarding\OnboardingChecklist::isFullyDone();
        return Response::html($view->render('admin::dashboard.index', [
            'title'          => 'Kontrol Paneli',
            'stats'          => $stats,
            'recentOrders'   => $recentOrders,
            'recentTickets'  => $recentTickets,
            'onboarding'     => $onboarding,
            'onboardingDone' => $onboardingDone,
        ]));
    }

    /** @return array<string, int|float> */
    private static function stats(): array
    {
        $today = date('Y-m-d');
        $thisMonth = date('Y-m-01');
        return [
            'customers_total'  => self::count("SELECT COUNT(*) c FROM customers"),
            'customers_active' => self::count("SELECT COUNT(*) c FROM customers WHERE status='active'"),
            'orders_today'     => self::count("SELECT COUNT(*) c FROM orders WHERE DATE(created_at) = ?", [$today]),
            'orders_month'     => self::count("SELECT COUNT(*) c FROM orders WHERE DATE(created_at) >= ?", [$thisMonth]),
            'revenue_month'    => self::sum("SELECT COALESCE(SUM(total),0) c FROM orders WHERE status='paid' AND DATE(paid_at) >= ?", [$thisMonth]),
            'revenue_today'    => self::sum("SELECT COALESCE(SUM(total),0) c FROM orders WHERE status='paid' AND DATE(paid_at) = ?", [$today]),
            'invoices_unpaid'  => self::count("SELECT COUNT(*) c FROM invoices WHERE status IN ('unpaid','partially_paid','overdue')"),
            'unpaid_total'     => self::sum("SELECT COALESCE(SUM(balance),0) c FROM invoices WHERE status IN ('unpaid','partially_paid','overdue')"),
            'tickets_open'     => self::count("SELECT COUNT(*) c FROM tickets WHERE status IN ('open','customer_reply')"),
            'services_active'  => self::count("SELECT COUNT(*) c FROM hosting_accounts WHERE status='active'"),
            'domains_active'   => self::count("SELECT COUNT(*) c FROM domains WHERE status='active'"),
            'payouts_pending'  => self::count("SELECT COUNT(*) c FROM payout_requests WHERE status='pending'"),
        ];
    }

    private static function count(string $sql, array $params = []): int
    {
        try { return (int) (Connection::selectOne($sql, $params)['c'] ?? 0); }
        catch (\Throwable) { return 0; }
    }
    private static function sum(string $sql, array $params = []): float
    {
        try { return (float) (Connection::selectOne($sql, $params)['c'] ?? 0); }
        catch (\Throwable) { return 0.0; }
    }
    private static function safeSelect(string $sql): array
    {
        try { return Connection::select($sql); }
        catch (\Throwable) { return []; }
    }
}
