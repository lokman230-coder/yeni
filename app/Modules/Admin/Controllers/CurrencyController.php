<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Services\Currency\CurrencyRateUpdater;

/**
 * Admin — Kur Yönetimi.
 *
 * Görev:
 *  - TCMB kuru + kar marjı (%) → görünen kur formülüyle listele
 *  - Marj yüzdesi düzenle (her para birimi bağımsız)
 *  - Aktif/Pasif toggle (müşteri seçim ekranında gözükecek mi)
 *  - "Şimdi Güncelle" butonu → TCMB'den anlık çek
 *  - Yeni para birimi ekle
 */
final class CurrencyController
{
    public function index(Request $request): Response
    {
        $rows = Connection::select("SELECT * FROM currency_rates ORDER BY (currency='TRY') DESC, currency ASC");
        $view = new View();
        return Response::html($view->render('admin::currency.index', [
            'title'    => 'Kur Yönetimi',
            'rates'    => $rows,
            'success'  => flash('success'),
            'error'    => flash('error'),
            'info'     => flash('info'),
        ]));
    }

    /** Marj + aktiflik toplu güncelleme */
    public function save(Request $request): Response
    {
        $margins = $request->input('margin', []);
        $actives = $request->input('active', []);
        $updated = 0;
        foreach ((array) $margins as $id => $margin) {
            $id = (int) $id;
            if ($id <= 0) continue;
            $m = (float) str_replace(',', '.', (string) $margin);
            if ($m < -50 || $m > 100) continue; // sanity
            $isActive = isset($actives[$id]) ? 1 : 0;
            Connection::update('currency_rates',
                ['margin_percent' => $m, 'is_active' => $isActive],
                'id = ?', [$id]
            );
            $updated++;
        }
        SessionManager::flash('success', "$updated para birimi güncellendi.");
        return Response::redirect('/admin/kur-yonetimi');
    }

    /** TCMB'den anlık güncelleme */
    public function refresh(Request $request): Response
    {
        try {
            $r = CurrencyRateUpdater::updateAll();
            if ($r['updated'] > 0) {
                SessionManager::flash('success',
                    "✓ {$r['updated']} kur güncellendi (kaynak: {$r['source']})"
                    . (count($r['errors']) ? ' — ' . count($r['errors']) . ' hata' : '')
                );
            } else {
                SessionManager::flash('error', 'Hiçbir kur güncellenemedi: ' . implode(', ', $r['errors']));
            }
        } catch (\Throwable $e) {
            SessionManager::flash('error', 'Hata: ' . $e->getMessage());
        }
        return Response::redirect('/admin/kur-yonetimi');
    }

    /** Yeni para birimi ekle */
    public function add(Request $request): Response
    {
        $code = strtoupper(trim((string) $request->input('currency', '')));
        if (!preg_match('/^[A-Z]{3}$/', $code)) {
            SessionManager::flash('error', 'Geçerli bir 3 harfli para birimi kodu girin (USD, EUR, JPY vb).');
            return Response::redirect('/admin/kur-yonetimi');
        }
        $exists = Connection::selectOne("SELECT id FROM currency_rates WHERE currency = ?", [$code]);
        if ($exists) {
            SessionManager::flash('info', "$code zaten kayıtlı.");
            return Response::redirect('/admin/kur-yonetimi');
        }
        Connection::insert('currency_rates', [
            'currency'       => $code,
            'symbol'         => (string) $request->input('symbol', $code),
            'rate'           => 0,
            'margin_percent' => (float) str_replace(',', '.', (string) $request->input('margin', '0')),
            'is_active'      => 1,
            'source'         => 'manual',
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        SessionManager::flash('success', "$code eklendi. Kur değerini güncellemek için 'TCMB'den Şimdi Çek' butonuna basın.");
        return Response::redirect('/admin/kur-yonetimi');
    }

    /** Sil */
    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id');
        $row = Connection::selectOne("SELECT currency FROM currency_rates WHERE id = ?", [$id]);
        if ($row && $row['currency'] !== 'TRY') {
            Connection::delete('currency_rates', 'id = ?', [$id]);
            SessionManager::flash('success', "{$row['currency']} silindi.");
        } else {
            SessionManager::flash('error', 'TRY silinemez.');
        }
        return Response::redirect('/admin/kur-yonetimi');
    }
}
