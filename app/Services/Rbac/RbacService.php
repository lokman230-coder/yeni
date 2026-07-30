<?php

declare(strict_types=1);

namespace App\Services\Rbac;

use App\Core\Database\Connection;
use App\Core\SessionManager;

final class RbacService
{
    private static array $permissionCache = [];

    public static function can(string $permission): bool
    {
        $adminId = SessionManager::get('admin_id');
        if (!$adminId) {
            return false;
        }

        $role = SessionManager::get('admin_role');
        // Super admin her şeyi yapar
        if ($role === 'super_admin') {
            return true;
        }

        $perms = self::userPermissions((int) $adminId);
        return in_array($permission, $perms, true);
    }

    public static function cannot(string $permission): bool
    {
        return !self::can($permission);
    }

    public static function userPermissions(int $adminId): array
    {
        if (isset(self::$permissionCache[$adminId])) {
            return self::$permissionCache[$adminId];
        }

        try {
            $rows = Connection::select(
                "SELECT p.key FROM admins a
                 JOIN admin_role_permissions rp ON rp.role_id = a.role_id
                 JOIN admin_permissions p ON p.id = rp.permission_id
                 WHERE a.id = ?",
                [$adminId]
            );
            return self::$permissionCache[$adminId] = array_column($rows, 'key');
        } catch (\Throwable) {
            return self::$permissionCache[$adminId] = [];
        }
    }

    public static function authorize(string $permission): void
    {
        if (!self::can($permission)) {
            http_response_code(403);
            echo '<h1>403 - Yetkiniz yok</h1>';
            exit;
        }
    }
}
