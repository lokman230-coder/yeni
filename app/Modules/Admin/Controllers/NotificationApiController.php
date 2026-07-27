<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\Auth\AuthService;
use App\Services\Notification\NotificationService;

final class NotificationApiController
{
    public function listAdmin(Request $request): Response
    {
        $admin = AuthService::admin();
        return Response::json([
            'ok' => true,
            'unread' => NotificationService::unreadCount('admin', (int)$admin['id']),
            'items'  => NotificationService::forUser('admin', (int)$admin['id'], 20),
        ]);
    }

    public function listCustomer(Request $request): Response
    {
        $c = AuthService::customer();
        return Response::json([
            'ok' => true,
            'unread' => NotificationService::unreadCount('customer', (int)$c['id']),
            'items'  => NotificationService::forUser('customer', (int)$c['id'], 20),
        ]);
    }

    public function markRead(Request $request): Response
    {
        $id = (int) $request->param('id');
        $admin = AuthService::admin();
        $customer = AuthService::customer();
        if ($admin)   NotificationService::markRead($id, 'admin', (int)$admin['id']);
        elseif ($customer) NotificationService::markRead($id, 'customer', (int)$customer['id']);
        return Response::json(['ok' => true]);
    }

    public function markAllRead(Request $request): Response
    {
        $admin = AuthService::admin();
        $customer = AuthService::customer();
        if ($admin)   NotificationService::markAllRead('admin', (int)$admin['id']);
        elseif ($customer) NotificationService::markAllRead('customer', (int)$customer['id']);
        return Response::json(['ok' => true]);
    }
}
