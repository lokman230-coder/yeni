<?php

declare(strict_types=1);

namespace App\Modules\Import\Drivers;

use App\Modules\Import\Contracts\ImportSourceInterface;
use PDO;

/**
 * Blesta driver.
 *
 * Blesta tabloları:
 *   - clients + contacts (contacts.contact_type='primary' → müşteri bilgisi)
 *   - services   → hosting hesapları
 *   - invoices + invoice_lines
 *   - transactions → ödemeler
 *   - packages   → ürünler
 *   - support_tickets + support_replies
 */
final class BlestaDriver implements ImportSourceInterface
{
    public function id(): string { return 'blesta'; }
    public function label(): string { return 'Blesta 5.x'; }

    public function configFields(): array
    {
        return [
            'host'     => ['label' => 'MySQL Host',   'type' => 'text',     'required' => true,  'default' => '127.0.0.1'],
            'port'     => ['label' => 'Port',         'type' => 'number',   'required' => true,  'default' => 3306],
            'database' => ['label' => 'DB adı',       'type' => 'text',     'required' => true],
            'username' => ['label' => 'DB Kullanıcı', 'type' => 'text',     'required' => true],
            'password' => ['label' => 'DB Şifre',     'type' => 'password', 'required' => true],
        ];
    }

    public function testConnection(array $config): array
    {
        try {
            $pdo = $this->connect($config);
            $c = (int) $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
            return ['ok' => true, 'message' => "Bağlantı başarılı — $c müşteri bulundu."];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Hata: ' . $e->getMessage()];
        }
    }

    public function counts(array $config): array
    {
        try {
            $pdo = $this->connect($config);
            $safe = fn($t) => (int) @$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            return [
                'customers'      => $safe('clients'),
                'orders'         => $safe('transactions'),
                'invoices'       => $safe('invoices'),
                'products'       => $safe('packages'),
                'domains'        => 0,
                'hosting'        => $safe('services'),
                'tickets'        => $safe('support_tickets'),
                'servers'        => $safe('module_rows'),
                'registrars'     => $this->countRegistrars($pdo),
                'settings'       => $safe('settings'),
                'addons'         => 0,
                'custom_fields'  => 0,
                'full_config'    => array_sum([
                    $safe('settings'),
                    $safe('company_settings'),
                    $safe('client_group_settings'),
                    $safe('module_rows'),
                    $safe('module_row_meta'),
                    $safe('package_groups'),
                    $safe('currencies'),
                ]),
            ];
        } catch (\Throwable) {
            return array_fill_keys(['customers','orders','invoices','products','domains','hosting','tickets','servers','registrars','settings','addons','custom_fields','full_config'], 0);
        }
    }

    public function fetch(array $config, string $type, int $limit = 100, int $offset = 0): array
    {
        $pdo = $this->connect($config);
        return match ($type) {
            'customers'      => $this->fetchCustomers($pdo, $limit, $offset),
            'orders'         => $this->fetchTransactions($pdo, $limit, $offset),
            'invoices'       => $this->fetchInvoices($pdo, $limit, $offset),
            'products'       => $this->fetchPackages($pdo, $limit, $offset),
            'hosting'        => $this->fetchServices($pdo, $limit, $offset),
            'tickets'        => $this->fetchTickets($pdo, $limit, $offset),
            'servers'        => $this->fetchServersBlesta($pdo, $limit, $offset),
            'registrars'     => $this->fetchRegistrars($pdo, $limit, $offset),
            'settings'       => $this->fetchSettingsBlesta($pdo, $limit, $offset),
            'addons'         => [],
            'custom_fields'  => [],
            'full_config'    => $this->fetchFullConfig($pdo, $limit, $offset),
            default          => [],
        };
    }

    private function fetchServersBlesta(PDO $pdo, int $limit, int $offset): array
    {
        try {
            $rows = $pdo->query("SELECT * FROM module_rows ORDER BY id ASC LIMIT $limit OFFSET $offset")->fetchAll();
        } catch (\Throwable) { return []; }
        // Blesta module_rows'ta host bilgisi module_row_meta'da JSON — basit map
        return array_map(fn($r) => [
            'external_id' => (string) ($r['id'] ?? ''),
            'name'        => 'Blesta Server #' . ($r['id'] ?? ''),
            'hostname'    => '',
            'ip'          => '',
            'panel_type'  => 'cpanel',
            'port'        => 2087,
            'ssl'         => true,
            'username'    => '',
            'api_token'   => '',
            'is_active'   => true,
        ], $rows);
    }

    private function fetchSettingsBlesta(PDO $pdo, int $limit, int $offset): array
    {
        try {
            $rows = $pdo->query("SELECT `key`, value FROM settings ORDER BY `key` LIMIT $limit OFFSET $offset")->fetchAll();
        } catch (\Throwable) { return []; }
        $mapKey = [
            'company_name' => 'company.name',
            'admin_email'  => 'company.email',
            'address'      => 'company.address',
        ];
        return array_map(fn($r) => [
            'external_id' => $r['key'],
            'key'         => $mapKey[$r['key']] ?? ('blesta.' . $r['key']),
            'value'       => (string) $r['value'],
            'is_mapped'   => isset($mapKey[$r['key']]),
        ], $rows);
    }

    private function fetchFullConfig(PDO $pdo, int $limit, int $offset): array
    {
        $tables = [
            'settings' => ['key'],
            'company_settings' => ['company_id', 'key'],
            'client_group_settings' => ['client_group_id', 'key'],
            'module_rows' => ['id'],
            'module_row_meta' => ['module_row_id', 'key'],
            'package_groups' => ['id'],
            'currencies' => ['code'],
        ];

        $records = [];
        foreach ($tables as $table => $ids) {
            foreach ($this->fetchRawConfigTable($pdo, $table, $ids) as $record) {
                $records[] = $record;
            }
        }

        return array_slice($records, $offset, $limit);
    }

    private function fetchRawConfigTable(PDO $pdo, string $table, array $idColumns): array
    {
        try {
            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return [];
        }

        $records = [];
        foreach ($rows as $i => $row) {
            $idParts = [];
            foreach ($idColumns as $col) {
                if (isset($row[$col]) && (string)$row[$col] !== '') {
                    $idParts[] = (string)$row[$col];
                }
            }
            $externalId = $idParts ? implode(':', $idParts) : (string)($row['id'] ?? $i);
            $records[] = [
                'external_id' => $table . ':' . $externalId,
                'entity_type' => 'full_config',
                'source_table' => $table,
                'payload' => $row,
                'map' => $this->mapFullConfigRecord($table, $row),
            ];
        }
        return $records;
    }

    private function mapFullConfigRecord(string $table, array $row): array
    {
        if ($table === 'currencies') {
            $code = strtoupper((string)($row['code'] ?? ''));
            if ($code !== '') {
                return ['currency' => [
                    'currency' => $code,
                    'rate' => (float)($row['exchange_rate'] ?? $row['rate'] ?? 1),
                    'prefix' => (string)($row['prefix'] ?? $row['symbol'] ?? ''),
                    'suffix' => (string)($row['suffix'] ?? ''),
                ]];
            }
        }

        if (in_array($table, ['settings','company_settings','client_group_settings'], true)) {
            $key = (string)($row['key'] ?? '');
            $value = (string)($row['value'] ?? '');
            if ($key === '') return [];
            $map = [
                'company_name' => 'company.name',
                'admin_email' => 'company.email',
                'email' => 'company.email',
                'address' => 'company.address',
                'country' => 'company.country',
                'default_currency' => 'site.default_currency',
                'language' => 'site.default_locale',
                'hostname' => 'site.url',
                'logo' => 'site.logo',
            ];
            $target = $map[$key] ?? ('blesta.configuration.' . $key);
            return ['settings' => [[
                'key' => $target,
                'value' => $value,
                'type' => self::isSecretSetting($key) ? 'encrypted' : 'string',
                'group' => isset($map[$key]) ? (explode('.', $target)[0] ?? 'general') : 'blesta',
            ]]];
        }

        if (in_array($table, ['module_rows','module_row_meta'], true)) {
            $name = (string)($row['name'] ?? $row['module_row_id'] ?? $row['id'] ?? 'unknown');
            $settings = [];
            foreach ($row as $key => $value) {
                if (is_array($value) || is_object($value)) continue;
                $settings[] = [
                    'key' => 'blesta.module.' . $name . '.' . $key,
                    'value' => (string)$value,
                    'type' => self::isSecretSetting((string)$key) ? 'encrypted' : 'string',
                    'group' => 'hosting',
                ];
            }
            return $settings ? ['settings' => $settings] : [];
        }

        return [];
    }

    private static function isSecretSetting(string $key): bool
    {
        $k = strtolower($key);
        return str_contains($k, 'password')
            || str_contains($k, 'secret')
            || str_contains($k, 'token')
            || str_contains($k, 'key')
            || str_contains($k, 'hash');
    }

    private function countRegistrars(PDO $pdo): int
    {
        foreach (['registrars','modules'] as $table) { try { return (int)$pdo->query("SELECT COUNT(*) FROM `{$table}` WHERE name LIKE '%registrar%' OR class LIKE '%registrar%'")->fetchColumn(); } catch (\Throwable) {} }
        return 0;
    }

    private function fetchRegistrars(PDO $pdo, int $limit, int $offset): array
    {
        $rows=[];
        foreach (['registrars','modules'] as $table) { try { $rows=$pdo->query("SELECT * FROM `{$table}` WHERE name LIKE '%registrar%' OR class LIKE '%registrar%' LIMIT {$limit} OFFSET {$offset}")->fetchAll(PDO::FETCH_ASSOC); break; } catch (\Throwable) {} }
        return array_values(array_filter(array_map(function($r){ $name=(string)($r['name']??$r['label']??$r['class']??''); if($name==='')return null; return ['external_id'=>(string)($r['id']??$name),'name'=>$name,'label'=>$name,'settings'=>[]]; },$rows)));
    }

    private function connect(array $c): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $c['host'] ?? '127.0.0.1', (int) ($c['port'] ?? 3306), $c['database'] ?? '');
        return new PDO($dsn, $c['username'] ?? '', $c['password'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }

    private function fetchCustomers(PDO $pdo, int $limit, int $offset): array
    {
        $rows = $pdo->query(
            "SELECT c.id_code, c.status, co.first_name, co.last_name, co.email, co.company, co.address1,
                    co.city, co.country, co.zip, cs.value AS credit
             FROM clients c
             LEFT JOIN contacts co ON co.client_id = c.id AND co.contact_type = 'primary'
             LEFT JOIN client_settings cs ON cs.client_id = c.id AND cs.key = 'credits'
             ORDER BY c.id ASC LIMIT $limit OFFSET $offset"
        )->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($r) => [
            'external_id' => (int) $r['id_code'],
            'email'       => strtolower(trim((string) ($r['email'] ?? ''))),
            'first_name'  => (string) ($r['first_name'] ?? ''),
            'last_name'   => (string) ($r['last_name'] ?? ''),
            'company'     => (string) ($r['company'] ?? '') ?: null,
            'address'     => (string) ($r['address1'] ?? '') ?: null,
            'city'        => (string) ($r['city'] ?? '') ?: null,
            'country'     => (string) ($r['country'] ?? 'TR'),
            'postcode'    => (string) ($r['zip'] ?? '') ?: null,
            'balance'     => (float) ($r['credit'] ?? 0),
            'status'      => (string) ($r['status'] ?? 'active') === 'active' ? 'active' : 'suspended',
            'created_at'  => date('Y-m-d H:i:s'),
        ], $rows);
    }

    private function fetchTransactions(PDO $pdo, int $limit, int $offset): array
    {
        $rows = $pdo->query(
            "SELECT t.id, t.client_id, t.amount, t.currency, t.type, t.status, t.date_added,
                    co.email
             FROM transactions t
             LEFT JOIN contacts co ON co.client_id = t.client_id AND co.contact_type = 'primary'
             ORDER BY t.id ASC LIMIT $limit OFFSET $offset"
        )->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($r) => [
            'external_id'    => (int) $r['id'],
            'order_number'   => 'BLS-' . str_pad((string) $r['id'], 6, '0', STR_PAD_LEFT),
            'customer_email' => strtolower(trim((string) $r['email'])),
            'total'          => (float) $r['amount'],
            'currency'       => (string) ($r['currency'] ?? 'USD'),
            'status'         => $r['status'] === 'approved' ? 'paid' : 'pending',
            'created_at'     => (string) $r['date_added'],
        ], $rows);
    }

    private function fetchInvoices(PDO $pdo, int $limit, int $offset): array
    {
        $rows = $pdo->query(
            "SELECT i.id, i.id_code, i.client_id, i.date_billed, i.date_due, i.date_closed,
                    i.subtotal, i.total, i.paid, i.status, i.currency,
                    co.email
             FROM invoices i
             LEFT JOIN contacts co ON co.client_id = i.client_id AND co.contact_type = 'primary'
             ORDER BY i.id ASC LIMIT $limit OFFSET $offset"
        )->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($r) => [
            'external_id'    => (int) $r['id'],
            'invoice_number' => (string) ($r['id_code'] ?: 'BLS-' . $r['id']),
            'customer_email' => strtolower(trim((string) $r['email'])),
            'issue_date'     => (string) $r['date_billed'],
            'due_date'       => (string) $r['date_due'],
            'paid_at'        => !empty($r['date_closed']) ? (string) $r['date_closed'] : null,
            'subtotal'       => (float) $r['subtotal'],
            'total'          => (float) $r['total'],
            'paid_total'     => (float) ($r['paid'] ?? 0),
            'status'         => $r['status'] === 'active' && (float) $r['paid'] >= (float) $r['total'] ? 'paid' : 'unpaid',
        ], $rows);
    }

    private function fetchPackages(PDO $pdo, int $limit, int $offset): array
    {
        $rows = $pdo->query("SELECT id, name, description, module_id, status FROM packages ORDER BY id ASC LIMIT $limit OFFSET $offset")->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($r) => [
            'external_id' => (int) $r['id'],
            'name'        => (string) $r['name'],
            'description' => (string) ($r['description'] ?? ''),
            'type'        => 'hosting',
            'status'      => $r['status'] === 'active' ? 'active' : 'hidden',
        ], $rows);
    }

    private function fetchServices(PDO $pdo, int $limit, int $offset): array
    {
        $rows = $pdo->query(
            "SELECT s.id, s.client_id, s.package_id, s.status, s.date_added, s.date_renews,
                    co.email
             FROM services s
             LEFT JOIN contacts co ON co.client_id = s.client_id AND co.contact_type = 'primary'
             ORDER BY s.id ASC LIMIT $limit OFFSET $offset"
        )->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($r) => [
            'external_id'    => (int) $r['id'],
            'domain'         => '',  // Blesta domain field ayrı service_fields'da
            'customer_email' => strtolower(trim((string) $r['email'])),
            'package_id'     => (int) $r['package_id'],
            'status'         => match ((string) $r['status']) {
                'active'    => 'active',
                'suspended' => 'suspended',
                'cancelled' => 'terminated',
                default     => 'pending',
            },
            'next_due_date'  => (string) ($r['date_renews'] ?? ''),
            'created_at'     => (string) $r['date_added'],
        ], $rows);
    }

    private function fetchTickets(PDO $pdo, int $limit, int $offset): array
    {
        $rows = $pdo->query(
            "SELECT t.id, t.code, t.client_id, t.summary, t.priority, t.status, t.date_added,
                    co.email
             FROM support_tickets t
             LEFT JOIN contacts co ON co.client_id = t.client_id AND co.contact_type = 'primary'
             ORDER BY t.id ASC LIMIT $limit OFFSET $offset"
        )->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $r) {
            $stmt = $pdo->prepare("SELECT type, details, date_added FROM support_replies WHERE ticket_id = ? ORDER BY id ASC");
            $stmt->execute([$r['id']]);
            $result[] = [
                'external_id'    => (int) $r['id'],
                'ticket_number'  => (string) ($r['code'] ?: 'BLS-' . $r['id']),
                'customer_email' => strtolower(trim((string) $r['email'])),
                'subject'        => (string) $r['summary'],
                'status'         => match ((string) $r['status']) {
                    'open','awaiting_reply' => 'open',
                    'in_progress' => 'open',
                    'on_hold'     => 'on_hold',
                    'closed'      => 'closed',
                    default       => 'open',
                },
                'priority'       => match ((string) $r['priority']) {
                    'low','emergency' => strtolower((string) $r['priority']) === 'emergency' ? 'urgent' : 'low',
                    'medium','high'   => (string) $r['priority'],
                    'critical'        => 'urgent',
                    default           => 'medium',
                },
                'created_at'     => (string) $r['date_added'],
                'replies'        => array_map(fn($rp) => [
                    'author_type' => $rp['type'] === 'reply' ? 'admin' : ($rp['type'] === 'note' ? 'admin' : 'customer'),
                    'message'     => (string) $rp['details'],
                    'created_at'  => (string) $rp['date_added'],
                ], $stmt->fetchAll(PDO::FETCH_ASSOC)),
            ];
        }
        return $result;
    }
}
