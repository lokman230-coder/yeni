<?php

declare(strict_types=1);

namespace App\Modules\Hosting\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Modules\Hosting\HostingManager;
use App\Support\Encrypter;

/**
 * Admin — Hosting & Sunucu Yönetimi CRUD.
 *
 * /admin/hosting-sunucu           → sunucu listesi + metrikler
 * /admin/hosting-sunucu/yeni      → yeni sunucu formu
 * /admin/hosting-sunucu/kaydet    → yeni sunucu POST
 * /admin/hosting-sunucu/{id}      → düzenleme
 * /admin/hosting-sunucu/{id}/kaydet
 * /admin/hosting-sunucu/{id}/test → bağlantı testi (JSON)
 * /admin/hosting-sunucu/{id}/sil
 */
final class AdminServerController
{
    public function index(Request $request): Response
    {
        $servers = Connection::select(
            "SELECT s.*,
                    (SELECT COUNT(*) FROM hosting_accounts WHERE server_id = s.id AND status='active') AS active_accounts
             FROM hosting_servers s
             ORDER BY s.name ASC"
        );
        $view = new View();
        return Response::html($view->render('hosting::admin.index', [
            'title'   => 'Hosting & Sunucu',
            'servers' => $servers,
            'success' => flash('success'),
            'error'   => flash('error'),
        ]));
    }

    public function createForm(Request $request): Response
    {
        $view = new View();
        return Response::html($view->render('hosting::admin.form', [
            'title'  => 'Yeni Sunucu',
            'server' => null,
            'error'  => flash('error'),
        ]));
    }

    public function editForm(Request $request): Response
    {
        $id = (int) $request->param('id');
        $server = Connection::selectOne("SELECT * FROM hosting_servers WHERE id = ?", [$id]);
        if (!$server) return Response::notFound();
        $view = new View();
        return Response::html($view->render('hosting::admin.form', [
            'title'   => 'Sunucu: ' . $server['name'],
            'server'  => $server,
            'success' => flash('success'),
            'error'   => flash('error'),
        ]));
    }

    public function store(Request $request): Response
    {
        $id = (int) $request->param('id');
        $data = [
            'name'         => trim((string) $request->input('name', '')),
            'hostname'     => trim((string) $request->input('hostname', '')),
            'ip'           => trim((string) $request->input('ip', '')) ?: null,
            'panel'        => (string) $request->input('panel', 'cpanel'),
            'username'     => trim((string) $request->input('username', '')) ?: null,
            'port'         => (int) $request->input('port', 2087),
            'use_ssl'      => $request->input('use_ssl') ? 1 : 0,
            'is_active'    => $request->input('is_active') ? 1 : 0,
            'max_accounts' => $request->input('max_accounts') ? (int) $request->input('max_accounts') : null,
            'server_group' => trim((string) $request->input('server_group', '')) ?: null,
        ];

        if ($data['name'] === '' || $data['hostname'] === '') {
            SessionManager::flash('error', 'Ad ve hostname zorunlu.');
            return Response::redirect($id ? "/admin/hosting-sunucu/$id" : '/admin/hosting-sunucu/yeni');
        }

        $password = (string) $request->input('password', '');
        $apiKey   = (string) $request->input('api_key', '');
        if ($password !== '') $data['password_encrypted'] = Encrypter::encrypt($password);
        if ($apiKey   !== '') $data['api_key_encrypted']  = Encrypter::encrypt($apiKey);

        try {
            if ($id > 0) {
                Connection::update('hosting_servers', $data, 'id = ?', [$id]);
                SessionManager::flash('success', 'Sunucu güncellendi.');
            } else {
                $data['current_accounts'] = 0;
                $id = Connection::insert('hosting_servers', $data);
                SessionManager::flash('success', 'Sunucu eklendi.');
            }
            return Response::redirect("/admin/hosting-sunucu/$id");
        } catch (\Throwable $e) {
            SessionManager::flash('error', 'Hata: ' . $e->getMessage());
            return Response::redirect('/admin/hosting-sunucu');
        }
    }

    /** AJAX: bağlantı testi */
    public function test(Request $request): Response
    {
        $id = (int) $request->param('id');
        try {
            $driver = HostingManager::forServer($id);
            $r = $driver->testConnection();
            return Response::json([
                'ok'      => (bool) ($r['success'] ?? false),
                'message' => (string) ($r['message'] ?? ''),
                'driver'  => $driver->id(),
            ]);
        } catch (\Throwable $e) {
            return Response::json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id');
        $usage = Connection::selectOne("SELECT COUNT(*) c FROM hosting_accounts WHERE server_id = ?", [$id]);
        if ((int) $usage['c'] > 0) {
            SessionManager::flash('error', "Bu sunucuya bağlı {$usage['c']} hesap var. Önce hesapları taşıyın veya sonlandırın.");
            return Response::redirect("/admin/hosting-sunucu/$id");
        }
        try {
            Connection::delete('hosting_servers', 'id = ?', [$id]);
            SessionManager::flash('success', 'Sunucu silindi.');
        } catch (\Throwable $e) {
            SessionManager::flash('error', 'Silinemedi: ' . $e->getMessage());
        }
        return Response::redirect('/admin/hosting-sunucu');
    }
}
