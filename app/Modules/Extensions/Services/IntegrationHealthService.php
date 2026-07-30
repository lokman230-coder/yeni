<?php
declare(strict_types=1);

namespace App\Modules\Extensions\Services;

use App\Core\Database\Connection;
use App\Services\Settings\SettingsManager;

final class IntegrationHealthService
{
    public static function report(): array
    {
        $items = [
            self::settingsCheck('PayTR', 'payment', ['paytr.merchant_id', 'paytr.merchant_key', 'paytr.merchant_salt']),
            self::settingsCheck('iyzico', 'payment', ['iyzico.api_key', 'iyzico.secret_key']),
            self::settingsCheck('Papara', 'payment', ['papara.api_key']),
            self::shopierCheck(),
            self::settingsCheck('SMTP Mail', 'mail', ['mail.host', 'mail.port', 'mail.username', 'mail.password', 'mail.from_address']),
            self::smsCheck(),
            self::aiCheck(),
            self::mobileBuildCheck(),
            self::s3Check(),
            self::settingsCheck('Sentry/GlitchTip', 'security', ['security.sentry_dsn'], true),
            self::recaptchaCheck(),
            self::einvoiceCheck(),
            self::registrarCheck(),
            self::hostingCheck(),
            self::tableCheck('Live Chat', 'extensions', ['live_chat_conversations', 'live_chat_messages']),
            self::tableCheck('Form Builder', 'extensions', ['form_builder_forms', 'form_builder_submissions']),
            self::tableCheck('Popup Builder', 'extensions', ['popup_builder_popups', 'popup_builder_events']),
            self::tableCheck('Marketplace Delivery', 'marketplace', ['marketplace_files', 'marketplace_purchases', 'marketplace_download_tokens']),
            self::tableCheck('VPS Provision Queue', 'hosting', ['vps_provisioning_jobs']),
        ];

        $summary = ['ok' => 0, 'warning' => 0, 'missing' => 0];
        foreach ($items as $item) {
            $summary[$item['status']] = ($summary[$item['status']] ?? 0) + 1;
        }

        return [
            'generated_at' => date('Y-m-d H:i:s'),
            'summary' => $summary,
            'items' => $items,
        ];
    }

    private static function settingsCheck(string $name, string $group, array $keys, bool $optional = false): array
    {
        $missing = self::missingSettings($keys);
        if (!$missing) {
            return self::item($name, $group, 'ok', 'Required settings are configured.', []);
        }

        return self::item(
            $name,
            $group,
            $optional ? 'warning' : 'missing',
            $optional ? 'Optional integration is not configured.' : 'Required settings are missing.',
            $missing
        );
    }

    private static function shopierCheck(): array
    {
        if (SettingsManager::get('shopier.pat', '', 'SHOPIER_PAT') !== '') {
            return self::item('Shopier', 'payment', 'ok', 'Shopier PAT is configured.', []);
        }

        $missing = self::missingSettings(['shopier.api_key', 'shopier.api_secret']);
        return $missing
            ? self::item('Shopier', 'payment', 'missing', 'Shopier PAT or legacy API settings are missing.', ['shopier.pat'])
            : self::item('Shopier', 'payment', 'ok', 'Legacy Shopier API settings are configured.', []);
    }

    private static function smsCheck(): array
    {
        $driver = (string) SettingsManager::get('sms.driver', 'log');
        if ($driver === 'log' || $driver === '') {
            return self::item('SMS', 'sms', 'warning', 'SMS is in log/test mode.', ['Choose NetGSM, IletiMerkezi or Twilio for production.']);
        }

        $required = match ($driver) {
            'netgsm' => ['sms.netgsm_user', 'sms.netgsm_password', 'sms.netgsm_header'],
            'iletimerkezi' => ['sms.iletimerkezi_user', 'sms.iletimerkezi_password', 'sms.iletimerkezi_header'],
            'twilio' => ['sms.twilio_sid', 'sms.twilio_token', 'sms.twilio_from'],
            default => [],
        };

        $missing = self::missingSettings($required);
        return $missing
            ? self::item('SMS', 'sms', 'missing', "SMS driver '{$driver}' has missing settings.", $missing)
            : self::item('SMS', 'sms', 'ok', "SMS driver '{$driver}' is configured.", []);
    }

    private static function aiCheck(): array
    {
        $provider = (string) SettingsManager::get('ai.provider', 'heuristic');
        if ($provider === 'heuristic' || $provider === '') {
            return self::item('AI Provider', 'ai', 'warning', 'Heuristic provider works without API, but production AI is not configured.', ['Set an AI provider and API key.']);
        }

        $required = match ($provider) {
            'openai' => ['ai.api_key'],
            'gemini' => ['ai.gemini_api_key'],
            'claude' => ['ai.claude_api_key'],
            'deepseek' => ['ai.deepseek_api_key'],
            'mistral' => ['ai.mistral_api_key'],
            default => [],
        };

        $missing = self::missingSettings($required);
        return $missing
            ? self::item('AI Provider', 'ai', 'missing', "AI provider '{$provider}' has missing settings.", $missing)
            : self::item('AI Provider', 'ai', 'ok', "AI provider '{$provider}' is configured.", []);
    }

    private static function mobileBuildCheck(): array
    {
        $missing = self::missingSettings(['mobile.github_owner', 'mobile.github_repo', 'mobile.github_token', 'mobile.github_workflow', 'mobile.github_branch']);
        return $missing
            ? self::item('Mobile Build', 'builder', 'missing', 'GitHub Actions build settings are missing.', $missing)
            : self::item('Mobile Build', 'builder', 'ok', 'GitHub Actions build settings are configured.', []);
    }

    private static function s3Check(): array
    {
        $enabled = SettingsManager::get('backup.s3_enabled', false);
        $missing = self::missingSettings(['backup.s3_bucket', 'backup.s3_region', 'backup.s3_access_key', 'backup.s3_secret_key']);
        if (!$enabled && $missing) {
            return self::item('Off-site Backup', 'backup', 'warning', 'S3 backup is not enabled.', ['Enable backup.s3_enabled and fill S3 settings.']);
        }

        return $missing
            ? self::item('Off-site Backup', 'backup', 'missing', 'S3 backup is enabled but settings are incomplete.', $missing)
            : self::item('Off-site Backup', 'backup', 'ok', 'S3 backup settings are configured.', []);
    }

    private static function recaptchaCheck(): array
    {
        $enabled = SettingsManager::get('security.recaptcha_enabled', false);
        $missing = self::missingSettings(['security.recaptcha_site_key', 'security.recaptcha_secret_key']);
        if (!$enabled) {
            return self::item('reCAPTCHA', 'security', 'warning', 'reCAPTCHA is disabled.', ['Enable security.recaptcha_enabled for public forms if needed.']);
        }

        return $missing
            ? self::item('reCAPTCHA', 'security', 'missing', 'reCAPTCHA is enabled but keys are missing.', $missing)
            : self::item('reCAPTCHA', 'security', 'ok', 'reCAPTCHA settings are configured.', []);
    }

    private static function einvoiceCheck(): array
    {
        $provider = (string) SettingsManager::get('einvoice.provider', 'noop');
        if ($provider === 'noop' || $provider === '') {
            return self::item('E-Invoice', 'einvoice', 'warning', 'E-invoice provider is disabled.', ['Select Uyumsoft if e-invoice is required.']);
        }

        $missing = self::missingSettings(['einvoice.uyumsoft_username', 'einvoice.uyumsoft_password']);
        if (!extension_loaded('soap')) {
            $missing[] = 'php.ext.soap';
        }

        return $missing
            ? self::item('E-Invoice', 'einvoice', 'missing', "E-invoice provider '{$provider}' is incomplete.", $missing)
            : self::item('E-Invoice', 'einvoice', 'ok', "E-invoice provider '{$provider}' is configured.", []);
    }

    private static function registrarCheck(): array
    {
        try {
            $row = Connection::selectOne("SELECT name, driver FROM domain_registrars WHERE is_active = 1 ORDER BY is_default DESC, id ASC LIMIT 1");
            if (!$row) {
                return self::item('Domain Registrar', 'registrar', 'missing', 'No active registrar found.', ['Add an active registrar in Domain Center.']);
            }

            $driver = (string) ($row['driver'] ?? 'manual');
            $status = $driver === 'manual' ? 'warning' : 'ok';
            $message = $driver === 'manual' ? 'Manual registrar is active.' : "Registrar '{$driver}' is active.";

            return self::item('Domain Registrar', 'registrar', $status, $message, $driver === 'manual' ? ['Configure an API registrar for automated domain operations.'] : []);
        } catch (\Throwable $e) {
            return self::item('Domain Registrar', 'registrar', 'missing', 'Registrar tables are not ready.', [$e->getMessage()]);
        }
    }

    private static function hostingCheck(): array
    {
        try {
            $row = Connection::selectOne("SELECT name, panel FROM hosting_servers WHERE is_active = 1 ORDER BY current_accounts ASC, id ASC LIMIT 1");
            if (!$row) {
                return self::item('Hosting Provisioning', 'hosting', 'missing', 'No active hosting server found.', ['Add an active hosting server.']);
            }

            $panel = (string) ($row['panel'] ?? 'manual');
            $status = $panel === 'manual' ? 'warning' : 'ok';
            $message = $panel === 'manual' ? 'Manual hosting server is active.' : "Hosting panel '{$panel}' is active.";

            return self::item('Hosting Provisioning', 'hosting', $status, $message, $panel === 'manual' ? ['Configure cPanel/Plesk/DirectAdmin automation for production.'] : []);
        } catch (\Throwable $e) {
            return self::item('Hosting Provisioning', 'hosting', 'missing', 'Hosting server tables are not ready.', [$e->getMessage()]);
        }
    }

    private static function tableCheck(string $name, string $group, array $tables): array
    {
        $missing = [];
        foreach ($tables as $table) {
            if (!self::tableExists($table)) {
                $missing[] = $table;
            }
        }

        return $missing
            ? self::item($name, $group, 'missing', 'Database migration is not applied.', $missing)
            : self::item($name, $group, 'ok', 'Database tables are ready.', []);
    }

    private static function tableExists(string $table): bool
    {
        try {
            $row = Connection::selectOne(
                "SELECT COUNT(*) c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?",
                [$table]
            );
            return (int) ($row['c'] ?? 0) > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    private static function missingSettings(array $keys): array
    {
        $missing = [];
        foreach ($keys as $key) {
            $value = SettingsManager::get($key);
            if ($value === null || $value === '' || $value === false) {
                $missing[] = $key;
            }
        }
        return $missing;
    }

    private static function item(string $name, string $group, string $status, string $message, array $actions): array
    {
        return [
            'name' => $name,
            'group' => $group,
            'status' => $status,
            'message' => $message,
            'actions' => $actions,
        ];
    }
}
