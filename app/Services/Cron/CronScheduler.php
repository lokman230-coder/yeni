<?php

declare(strict_types=1);

namespace App\Services\Cron;

use App\Core\Database\Connection;
use App\Services\Logger\Logger;

/**
 * Basit cron scheduler.
 * Dış crontab dakikada bir `php console cron:run` çağırır.
 * Bu servis DB'deki cron_schedules'a bakıp süresi gelenleri çalıştırır.
 */
final class CronScheduler
{
    /** @var array<string, callable> Command adı → PHP callable */
    private static array $commands = [];

    public static function register(string $command, callable $handler): void
    {
        self::$commands[$command] = $handler;
    }

    /** Tüm zamanlanmış cron'ları kontrol et; süresi geleni çalıştır. */
    public static function run(): array
    {
        $now = time();
        try {
            $schedules = Connection::select(
                "SELECT * FROM cron_schedules WHERE is_active = 1 AND (next_run_at IS NULL OR next_run_at <= NOW())"
            );
        } catch (\Throwable) {
            return ['ran' => 0];
        }

        $ran = 0;
        foreach ($schedules as $s) {
            $cmd = $s['command'];
            if (!isset(self::$commands[$cmd])) {
                Logger::warning("Cron command bulunamadı: {$cmd}");
                continue;
            }

            $start = microtime(true);
            Connection::update('cron_schedules', ['last_status' => 'running'], 'id = ?', [$s['id']]);

            try {
                Connection::insert('cron_logs', [
                    'command' => $cmd,
                    'status'  => 'running',
                ]);
                $logId = (int) Connection::pdo()->lastInsertId();

                $output = call_user_func(self::$commands[$cmd]);
                $duration = (int) ((microtime(true) - $start) * 1000);

                Connection::update('cron_logs', [
                    'status'      => 'success',
                    'finished_at' => date('Y-m-d H:i:s'),
                    'output'      => is_string($output) ? mb_substr($output, 0, 4000) : json_encode($output),
                ], 'id = ?', [$logId]);

                Connection::update('cron_schedules', [
                    'last_run_at'      => date('Y-m-d H:i:s'),
                    'next_run_at'      => self::calcNextRun($s['expression']),
                    'last_duration_ms' => $duration,
                    'last_status'      => 'success',
                ], 'id = ?', [$s['id']]);

                $ran++;
            } catch (\Throwable $e) {
                Logger::error("Cron '{$cmd}' failed: " . $e->getMessage());
                Connection::update('cron_schedules', [
                    'last_run_at' => date('Y-m-d H:i:s'),
                    'next_run_at' => self::calcNextRun($s['expression']),
                    'last_status' => 'failed',
                ], 'id = ?', [$s['id']]);
            }
        }
        return ['ran' => $ran];
    }

    /**
     * Sonraki çalıştırma zamanını hesapla.
     * Basit destek: her N dakika, saatlik, günlük.
     */
    private static function calcNextRun(string $expr): string
    {
        // Basit patternler: "* * * * *" (her dakika), "*/5 * * * *" (5 dk), "0 * * * *" (saat başı), "0 0 * * *" (günlük)
        $now = time();
        return match (true) {
            $expr === '* * * * *'         => date('Y-m-d H:i:s', $now + 60),
            $expr === '*/5 * * * *'       => date('Y-m-d H:i:s', $now + 300),
            $expr === '*/15 * * * *'      => date('Y-m-d H:i:s', $now + 900),
            $expr === '*/30 * * * *'      => date('Y-m-d H:i:s', $now + 1800),
            $expr === '0 * * * *'         => date('Y-m-d H:00:00', strtotime('+1 hour')),
            $expr === '0 0 * * *'         => date('Y-m-d 00:00:00', strtotime('+1 day')),
            default                       => date('Y-m-d H:i:s', $now + 3600),
        };
    }

    /** Bir cron zamanlamasını DB'ye kaydet. */
    public static function schedule(string $command, string $description, string $expression = '*/5 * * * *'): void
    {
        $exists = Connection::selectOne("SELECT id FROM cron_schedules WHERE command = ?", [$command]);
        if ($exists) {
            Connection::update('cron_schedules', [
                'description' => $description,
                'expression'  => $expression,
                'is_active'   => 1,
                'updated_at'  => date('Y-m-d H:i:s'),
            ], 'id = ?', [$exists['id']]);
        } else {
            Connection::insert('cron_schedules', [
                'command'     => $command,
                'description' => $description,
                'expression'  => $expression,
                'is_active'   => 1,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public static function all(): array
    {
        try { return Connection::select("SELECT * FROM cron_schedules ORDER BY command"); }
        catch (\Throwable) { return []; }
    }
}
