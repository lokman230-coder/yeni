<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Services\Domain\TldPricingService;

final class AdminTldController
{
    public function index(Request $request): Response
    {
        $tlds = Connection::select("SELECT * FROM tld_configs ORDER BY sort_order, tld");
        $pricings = Connection::select("SELECT * FROM domain_pricing WHERE period_years = 1");
        $priceMap = [];
        foreach ($pricings as $p) $priceMap[$p['tld']] = $p;

        // Her TLD için satış fiyatı hesapla
        foreach ($tlds as &$t) {
            $t['sale_price'] = TldPricingService::priceFor($t['tld'], 1);
            $t['cost_price'] = $priceMap[$t['tld']] ?? null;
        }
        unset($t);

        return Response::html((new View())->render('admin::tlds.index', [
            'title' => 'TLD Yönetimi',
            'tlds'  => $tlds,
        ]));
    }

    public function edit(Request $request): Response
    {
        $id = (int) $request->param('id');
        $tld = Connection::selectOne("SELECT * FROM tld_configs WHERE id = ?", [$id]);
        if (!$tld) return Response::notFound();
        $pricing = Connection::selectOne("SELECT * FROM domain_pricing WHERE tld = ? AND period_years = 1", [$tld['tld']]);

        return Response::html((new View())->render('admin::tlds.form', [
            'title'   => 'TLD: .' . $tld['tld'],
            'tld'     => $tld,
            'pricing' => $pricing,
        ]));
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $tld = Connection::selectOne("SELECT * FROM tld_configs WHERE id = ?", [$id]);
        if (!$tld) return Response::notFound();

        $data = [
            'markup_type'         => in_array($request->input('markup_type'), ['percent','fixed'], true) ? $request->input('markup_type') : 'percent',
            'markup_value'        => (float) $request->input('markup_value', 30),
            'min_price'           => (float) $request->input('min_price', 0) ?: null,
            'requires_documents'  => $request->input('requires_documents') ? 1 : 0,
            'required_documents_json' => $request->input('required_documents') ? json_encode(explode(',', (string)$request->input('required_documents'))) : null,
            'allow_transfer'      => $request->input('allow_transfer') ? 1 : 0,
            'allow_backorder'     => $request->input('allow_backorder') ? 1 : 0,
            'is_popular'          => $request->input('is_popular') ? 1 : 0,
            'is_active'           => $request->input('is_active') ? 1 : 0,
            'updated_at'          => date('Y-m-d H:i:s'),
        ];
        Connection::update('tld_configs', $data, 'id = ?', [$id]);

        // Maliyet fiyatı da güncellendiyse
        if ($request->input('cost_register')) {
            $cost = (float) $request->input('cost_register');
            $existing = Connection::selectOne("SELECT id FROM domain_pricing WHERE tld = ? AND period_years = 1", [$tld['tld']]);
            $priceData = [
                'register_price' => $cost,
                'transfer_price' => $cost,
                'renew_price'    => (float) $request->input('cost_renew', $cost),
                'currency'       => 'TRY',
                'is_active'      => 1,
                'updated_at'     => date('Y-m-d H:i:s'),
            ];
            if ($existing) {
                Connection::update('domain_pricing', $priceData, 'id = ?', [$existing['id']]);
            } else {
                $priceData['tld'] = $tld['tld'];
                $priceData['period_years'] = 1;
                $priceData['created_at'] = date('Y-m-d H:i:s');
                Connection::insert('domain_pricing', $priceData);
            }
        }

        SessionManager::flash('success', '✓ TLD güncellendi.');
        return Response::redirect('/admin/tld-yonetimi');
    }

    public function syncFromRegistrar(Request $request): Response
    {
        // console komutunu çağır
        exec('cd ' . escapeshellarg(AHO_ROOT) . ' && php console domain:sync-tld-prices 2>&1', $out, $rc);
        $msg = strip_tags(implode(' ', array_slice($out, -3)));
        SessionManager::flash($rc === 0 ? 'success' : 'error', $msg);
        return Response::redirect('/admin/tld-yonetimi');
    }
}
