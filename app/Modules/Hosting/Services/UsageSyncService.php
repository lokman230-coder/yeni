<?php

declare(strict_types=1);

namespace App\Modules\Hosting\Services;

use App\Core\Database\Connection;
use App\Modules\Hosting\HostingManager;
use App\Services\Logger\Logger;

/**
 * Hosting hesap kullanımını (disk + bandwidth) senkronize eden servis.
 *
 * Cron: hosting:usage-update  (6 saatte bir çalışır)
 *   → Aktif hostinglere gider, driver->getUsage(username) çağırır
 *   → hosting_accounts.disk_usage_mb / bandwidth_usage_mb güncellenir
 *
 * Driver getUsage dönüşü: ['disk_mb' => int, 'bandwidth_mb' => int, 'status' => 'active']
 */
final class UsageSyncService
{
    /** @return array{updated:int,skipped:int,errors:int,accounts:int} */
    public static function sync(int $limit = 200): array
    {
        $accounts = Connection::select(
            "SELECT * FROM hosting_accounts
             WHERE status = 'active' AND server_id IS NOT NULL AND username IS NOT NULL
             ORDER BY COALESCE(usage_updated_at, '1970-01-01') ASC
             LIMIT ?",
            [$limit]
        );

        $updated = 0; $skipped = 0; $errors = 0;

        foreach ($accounts as $a) {
            try {
                $driver = HostingManager::forServer((int) $a['server_id']);
                $r = $driver->getUsage((string) $a['username']);
                $disk = $r['disk_mb']      ?? null;
                $band = $r['bandwidth_mb'] ?? null;
                $panelStatus = $r['status'] ?? null;

                if ($disk === null && $band === null) {
                    $skipped++;
                    continue;
                }

                $upd = ['usage_updated_at' => date('Y-m-d H:i:s')];
                if ($disk !== null) $upd['disk_usage_mb'] = (int) $disk;
                if ($band !== null) $upd['bandwidth_usage_mb'] = (int) $band;

                // Panelde suspended görünüyorsa senkron et
                if ($panelStatus === 'suspended' && $a['status'] !== 'suspended') {
                    $upd['status'] = 'suspended';
                    $upd['suspended_at'] = date('Y-m-d H:i:s');
                }

                Connection::update('hosting_accounts', $upd, 'id = ?', [$a['id']]);

                // Snapshot — günlük kayıt (aynı gün için UNIQUE, yenilerse update)
                try {
                    $today = date('Y-m-d');
                    $existing = Connection::selectOne(
                        "SELECT id FROM hosting_usage_snapshots WHERE account_id = ? AND snap_date = ?",
                        [$a['id'], $today]
                    );
                    if ($existing) {
                        Connection::update('hosting_usage_snapshots', [
                            'disk_mb'      => $disk,
                            'bandwidth_mb' => $band,
                        ], 'id = ?', [$existing['id']]);
                    } else {
                        Connection::insert('hosting_usage_snapshots', [
                            'account_id'   => (int) $a['id'],
                            'snap_date'    => $today,
                            'disk_mb'      => $disk,
                            'bandwidth_mb' => $band,
                        ]);
                    }
                } catch (\Throwable) {}

                $updated++;
            } catch (\Throwable $e) {
                $errors++;
                Logger::warning('Usage sync fail', ['account' => $a['id'], 'err' => $e->getMessage()]);
            }
        }

        Logger::info('Usage sync tamamlandı', ['accounts' => count($accounts), 'updated' => $updated, 'errors' => $errors]);
        return ['updated' => $updated, 'skipped' => $skipped, 'errors' => $errors, 'accounts' => count($accounts)];
    }
}
