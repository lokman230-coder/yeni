<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;

/**
 * Admin > Global arama — topbar arama kutusu için JSON endpoint.
 *
 * Aranan yerler: customers, orders, invoices, domains, tickets, hosting_accounts
 * Sonuçlar tek liste halinde döner ({type, id, title, subtitle, url}).
 */
final class GlobalSearchController
{
    public function search(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return Response::json(['results' => []]);
        }
        $like = "%$q%";
        $results = [];

        // Customers
        try {
            $rows = Connection::select(
                "SELECT id, email, CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,'')) AS name
                 FROM customers WHERE email LIKE ? OR first_name LIKE ? OR last_name LIKE ? LIMIT 5",
                [$like, $like, $like]
            );
            foreach ($rows as $r) {
                $results[] = [
                    'type' => 'customer', 'icon' => '👤',
                    'title' => trim($r['name']) ?: $r['email'],
                    'subtitle' => $r['email'],
                    'url' => '/admin/musteriler/' . $r['id'],
                ];
            }
        } catch (\Throwable) {}

        // Orders
        try {
            $rows = Connection::select(
                "SELECT id, order_number, status, total, currency FROM orders WHERE order_number LIKE ? LIMIT 5",
                [$like]
            );
            foreach ($rows as $r) {
                $results[] = [
                    'type' => 'order', 'icon' => '📦',
                    'title' => $r['order_number'],
                    'subtitle' => number_format((float)$r['total'], 2, ',', '.') . ' ' . $r['currency'] . ' · ' . $r['status'],
                    'url' => '/admin/siparisler/' . $r['id'],
                ];
            }
        } catch (\Throwable) {}

        // Invoices
        try {
            $rows = Connection::select(
                "SELECT id, invoice_number, status, total FROM invoices WHERE invoice_number LIKE ? LIMIT 5",
                [$like]
            );
            foreach ($rows as $r) {
                $results[] = [
                    'type' => 'invoice', 'icon' => '🧾',
                    'title' => $r['invoice_number'],
                    'subtitle' => number_format((float)$r['total'], 2, ',', '.') . ' · ' . $r['status'],
                    'url' => '/admin/faturalar/' . $r['id'] . '/pdf',
                ];
            }
        } catch (\Throwable) {}

        // Domains
        try {
            $rows = Connection::select(
                "SELECT id, domain_name, status FROM domains WHERE domain_name LIKE ? LIMIT 5",
                [$like]
            );
            foreach ($rows as $r) {
                $results[] = [
                    'type' => 'domain', 'icon' => '🌐',
                    'title' => $r['domain_name'],
                    'subtitle' => $r['status'],
                    'url' => '/admin/domain-center/' . $r['id'],
                ];
            }
        } catch (\Throwable) {}

        // Tickets
        try {
            $rows = Connection::select(
                "SELECT id, ticket_number, subject, status FROM tickets WHERE ticket_number LIKE ? OR subject LIKE ? LIMIT 5",
                [$like, $like]
            );
            foreach ($rows as $r) {
                $results[] = [
                    'type' => 'ticket', 'icon' => '🎧',
                    'title' => $r['subject'],
                    'subtitle' => $r['ticket_number'] . ' · ' . $r['status'],
                    'url' => '/admin/destek-merkezi/' . $r['id'],
                ];
            }
        } catch (\Throwable) {}

        // Products
        try {
            $rows = Connection::select(
                "SELECT id, name, slug, type FROM products WHERE name LIKE ? OR slug LIKE ? LIMIT 5",
                [$like, $like]
            );
            foreach ($rows as $r) {
                $results[] = [
                    'type' => 'product', 'icon' => '🛒',
                    'title' => $r['name'],
                    'subtitle' => $r['type'] . ' · /' . $r['slug'],
                    'url' => '/admin/urun-merkezi/' . $r['id'],
                ];
            }
        } catch (\Throwable) {}

        // Coupons
        try {
            $rows = Connection::select(
                "SELECT id, code, name, value, type FROM coupons WHERE code LIKE ? OR name LIKE ? LIMIT 5",
                [$like, $like]
            );
            foreach ($rows as $r) {
                $val = $r['type'] === 'percent' ? '%' . number_format((float)$r['value'], 0) : number_format((float)$r['value'], 2, ',', '.') . ' ₺';
                $results[] = [
                    'type' => 'coupon', 'icon' => '🎟️',
                    'title' => $r['code'],
                    'subtitle' => $val . ' · ' . $r['name'],
                    'url' => '/admin/kuponlar/' . $r['id'],
                ];
            }
        } catch (\Throwable) {}

        // Blog posts
        try {
            $rows = Connection::select(
                "SELECT id, title, slug, status FROM blog_posts WHERE title LIKE ? OR slug LIKE ? LIMIT 3",
                [$like, $like]
            );
            foreach ($rows as $r) {
                $results[] = [
                    'type' => 'blog', 'icon' => '✍️',
                    'title' => $r['title'],
                    'subtitle' => '/' . $r['slug'] . ' · ' . $r['status'],
                    'url' => '/admin/blog/' . $r['id'],
                ];
            }
        } catch (\Throwable) {}

        return Response::json(['results' => array_slice($results, 0, 25), 'query' => $q]);
    }
}
