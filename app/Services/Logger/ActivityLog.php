<?php

declare(strict_types=1);

namespace App\Services\Logger;

use App\Core\Database\Connection;
use App\Core\SessionManager;

/**
 * Admin aktivite loglama helper'ı.
 *
 * ActivityLog::log('approved', 'payout', $payoutId, "Payout $amount ₺ ödendi işaretlendi");
 * Otomatik admin_id + admin_email + ip.
 */
final class ActivityLog
{
    public static function log(string $action, string $resourceType, ?int $resourceId = null, string $summary = '', array $meta = []): void
    {
        try {
            $adminId = SessionManager::get('admin_id');
            $adminEmail = SessionManager::get('admin_email');
            Connection::insert('admin_activity_logs', [
                'admin_id'      => $adminId ? (int) $adminId : null,
                'admin_email'   => $adminEmail ? (string) $adminEmail : null,
                'action'        => $action,
                'resource_type' => $resourceType,
                'resource_id'   => $resourceId,
                'summary'       => mb_substr($summary, 0, 500),
                'meta_json'     => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
                'ip'            => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Logger::warning('ActivityLog write failed: ' . $e->getMessage());
        }
    }
}
