<?php

declare(strict_types=1);

namespace App\Modules\Domain\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;

/**
 * Admin — Domain Center CRUD.
 *
 * /admin/domain-center           → listeleme + filtre
 * /admin/domain-center/{id}      → detay/düzenle
 * /admin/domain-center/{id}/kaydet
 * /admin/domain-center/{id}/sil
 * /admin/domain-center/yeni      → manuel ekle (registrar-dışı elle giriş)
 */
final class AdminDomainController
{
    public function index(Request $request): Response
    {
        $q      = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $filter = 'WHERE 1=1';
        $params = [];

        if ($q !== '') {
            $filter .= " AND (d.domain_name LIKE ? OR c.email LIKE ?)";
            $params[] = "%$q%";
            $params[] = "%$q%";
        }
        if ($status !== '' && in_array($status, ['active','pending','expired','cancelled','suspended','pending_transfer'], true)) {
            $filter .= " AND d.status = ?";
            $params[] = $status;
        }

        $domains = Connection::select(
            "SELECT d.*, c.email AS customer_email,
                    CONCAT(COALESCE(c.first_name,''),' ',COALESCE(c.last_name,'')) AS customer_name,
                    r.name AS registrar_name
             FROM domains d
             LEFT JOIN customers c ON c.id = d.customer_id
             LEFT JOIN domain_registrars r ON r.id = d.registrar_id
             $filter
             ORDER BY d.expiry_date ASC
             LIMIT 200",
            $params
        );

        $metrics = [
            'total'      => (int) (Connection::selectOne("SELECT COUNT(*) c FROM domains")['c'] ?? 0),
            'active'     => (int) (Connection::selectOne("SELECT COUNT(*) c FROM domains WHERE status='active'")['c'] ?? 0),
            'pending'    => (int) (Connection::selectOne("SELECT COUNT(*) c FROM domains WHERE status='pending'")['c'] ?? 0),
            'expired'    => (int) (Connection::selectOne("SELECT COUNT(*) c FROM domains WHERE status='expired'")['c'] ?? 0),
            'expiring30' => (int) (Connection::selectOne("SELECT COUNT(*) c FROM domains WHERE status='active' AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)")['c'] ?? 0),
        ];

        $view = new View();
        return Response::html($view->render('domain::admin.index', [
            'title'    => 'Domain Center',
            'domains'  => $domains,
            'metrics'  => $metrics,
            'q'        => $q,
            'status'   => $status,
            'success'  => flash('success'),
            'error'    => flash('error'),
        ]));
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $domain = Connection::selectOne(
            "SELECT d.*, c.email AS customer_email FROM domains d LEFT JOIN customers c ON c.id = d.customer_id WHERE d.id = ?",
            [$id]
        );
        if (!$domain) {
            return Response::notFound('Domain bulunamadı');
        }
        $registrars = Connection::select("SELECT id, name FROM domain_registrars ORDER BY name");
        $view = new View();
        return Response::html($view->render('domain::admin.edit', [
            'title'      => 'Domain: ' . $domain['domain_name'],
            'domain'     => $domain,
            'registrars' => $registrars,
            'success'    => flash('success'),
            'error'      => flash('error'),
        ]));
    }

    public function save(Request $request): Response
    {
        $id = (int) $request->param('id');
        $existing = Connection::selectOne("SELECT id FROM domains WHERE id = ?", [$id]);
        if (!$existing) {
            return Response::notFound();
        }

        $data = [
            'status'         => (string) $request->input('status', 'pending'),
            'expiry_date'    => (string) $request->input('expiry_date') ?: null,
            'next_due_date'  => (string) $request->input('next_due_date') ?: null,
            'auto_renew'     => $request->input('auto_renew') ? 1 : 0,
            'transfer_lock'  => $request->input('transfer_lock') ? 1 : 0,
            'whois_privacy'  => $request->input('whois_privacy') ? 1 : 0,
            'nameservers'    => (string) $request->input('nameservers', ''),
            'registrar_id'   => $request->input('registrar_id') ? (int) $request->input('registrar_id') : null,
            'epp_code'       => (string) $request->input('epp_code', '') ?: null,
            'updated_at'     => date('Y-m-d H:i:s'),
        ];
        try {
            Connection::update('domains', $data, 'id = ?', [$id]);
            SessionManager::flash('success', 'Domain güncellendi.');
        } catch (\Throwable $e) {
            SessionManager::flash('error', 'Hata: ' . $e->getMessage());
        }
        return Response::redirect('/admin/domain-center/' . $id);
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id');
        try {
            Connection::delete('domains', 'id = ?', [$id]);
            SessionManager::flash('success', 'Domain silindi.');
        } catch (\Throwable $e) {
            SessionManager::flash('error', 'Silinemedi: ' . $e->getMessage());
        }
        return Response::redirect('/admin/domain-center');
    }

    /** WHOIS ile bitiş tarihini + registrar bilgisini yenile */
    public function refreshWhois(Request $request): Response
    {
        $id = (int) $request->param('id');
        $domain = Connection::selectOne("SELECT * FROM domains WHERE id = ?", [$id]);
        if (!$domain) return Response::notFound();

        try {
            $tool = new \App\Modules\SiteTools\Tools\WhoisTool();
            $r = $tool->run((string) $domain['domain_name']);
            if (($r['success'] ?? false) && !empty($r['data']['expiry_date'])) {
                $exp = date('Y-m-d', strtotime((string) $r['data']['expiry_date']));
                Connection::update('domains', [
                    'expiry_date' => $exp,
                    'next_due_date' => $exp,
                    'nameservers' => is_array($r['data']['nameservers'] ?? null) ? implode("\n", $r['data']['nameservers']) : ($r['data']['nameservers'] ?? $domain['nameservers']),
                    'updated_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$id]);
                SessionManager::flash('success', "✓ WHOIS güncellendi — bitiş: $exp");
            } else {
                SessionManager::flash('error', 'WHOIS okunamadı: ' . ($r['error'] ?? 'bilinmeyen hata'));
            }
        } catch (\Throwable $e) {
            SessionManager::flash('error', 'WHOIS hatası: ' . $e->getMessage());
        }
        return Response::redirect('/admin/domain-center/' . $id);
    }

    public function createForm(Request $request): Response
    {
        $registrars = Connection::select("SELECT id, name FROM domain_registrars ORDER BY name");
        $view = new View();
        return Response::html($view->render('domain::admin.create', [
            'title'      => 'Yeni Domain Ekle',
            'registrars' => $registrars,
            'error'      => flash('error'),
        ]));
    }

    public function store(Request $request): Response
    {
        $name = strtolower(trim((string) $request->input('domain_name', '')));
        $customerId = (int) $request->input('customer_id', 0);
        if ($name === '' || !preg_match('/^[a-z0-9][a-z0-9\-]*(\.[a-z]{2,})+$/', $name)) {
            SessionManager::flash('error', 'Geçerli bir domain adı girin.');
            return Response::redirect('/admin/domain-center/yeni');
        }
        $exists = Connection::selectOne("SELECT id FROM domains WHERE domain_name = ?", [$name]);
        if ($exists) {
            SessionManager::flash('error', 'Bu domain zaten kayıtlı.');
            return Response::redirect('/admin/domain-center/yeni');
        }
        try {
            $id = Connection::insert('domains', [
                'customer_id'       => $customerId ?: 1,
                'domain_name'       => $name,
                'registrar_id'      => $request->input('registrar_id') ? (int) $request->input('registrar_id') : null,
                'registration_date' => (string) $request->input('registration_date', date('Y-m-d')),
                'expiry_date'       => (string) $request->input('expiry_date') ?: date('Y-m-d', strtotime('+1 year')),
                'next_due_date'     => (string) $request->input('next_due_date') ?: date('Y-m-d', strtotime('+1 year')),
                'status'            => (string) $request->input('status', 'active'),
                'auto_renew'        => $request->input('auto_renew') ? 1 : 0,
            ]);
            SessionManager::flash('success', "Domain kaydedildi (#$id).");
            return Response::redirect('/admin/domain-center/' . $id);
        } catch (\Throwable $e) {
            SessionManager::flash('error', 'Hata: ' . $e->getMessage());
            return Response::redirect('/admin/domain-center/yeni');
        }
    }
}
