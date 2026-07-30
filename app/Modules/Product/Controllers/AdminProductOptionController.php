<?php

declare(strict_types=1);

namespace App\Modules\Product\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Modules\Product\Services\OptionService;
use App\Modules\Product\Services\ProductRepository;

final class AdminProductOptionController
{
    public function index(Request $request): Response
    {
        $productId = (int) $request->query('product_id', 0) ?: null;
        $options = OptionService::allForAdmin($productId);
        $products = ProductRepository::all([]);
        return Response::html((new View())->render('product::admin.options.index', [
            'title'     => 'Paket Opsiyonları',
            'options'   => $options,
            'products'  => $products,
            'productId' => $productId,
        ]));
    }

    public function create(Request $request): Response
    {
        $products = ProductRepository::all([]);
        return Response::html((new View())->render('product::admin.options.form', [
            'title'    => 'Yeni Paket Opsiyonu',
            'option'   => null,
            'products' => $products,
        ]));
    }

    public function edit(Request $request): Response
    {
        $id = (int) $request->param('id');
        $option = OptionService::find($id);
        if (!$option) {
            return Response::notFound();
        }
        $products = ProductRepository::all([]);
        return Response::html((new View())->render('product::admin.options.form', [
            'title'    => 'Opsiyon Düzenle · ' . $option['name'],
            'option'   => $option,
            'products' => $products,
        ]));
    }

    public function store(Request $request): Response
    {
        $data = $request->all();
        $values = $data['values'] ?? [];
        $id = OptionService::save($data, is_array($values) ? $values : []);
        SessionManager::flash('success', 'Opsiyon kaydedildi.');
        return Response::redirect('/admin/paket-opsiyonlari/' . $id . '/duzenle');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $data = $request->all();
        $values = $data['values'] ?? [];
        OptionService::save($data, is_array($values) ? $values : [], $id);
        SessionManager::flash('success', 'Opsiyon güncellendi.');
        return Response::redirect('/admin/paket-opsiyonlari/' . $id . '/duzenle');
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->param('id');
        OptionService::delete($id);
        SessionManager::flash('success', 'Opsiyon silindi.');
        return Response::redirect('/admin/paket-opsiyonlari');
    }
}
