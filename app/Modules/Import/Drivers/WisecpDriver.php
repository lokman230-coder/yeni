<?php

declare(strict_types=1);

namespace App\Modules\Import\Drivers;

use App\Modules\Import\Contracts\ImportSourceInterface;
use PDO;

/**
 * WISECP (Türk hosting yönetim paneli) driver.
 * WISECP tabloları prefix'siz genelde.
 *
 * Ana tablolar:
 *   - clients          → müşteriler
 *   - orders           → siparişler
 *   - invoices         → faturalar
 *   - products         → ürünler
 *   - clients_products → aktif hizmetler
 *   - domains_registered → domainler
 *   - tickets          → destek
 */
final class WisecpDriver implements ImportSourceInterface
{
    public function id(): string { return 'wisecp'; }
    public function label(): string { return 'WISECP'; }

    public function configFields(): array
    {
        return [
            'host'     => ['label' => 'MySQL Host',   'type' => 'text',     'required' => true,  'default' => '127.0.0.1'],
            'port'     => ['label' => 'Port',         'type' => 'number',   'required' => true,  'default' => 3306],
            'database' => ['label' => 'DB adı',       'type' => 'text',     'required' => true,  'hint' => 'WISECP DB adı'],
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
                'orders'         => $safe('orders'),
                'invoices'       => $safe('invoices'),
                'products'       => $safe('products'),
                'domains'        => $safe('domains_registered'),
                'hosting'        => $safe('clients_products'),
                'tickets'        => $safe('tickets'),
                'servers'        => $safe('servers'),
                'registrars'     => $safe('registrars'),
                'settings'       => $safe('settings'),
                'addons'         => $this->countFirstExisting($pdo, ['addons','product_addons','product_options']),
                'custom_fields'  => $this->countFirstExisting($pdo, ['custom_fields','product_custom_fields','product_fields']),
                'full_config'    => array_sum([
                    $safe('settings'),
                    $safe('payment_gateways'),
                    $safe('payment_methods'),
                    $safe('registrars'),
                    $safe('servers'),
                    $safe('currencies'),
                    $safe('mail_templates'),
                    $safe('email_templates'),
                    $safe('product_groups'),
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
            'orders'         => $this->fetchOrders($pdo, $limit, $offset),
            'invoices'       => $this->fetchInvoices($pdo, $limit, $offset),
            'products'       => $this->fetchProducts($pdo, $limit, $offset),
            'domains'        => $this->fetchDomains($pdo, $limit, $offset),
            'hosting'        => $this->fetchHosting($pdo, $limit, $offset),
            'tickets'        => $this->fetchTickets($pdo, $limit, $offset),
            'servers'        => $this->fetchServersGeneric($pdo, 'servers', $limit, $offset),
            'registrars'     => $this->fetchRegistrarsGeneric($pdo, $limit, $offset),
            'settings'       => $this->fetchSettingsGeneric($pdo, 'settings', $limit, $offset),
            'addons'         => $this->fetchFlexibleAddons($pdo, $limit, $offset),
            'custom_fields'  => $this->fetchFlexibleFields($pdo, $limit, $offset),
            'full_config'    => $this->fetchFullConfig($pdo, $limit, $offset),
            default          => [],
        };
    }

    private function countFirstExisting(PDO $pdo, array $tables): int
    {
        foreach ($tables as $table) { try { return (int)$pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn(); } catch (\Throwable) {} }
        return 0;
    }

    private function firstRows(PDO $pdo, array $tables, int $limit, int $offset): array
    {
        foreach ($tables as $table) { try { return $pdo->query("SELECT * FROM `{$table}` ORDER BY id ASC LIMIT {$limit} OFFSET {$offset}")->fetchAll(PDO::FETCH_ASSOC); } catch (\Throwable) {} }
        return [];
    }

    private function pick(array $row, array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') return $row[$key];
        return $default;
    }

    private function fetchFlexibleAddons(PDO $pdo, int $limit, int $offset): array
    {
        $rows=$this->firstRows($pdo,['addons','product_addons','product_options'],$limit,$offset); $out=[];
        foreach($rows as $r){$name=(string)$this->pick($r,['name','title','label','addon_name'],'Ek paket'); $out[]=['external_id'=>(string)$this->pick($r,['id','addon_id'],''),'name'=>$name,'description'=>(string)$this->pick($r,['description','details'],''),'price'=>(float)$this->pick($r,['price','amount','monthly_price'],0),'currency'=>(string)$this->pick($r,['currency','currency_code'],'TRY'),'period'=>(string)$this->pick($r,['period','billing_cycle'],'monthly'),'product_id'=>(int)$this->pick($r,['product_id','service_id'],0)];}
        return array_filter($out,fn($r)=>$r['external_id']!=='');
    }

    private function fetchFlexibleFields(PDO $pdo, int $limit, int $offset): array
    {
        $rows=$this->firstRows($pdo,['custom_fields','product_custom_fields','product_fields'],$limit,$offset); $out=[];
        foreach($rows as $r){$label=(string)$this->pick($r,['label','name','title','field_name'],'Özel alan'); $type=strtolower((string)$this->pick($r,['field_type','type','input_type'],'text')); $allowed=['text','textarea','number','ip','url','email','phone','select','radio','checkbox','file','image','password']; if(!in_array($type,$allowed,true))$type='text'; $out[]=['external_id'=>(string)$this->pick($r,['id','field_id'],''),'context'=>'product','product_id'=>(int)$this->pick($r,['product_id','service_id'],0),'label'=>$label,'field_type'=>$type,'options'=>$this->pick($r,['options','values'],[]),'is_required'=>(int)$this->pick($r,['required','is_required'],0),'show_on'=>'order'];}
        return array_filter($out,fn($r)=>$r['external_id']!=='');
    }

    private function fetchServersGeneric(PDO $pdo, string $table, int $limit, int $offset): array
    {
        try {
            $rows = $pdo->query("SELECT * FROM $table ORDER BY id ASC LIMIT $limit OFFSET $offset")->fetchAll();
        } catch (\Throwable) { return []; }
        return array_map(fn($r) => [
            'external_id' => (string) ($r['id'] ?? ''),
            'name'        => (string) ($r['name'] ?? $r['title'] ?? ''),
            'hostname'    => (string) ($r['hostname'] ?? $r['host'] ?? ''),
            'ip'          => (string) ($r['ip'] ?? ''),
            'panel_type'  => strtolower((string) ($r['type'] ?? 'cpanel')),
            'port'        => (int) ($r['port'] ?? 2087),
            'ssl'         => (int) ($r['ssl'] ?? 1) === 1,
            'username'    => (string) ($r['username'] ?? ''),
            'api_token'   => (string) ($r['password'] ?? $r['api_key'] ?? ''),
            'is_active'   => (int) ($r['status'] ?? 1) === 1,
        ], $rows);
    }

    private function fetchRegistrarsGeneric(PDO $pdo, int $limit, int $offset): array
    {
        try {
            $rows = $pdo->query("SELECT DISTINCT registrar FROM domains_registered WHERE registrar IS NOT NULL LIMIT $limit OFFSET $offset")->fetchAll();
        } catch (\Throwable) { return []; }
        return array_map(fn($r) => [
            'external_id' => (string) $r['registrar'],
            'name'        => (string) $r['registrar'],
            'label'       => ucfirst((string) $r['registrar']),
            'settings'    => [],
        ], $rows);
    }

    private function fetchSettingsGeneric(PDO $pdo, string $table, int $limit, int $offset): array
    {
        try {
            $rows = $pdo->query("SELECT * FROM $table LIMIT $limit OFFSET $offset")->fetchAll();
        } catch (\Throwable) { return []; }
        // WISECP settings tek satır tüm ayarları JSON tutabilir — ilk satır alıp key-value çıkar
        $result = [];
        foreach ($rows as $r) {
            foreach ($r as $key => $val) {
                if ($key === 'id') continue;
                $result[] = [
                    'external_id' => $key,
                    'key'         => "wisecp.$key",
                    'value'       => (string) $val,
                    'is_mapped'   => false, // güvenlik: mapped değilse skip
                ];
            }
            break; // sadece ilk satırdan
        }
        return $result;
    }

    private function fetchFullConfig(PDO $pdo, int $limit, int $offset): array
    {
        $tables = [
            'settings' => ['id'],
            'payment_gateways' => ['id'],
            'payment_methods' => ['id'],
            'registrars' => ['id'],
            'servers' => ['id'],
            'currencies' => ['id'],
            'mail_templates' => ['id'],
            'email_templates' => ['id'],
            'product_groups' => ['id'],
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
            $code = strtoupper((string)$this->pick($row, ['code','currency','currency_code'], ''));
            if ($code !== '') {
                return ['currency' => [
                    'currency' => $code,
                    'rate' => (float)$this->pick($row, ['rate','exchange_rate'], 1),
                    'prefix' => (string)$this->pick($row, ['prefix','symbol'], ''),
                    'suffix' => (string)$this->pick($row, ['suffix'], ''),
                ]];
            }
        }

        if ($table === 'settings') {
            $settings = [];
            $map = [
                'company_name' => 'company.name',
                'site_name' => 'company.name',
                'email' => 'company.email',
                'admin_email' => 'company.email',
                'phone' => 'company.phone',
                'address' => 'company.address',
                'country' => 'company.country',
                'currency' => 'site.default_currency',
                'default_currency' => 'site.default_currency',
                'language' => 'site.default_locale',
                'default_language' => 'site.default_locale',
                'logo' => 'site.logo',
            ];
            foreach ($row as $key => $value) {
                if ($key === 'id' || is_array($value) || is_object($value)) continue;
                $target = $map[$key] ?? ('wisecp.configuration.' . $key);
                $settings[] = [
                    'key' => $target,
                    'value' => (string)$value,
                    'type' => self::isSecretSetting((string)$key) ? 'encrypted' : 'string',
                    'group' => isset($map[$key]) ? (explode('.', $target)[0] ?? 'general') : 'wisecp',
                ];
            }
            return $settings ? ['settings' => $settings] : [];
        }

        if (in_array($table, ['payment_gateways','payment_methods','registrars','servers'], true)) {
            $prefix = 'wisecp.' . rtrim($table, 's') . '.';
            $name = (string)$this->pick($row, ['code','name','module','class','id'], 'unknown');
            $settings = [];
            foreach ($row as $key => $value) {
                if (is_array($value) || is_object($value)) continue;
                $settings[] = [
                    'key' => $prefix . $name . '.' . $key,
                    'value' => (string)$value,
                    'type' => self::isSecretSetting((string)$key) ? 'encrypted' : 'string',
                    'group' => str_contains($table, 'payment') ? 'payment' : (str_contains($table, 'registrar') ? 'registrar' : 'hosting'),
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

    private function connect(array $c): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $c['host'] ?? '127.0.0.1', (int) ($c['port'] ?? 3306), $c['database'] ?? '');
        return new PDO($dsn, $c['username'] ?? '', $c['password'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    private function fetchCustomers(PDO $pdo, int $limit, int $offset): array
    {
        // WISECP: clients tablosu — sütun adları çeşitli sürümlerde farklı olabilir
        $rows = $pdo->query(
            "SELECT id, name, surname, email, company, phone, address, city, country, zip_code,
                    tc_number AS tax_id, credit AS balance, status, creation_date, password
             FROM clients ORDER BY id ASC LIMIT $limit OFFSET $offset"
        )->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($r) => [
            'external_id' => (int) $r['id'],
            'email'       => strtolower(trim((string) $r['email'])),
            'first_name'  => (string) ($r['name'] ?? ''),
            'last_name'   => (string) ($r['surname'] ?? ''),
            'company'     => (string) ($r['company'] ?? '') ?: null,
            'phone'       => (string) ($r['phone'] ?? '') ?: null,
            'address'     => (string) ($r['address'] ?? '') ?: null,
            'city'        => (string) ($r['city'] ?? '') ?: null,
            'country'     => (string) ($r['country'] ?? 'TR'),
            'postcode'    => (string) ($r['zip_code'] ?? '') ?: null,
            'tax_id'      => (string) ($r['tax_id'] ?? '') ?: null,
            'balance'     => (float) ($r['balance'] ?? 0),
            'status'      => ((int) ($r['status'] ?? 1)) === 1 ? 'active' : 'suspended',
            'created_at'  => (string) ($r['creation_date'] ?? date('Y-m-d')),
            'password_hash' => (string) ($r['password'] ?? ''), // WISECP: bcrypt/md5 karışık
        ], $rows);
    }

    private function fetchOrders(PDO $pdo, int $limit, int $offset): array
    {
        $rows = $pdo->query(
            "SELECT o.id, o.owner_id, o.currency, o.total, o.status, o.date, o.type,
                    c.email
             FROM orders o LEFT JOIN clients c ON c.id = o.owner_id
             ORDER BY o.id ASC LIMIT $limit OFFSET $offset"
        )->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($r) => [
            'external_id'    => (int) $r['id'],
            'order_number'   => 'WSC-' . str_pad((string) $r['id'], 6, '0', STR_PAD_LEFT),
            'customer_email' => strtolower(trim((string) $r['email'])),
            'total'          => (float) $r['total'],
            'currency'       => (string) ($r['currency'] ?? 'TRY'),
            'status'         => (int) $r['status'] === 1 ? 'paid' : 'pending',
            'created_at'     => (string) $r['date'],
        ], $rows);
    }

    private function fetchInvoices(PDO $pdo, int $limit, int $offset): array
    {
        $rows = $pdo->query(
            "SELECT i.id, i.owner_id, i.date, i.due_date, i.date_paid, i.total, i.status, i.subtotal, i.tax_total,
                    c.email
             FROM invoices i LEFT JOIN clients c ON c.id = i.owner_id
             ORDER BY i.id ASC LIMIT $limit OFFSET $offset"
        )->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($r) => [
            'external_id'    => (int) $r['id'],
            'invoice_number' => 'WSC-INV-' . $r['id'],
            'customer_email' => strtolower(trim((string) $r['email'])),
            'issue_date'     => (string) $r['date'],
            'due_date'       => (string) ($r['due_date'] ?? $r['date']),
            'paid_at'        => !empty($r['date_paid']) && $r['date_paid'] !== '0000-00-00 00:00:00' ? (string) $r['date_paid'] : null,
            'subtotal'       => (float) ($r['subtotal'] ?? 0),
            'tax_total'      => (float) ($r['tax_total'] ?? 0),
            'total'          => (float) $r['total'],
            'status'         => (int) $r['status'] === 1 ? 'paid' : 'unpaid',
        ], $rows);
    }

    private function fetchProducts(PDO $pdo, int $limit, int $offset): array
    {
        $rows = $pdo->query("SELECT id, name, type, status, description FROM products ORDER BY id ASC LIMIT $limit OFFSET $offset")->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($r) => [
            'external_id' => (int) $r['id'],
            'name'        => (string) $r['name'],
            'description' => (string) ($r['description'] ?? ''),
            'type'        => match (strtolower((string)($r['type'] ?? ''))) {
                'hosting'   => 'hosting',
                'vps','server' => 'vps',
                'domain'    => 'domain',
                default     => 'hosting',
            },
            'status' => ((int) ($r['status'] ?? 1)) === 1 ? 'active' : 'hidden',
        ], $rows);
    }

    private function fetchDomains(PDO $pdo, int $limit, int $offset): array
    {
        $rows = $pdo->query(
            "SELECT d.id, d.owner_id, d.name, d.registrar, d.registration_date, d.expire_date, d.status,
                    c.email
             FROM domains_registered d LEFT JOIN clients c ON c.id = d.owner_id
             ORDER BY d.id ASC LIMIT $limit OFFSET $offset"
        )->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($r) => [
            'external_id'       => (int) $r['id'],
            'domain_name'       => strtolower((string) $r['name']),
            'customer_email'    => strtolower(trim((string) $r['email'])),
            'registrar'         => (string) ($r['registrar'] ?? 'manual'),
            'registration_date' => (string) ($r['registration_date'] ?? ''),
            'expiry_date'       => (string) ($r['expire_date'] ?? ''),
            'next_due_date'     => (string) ($r['expire_date'] ?? ''),
            'status'            => (int) ($r['status'] ?? 1) === 1 ? 'active' : 'expired',
            'auto_renew'        => 1,
        ], $rows);
    }

    private function fetchHosting(PDO $pdo, int $limit, int $offset): array
    {
        $rows = $pdo->query(
            "SELECT cp.id, cp.owner_id, cp.domain, cp.username, cp.product_id, cp.server_id, cp.status,
                    cp.due_date, cp.creation_date, c.email
             FROM clients_products cp LEFT JOIN clients c ON c.id = cp.owner_id
             ORDER BY cp.id ASC LIMIT $limit OFFSET $offset"
        )->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($r) => [
            'external_id'    => (int) $r['id'],
            'domain'         => strtolower((string) ($r['domain'] ?? '')),
            'customer_email' => strtolower(trim((string) $r['email'])),
            'username'       => (string) ($r['username'] ?? ''),
            'package_id'     => (int) ($r['product_id'] ?? 0),
            'server_id'      => (int) ($r['server_id'] ?? 0),
            'status'         => (int) $r['status'] === 1 ? 'active' : 'suspended',
            'next_due_date'  => (string) ($r['due_date'] ?? ''),
            'created_at'     => (string) $r['creation_date'],
        ], $rows);
    }

    private function fetchTickets(PDO $pdo, int $limit, int $offset): array
    {
        $rows = $pdo->query(
            "SELECT t.id, t.owner_id, t.subject, t.priority, t.status, t.creation_date,
                    c.email
             FROM tickets t LEFT JOIN clients c ON c.id = t.owner_id
             ORDER BY t.id ASC LIMIT $limit OFFSET $offset"
        )->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $r) {
            $stmt = $pdo->prepare("SELECT sender_type, sender_id, message, creation_date FROM tickets_messages WHERE tid = ? ORDER BY id ASC");
            $stmt->execute([$r['id']]);
            $result[] = [
                'external_id'    => (int) $r['id'],
                'ticket_number'  => 'WSC-TKT-' . $r['id'],
                'customer_email' => strtolower(trim((string) $r['email'])),
                'subject'        => (string) $r['subject'],
                'status'         => match ((int) $r['status']) { 1 => 'open', 2 => 'closed', default => 'open' },
                'priority'       => match ((int) $r['priority']) { 1 => 'low', 2 => 'medium', 3 => 'high', 4 => 'urgent', default => 'medium' },
                'created_at'     => (string) $r['creation_date'],
                'replies'        => array_map(fn($m) => [
                    'author_type' => ($m['sender_type'] === 'admin') ? 'admin' : 'customer',
                    'message'     => (string) $m['message'],
                    'created_at'  => (string) $m['creation_date'],
                ], $stmt->fetchAll(PDO::FETCH_ASSOC)),
            ];
        }
        return $result;
    }
}
