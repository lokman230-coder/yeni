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
            'serverGroups' => $this->serverGroups(),
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
            'serverGroups' => $this->serverGroups(),
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
            'server_group_id'   => (int) $request->input('server_group_id', 0) ?: null,
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

    private function serverGroups(): array
    {
        try {
            Connection::pdo()->exec(
                "CREATE TABLE IF NOT EXISTS `server_groups` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `name` VARCHAR(120) NOT NULL,
                    `slug` VARCHAR(120) NOT NULL,
                    `fill_type` ENUM('fill_first','round_robin','least_used') NOT NULL DEFAULT 'least_used',
                    `created_at` DATETIME NULL,
                    `updated_at` DATETIME NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `server_groups_slug_unique` (`slug`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );

            $groups = Connection::select("SELECT id, name, slug, fill_type FROM server_groups ORDER BY name ASC");
            if ($groups) {
                return $groups;
            }

            $serverGroups = Connection::select(
                "SELECT DISTINCT server_group
                 FROM hosting_servers
                 WHERE server_group IS NOT NULL AND server_group != ''
                 ORDER BY server_group ASC"
            );
            foreach ($serverGroups as $row) {
                $slug = trim((string) ($row['server_group'] ?? ''));
                if ($slug === '') {
                    continue;
                }
                Connection::insert('server_groups', [
                    'name' => ucwords(str_replace(['-', '_'], ' ', $slug)),
                    'slug' => $slug,
                    'fill_type' => 'least_used',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            return Connection::select("SELECT id, name, slug, fill_type FROM server_groups ORDER BY name ASC");
        } catch (\Throwable) {
            return [];
        }
    }
}
