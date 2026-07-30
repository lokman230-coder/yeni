<?php

declare(strict_types=1);

namespace App\Modules\License\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\License\LicenseService;

/**
 * Public API — müşteri sunucularındaki scriptler buraya HTTP çağrısı yapar.
 * URL: POST /api/license/verify
 */
final class LicenseApiController
{
    public function verify(Request $request): Response
    {
        $key        = (string) $request->input('license_key', '');
        $identifier = (string) $request->input('domain', $request->input('identifier', ''));
        $type       = (string) $request->input('type', 'domain');

        if ($key === '' || $identifier === '') {
            return Response::json(['valid' => false, 'error' => 'missing_params'], 400);
        }

        $result = LicenseService::verify($key, $identifier, $type);
        return Response::json($result, $result['valid'] ? 200 : 403);
    }

    /** Envato purchase code doğrulama */
    public function envato(Request $request): Response
    {
        $code = (string) $request->input('purchase_code', '');
        if ($code === '') {
            return Response::json(['valid' => false, 'error' => 'missing_purchase_code'], 400);
        }
        return Response::json(LicenseService::verifyEnvatoPurchase($code));
    }
}
