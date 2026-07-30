<?php

declare(strict_types=1);

namespace App\Modules\Product\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Modules\Product\Services\PricingService;
use App\Modules\Product\Services\ProductRepository;
use App\Services\Currency\CurrencyService;
use App\Support\Slug;

final class AdminProductController
{
    public function index(Request $request): Response
    {
        $filters = [
            'q'        => (string) $request->query('q', ''),
            'type'     => (string) $request->query('type', ''),
            'status'   => (string) $request->query('status', ''),
            'group_id' => (int) $request->query('group_id', 0) ?: null,
        ];
        $products = ProductRepository::all(array_filter($filters, fn($v) => $v !== '' && $v !== null && $v !== 0));
        $groups   = ProductRepository::groups();
        $types    = ProductRepository::types();

        $view = new View();
        return Response::html($view->render('product::admin.index', [
            'title'    => 'Ürün Merkezi',
            'products' => $products,
            'groups'   => $groups,
            'types'    => $types,
            'filters'  => $filters,
        ]));
    }

    public function create(Request $request): Response
    {
        $view = new View();
        return Response::html($view->render('product::admin.form', [
            'title'   => 'Yeni Ürün',
            'product' => null,
            'groups'  => ProductRepository::groups(),
            'types'   => ProductRepository::types(),
            'prices'  => [],
            'currencies' => CurrencyService::supported(),
            'periods' => PricingService::allPeriods(),
            'servers' => $this->servers(),
        ]));
    }

    public function edit(Request $request): Response
    {
        $id = (int) $request->param('id');
        $product = ProductRepository::find($id);
        if (!$product) {
            SessionManager::flash('error', 'Ürün bulunamadı.');
            return Response::redirect('/admin/urun-merkezi');
        }
        $view = new View();
        return Response::html($view->render('product::admin.form', [
            'title'   => 'Ürünü Düzenle: ' . $product['name'],
            'product' => $product,
            'groups'  => ProductRepository::groups(),
            'types'   => ProductRepository::types(),
            'prices'  => ProductRepository::prices($id),
            'addons'  => ProductRepository::addons($id),
            'customFields' => ProductRepository::customFields($id, false),
            'currencies' => CurrencyService::supported(),
            'periods' => PricingService::allPeriods(),
            'servers' => $this->servers(),
        ]));
    }

    public function store(Request $request): Response
    {
        $data = $this->extractProductData($request);
        $errors = $this->validate($data);
        if ($errors) {
            SessionManager::flash('error', implode(' ', $errors));
            SessionManager::flash('_old', $data);
            return Response::redirect('/admin/urun-merkezi/yeni');
        }
        $data['slug'] = Slug::unique($data['name'], 'products');
        $id = ProductRepository::create($data);
        // Fiyatları kaydet
        $this->savePrices($id, $request);
        // Ek paket + Özel alanları kaydet
        ProductRepository::replaceAddons($id, (array) $request->input('addons', []));
        ProductRepository::replaceCustomFields($id, (array) $request->input('custom_fields', []));
        SessionManager::flash('success', 'Ürün oluşturuldu.');
        return Response::redirect('/admin/urun-merkezi/' . $id . '/duzenle');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        if (!ProductRepository::find($id)) {
            SessionManager::flash('error', 'Ürün bulunamadı.');
            return Response::redirect('/admin/urun-merkezi');
        }
        $data = $this->extractProductData($request);
        $errors = $this->validate($data);
        if ($errors) {
            SessionManager::flash('error', implode(' ', $errors));
            return Response::redirect('/admin/urun-merkezi/' . $id . '/duzenle');
        }
        ProductRepository::update($id, $data);
        $this->savePrices($id, $request);
        // Ek paket + Özel alanları kaydet
        ProductRepository::replaceAddons($id, (array) $request->input('addons', []));
        ProductRepository::replaceCustomFields($id, (array) $request->input('custom_fields', []));
        SessionManager::flash('success', 'Ürün güncellendi.');
        return Response::redirect('/admin/urun-merkezi/' . $id . '/duzenle');
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->param('id');
        ProductRepository::softDelete($id);
        SessionManager::flash('success', 'Ürün silindi.');
        return Response::redirect('/admin/urun-merkezi');
    }

    // --- Yardımcılar ---

    private function extractProductData(Request $request): array
    {
        return [
            'name'              => trim((string) $request->input('name', '')),
            'type'              => (string) $request->input('type', 'hosting'),
            'group_id'          => (int) $request->input('group_id', 0) ?: null,
            'short_description' => (string) $request->input('short_description', ''),
            'description'       => (string) $request->input('description', ''),
            'status'            => in_array($request->input('status'), ['active','hidden','disabled'], true)
                                    ? $request->input('status') : 'hidden',
            'stock_type'        => $request->input('stock_type') === 'limited' ? 'limited' : 'unlimited',
            'stock_qty'         => $request->input('stock_qty') !== '' ? (int) $request->input('stock_qty') : null,
            'payment_type'      => in_array($request->input('payment_type'), ['free','onetime','recurring'], true)
                                    ? $request->input('payment_type') : 'recurring',
            'setup_fee'         => (float) $request->input('setup_fee', 0),
            'setup_fee_currency'=> strtoupper((string) $request->input('setup_fee_currency', 'TRY')),
            'automation_module' => (string) $request->input('automation_module', ''),
            'server_id'         => (int) $request->input('server_id', 0) ?: null,
            'seo_title'         => (string) $request->input('seo_title', ''),
            'seo_description'   => (string) $request->input('seo_description', ''),
            'sort_order'        => (int) $request->input('sort_order', 0),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        if ($data['name'] === '') $errors[] = 'Ürün adı zorunludur.';
        if (mb_strlen($data['name']) > 191) $errors[] = 'Ürün adı çok uzun.';
        return $errors;
    }

    private function savePrices(int $productId, Request $request): void
    {
        $prices = (array) $request->input('prices', []);
        // Şekil: [{period:'monthly', source_currency:'TRY', source_price:'99.90', is_active:1}, ...]
        ProductRepository::replacePrices($productId, $prices);
    }

    private function servers(): array
    {
        try {
            return Connection::select(
                "SELECT id, name, hostname, panel, is_active
                 FROM hosting_servers
                 ORDER BY is_active DESC, name ASC"
            );
        } catch (\Throwable) {
            return [];
        }
    }
}
