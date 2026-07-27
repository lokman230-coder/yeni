<?php

declare(strict_types=1);

namespace App\Console;

use App\Core\Database\Connection;
use App\Services\Cron\CronScheduler;
use App\Services\Mail\Mailer;
use App\Services\Notification\NotificationService;

/**
 * Sistem cron komutlarını kaydeder (bootstrap zamanı).
 */
final class CronCommands
{
    public static function registerAll(): void
    {
        // Mail kuyruğunu işle - her dakika
        CronScheduler::register('mail:queue', function () {
            $r = Mailer::processQueue(50);
            return "sent={$r['sent']}, failed={$r['failed']}";
        });

        // Domain yenileme hatırlatma - günlük
        CronScheduler::register('domains:renewal-reminder', function () {
            try {
                $rows = Connection::select(
                    "SELECT d.*, c.email, c.first_name, c.last_name
                     FROM domains d
                     JOIN customers c ON c.id = d.customer_id
                     WHERE d.status = 'active'
                       AND d.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
                );
                $count = 0;
                foreach ($rows as $d) {
                    Mailer::send('domain_renewal_reminder', $d['email'], [
                        'name'   => $d['first_name'],
                        'domain' => $d['domain_name'],
                        'expiry' => $d['expiry_date'],
                    ]);
                    NotificationService::push('customer', (int)$d['customer_id'], 'domain_renewal',
                        'Domain süresi yaklaşıyor',
                        "{$d['domain_name']} · {$d['expiry_date']}",
                        '/panel', '⏰'
                    );
                    $count++;
                }
                return "queued={$count}";
            } catch (\Throwable $e) {
                return "error: " . $e->getMessage();
            }
        });

        // Hizmet süresi kontrolü - günlük
        CronScheduler::register('services:due-check', function () {
            try {
                $overdue = Connection::select(
                    "SELECT h.*, c.email FROM hosting_accounts h
                     JOIN customers c ON c.id = h.customer_id
                     WHERE h.status = 'active' AND h.next_due_date < CURDATE()"
                );
                return "overdue=" . count($overdue);
            } catch (\Throwable $e) { return "error: " . $e->getMessage(); }
        });

        // Kur güncelleme - saatlik (TCMB birincil, exchangerate.host fallback)
        CronScheduler::register('currency:update', function () {
            try {
                $r = \App\Services\Currency\CurrencyRateUpdater::updateAll();
                return sprintf('source=%s updated=%d skipped=%d errors=%d',
                    $r['source'], $r['updated'], $r['skipped'], count($r['errors'])
                );
            } catch (\Throwable $e) {
                return 'error: ' . $e->getMessage();
            }
        });

        // Hosting kullanım senkronizasyonu — 6 saatte bir (Faz 6e)
        CronScheduler::register('hosting:usage-update', function () {
            try {
                $r = \App\Modules\Hosting\Services\UsageSyncService::sync(200);
                return sprintf('accounts=%d updated=%d skipped=%d errors=%d',
                    $r['accounts'], $r['updated'], $r['skipped'], $r['errors']);
            } catch (\Throwable $e) {
                return 'error: ' . $e->getMessage();
            }
        });

        // Auth token cleanup — 7 günden eski süresi geçmiş tokenları temizle (Faz 6e)
        CronScheduler::register('auth:token-cleanup', function () {
            try {
                $n = \App\Services\Auth\AuthTokenService::cleanup();
                return "cleaned={$n}";
            } catch (\Throwable $e) {
                return 'error: ' . $e->getMessage();
            }
        });

        // Ödeme mutabakatı — 15 dakikada bir (Faz 6l — kritik güvenlik)
        CronScheduler::register('payment:reconcile', function () {
            try {
                $r = \App\Modules\Payment\Services\ReconciliationService::run(100);
                return sprintf('checked=%d reconciled=%d failed=%d errors=%d',
                    $r['checked'], $r['reconciled'], $r['failed'], count($r['errors']));
            } catch (\Throwable $e) {
                return 'error: ' . $e->getMessage();
            }
        });

        // Sepet terk edilme e-postası — saatte bir (Faz 6l)
        CronScheduler::register('cart:abandoned-reminder', function () {
            try {
                $r = \App\Modules\Cart\Services\AbandonedCartService::sendReminders();
                return sprintf('checked=%d sent=%d', $r['checked'], $r['sent']);
            } catch (\Throwable $e) {
                return 'error: ' . $e->getMessage();
            }
        });

        // Günlük otomatik DB backup + 30 gün öncesini sil (Faz 6m)
        CronScheduler::register('backup:daily', function () {
            try {
                $db = \App\Modules\Admin\Services\BackupService::createDbBackup();
                $pruned = \App\Modules\Admin\Services\BackupService::pruneOld(30);
                return sprintf('db=%s pruned=%d', $db['ok'] ? 'ok' : 'fail', $pruned);
            } catch (\Throwable $e) {
                return 'error: ' . $e->getMessage();
            }
        });

        // Cache temizleme - günlük
        CronScheduler::register('cache:clean', function () {
            $dir = AHO_ROOT . '/storage/cache';
            $count = 0;
            if (is_dir($dir)) {
                foreach (glob($dir . '/*') as $f) {
                    if (is_file($f) && (time() - filemtime($f)) > 86400) {
                        @unlink($f); $count++;
                    }
                }
            }
            return "cleaned={$count}";
        });

        // Rate limit dosyaları temizleme - saatlik
        CronScheduler::register('ratelimit:clean', function () {
            $dir = AHO_ROOT . '/storage/cache/rate-limits';
            $count = 0;
            if (is_dir($dir)) {
                foreach (glob($dir . '/*') as $f) {
                    if (is_file($f) && (time() - filemtime($f)) > 3600) {
                        @unlink($f); $count++;
                    }
                }
            }
            return "cleaned={$count}";
        });

        // API log retention (90 gün) - günlük
        CronScheduler::register('logs:cleanup', function () {
            $deleted = 0;
            try {
                Connection::query("DELETE FROM api_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
                $deleted += Connection::pdo()->query("SELECT ROW_COUNT()")->fetchColumn();

                // Cookie analytics — 12 ay
                Connection::query("DELETE FROM cookie_analytics_events WHERE created_at < DATE_SUB(NOW(), INTERVAL 12 MONTH)");

                // Mail queue — sent olan 30 gün eski
                Connection::query("DELETE FROM mail_queue WHERE status = 'sent' AND sent_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
            } catch (\Throwable $e) { return "error: " . $e->getMessage(); }
            return "api_logs_deleted={$deleted}";
        });

        // Cron log retention - günlük
        CronScheduler::register('cron:log-cleanup', function () {
            try {
                Connection::query("DELETE FROM cron_logs WHERE started_at < DATE_SUB(NOW(), INTERVAL 60 DAY)");
                return "ok";
            } catch (\Throwable $e) { return "error"; }
        });
    }
}
