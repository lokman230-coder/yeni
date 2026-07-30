<?php

declare(strict_types=1);

namespace App\Modules\Hosting\Services;

use App\Services\Settings\SettingsManager;

final class AccountCronService
{
    public static function enabled(): bool
    {
        return (bool) SettingsManager::get('hosting.auto_cron_enabled', '1');
    }

    /**
     * @return array<int,array{minute:string,hour:string,day:string,month:string,weekday:string,command:string}>
     */
    public static function jobs(string $username, string $domain): array
    {
        $home = '/home/' . $username;
        $raw = (string) SettingsManager::get('hosting.auto_cron_jobs', '');
        $jobs = [];
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $jobs = $decoded;
            }
        }

        if (!$jobs) {
            $jobs = [[
                'minute' => '*',
                'hour' => '*',
                'day' => '*',
                'month' => '*',
                'weekday' => '*',
                'command' => '/usr/local/bin/php -q {home}/public_html/console cron:run >/dev/null 2>&1',
            ]];
        }

        $out = [];
        foreach ($jobs as $job) {
            if (!is_array($job)) continue;
            $command = (string)($job['command'] ?? '');
            if ($command === '') continue;
            $out[] = [
                'minute' => self::field($job['minute'] ?? '*'),
                'hour' => self::field($job['hour'] ?? '*'),
                'day' => self::field($job['day'] ?? '*'),
                'month' => self::field($job['month'] ?? '*'),
                'weekday' => self::field($job['weekday'] ?? '*'),
                'command' => strtr($command, [
                    '{username}' => $username,
                    '{domain}' => $domain,
                    '{home}' => $home,
                ]),
            ];
        }

        return $out;
    }

    private static function field(mixed $value): string
    {
        $v = trim((string)$value);
        return preg_match('/^[0-9*,\/-]+$/', $v) ? $v : '*';
    }
}
