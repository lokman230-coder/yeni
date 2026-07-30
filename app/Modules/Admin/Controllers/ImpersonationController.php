<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Services\Auth\ImpersonationService;

final class ImpersonationController
{
    public function stop(Request $request): Response
    {
        $state = ImpersonationService::currentState();
        ImpersonationService::stop();
        SessionManager::flash('success', 'Admin oturumuna geri dönüldü.');

        // Admin panele geri dön
        $target = '/admin';
        if ($state && !empty($state['customer_id'])) {
            $target = '/admin/musteriler/' . (int)$state['customer_id'];
        }
        return Response::redirect($target);
    }
}
