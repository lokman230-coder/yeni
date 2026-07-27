<?php

declare(strict_types=1);

namespace App\Modules\Import\Drivers;

use App\Modules\Import\Contracts\ImportSourceInterface;
use PDO;

/**
 * WHMCS 8.x DB'den doğrudan okuma driver.
 *
 * WHMCS API yerine DIRECT DB kullanır — daha hızlı ve rate-limit yok.
 * Kaynak DB read-only bağlantı önerilir (güvenlik).
 *
 * WHMCS tablo yapısı (kısaltılmış):
 *   - tblclients        → müşteriler
 *   - tblorders         → siparişler
 *   - tblinvoices       → faturalar
 *   - tblinvoiceitems   → fatura kalemleri
 *   - tblproducts       → ürünler
 *   - tbldomains        → domainler
 *   - tblhosting        → hosting hesapları
 *   - tbltickets        → destek talepleri
 *   - tblticketreplies  → talep yanıtları
 */
final class WhmcsDriver implements ImportSourceInterface
{
    public function id(): string { return 'whmcs'; }
    public function label(): string { return 'WHMCS 8.x'; }

    public function configFields(): array
    {
        return [
            'host'     => ['label' => 'MySQL Host',     'type' => 'text',     'required' => true,  'hint' => 'Genelde 127.0.0.1 veya farklı sunucu IP'],
            'port'     => ['label' => 'Port',           'type' => 'number',   'required' => true,  'default' => 3306],
            'database' => ['label' => 'DB adı',         'type' => 'text',     'required' => true,  'hint' => 'Genelde whmcs veya cpanel kullanıcıadı_whmcs'],
            'username' => ['label' => 'DB Kullanıcı',   'type' => 'text',     'required' => true,  'hint' => 'READ-ONLY kullanıcı önerilir'],
            'password' => ['label' => 'DB Şifre',       'type' => 'password', 'required' => true],
            'prefix'   => ['label' => 'Tablo prefix',   'type' => 'text',     'required' => false, 'default' => 'tbl', 'hint' => 'WHMCS default: tbl'],
        ];
    }

    public function testConnection(array $config): array
    {
        try {
            $pdo = $this->connect($config);
            $prefix = (string) ($config['prefix'] ?? 'tbl');
            $c = (int) $pdo->query("SELECT COUNT(*) FROM {$prefix}clients")->fetchColumn();
            return ['ok' => true, 'message' => "Bağlantı başarılı — $c müşteri bulundu."];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Bağlantı hatası: ' . $e->getMessage()];
        }
    }

    public function counts(array $config): array
    {
        $pdo = null;
        try {
            $pdo = $this->connect($config);
        } catch (\Throwable) {
            return array_fill_keys(['customers','orders','invoices','products','domains','hosting','tickets','servers','registrars','settings','addons','custom_fields'], 0);
        }
        $prefix = (string) ($config['prefix'] ?? 'tbl');

        // Her tablo için ayrı try — biri yoksa diğerleri çalışsın
        $safe = function (string $sql) use ($pdo): int {
            try { return (int) $pdo->query($sql)->fetchColumn(); }
            catch (\Throwable) { return 0; }
        };

        return [
            'customers'     => $safe("SELECT COUNT(*) FROM {$prefix}clients"),
            'orders'        => $safe("SELECT COUNT(*) FROM {$prefix}orders"),
            'invoices'      => $safe("SELECT COUNT(*) FROM {$prefix}invoices"),
            'products'      => $safe("SELECT COUNT(*) FROM {$prefix}products"),
            'domains'       => $safe("SELECT COUNT(*) FROM {$prefix}domains"),
            'hosting'       => $safe("SELECT COUNT(*) FROM {$prefix}hosting"),
            'tickets'       => $safe("SELECT COUNT(*) FROM {$prefix}tickets"),
            'servers'       => $safe("SELECT COUNT(*) FROM {$prefix}servers"),
            'registrars'    => $safe("SELECT COUNT(DISTINCT registrar) FROM {$prefix}domains"),
            'settings'      => $safe("SELECT COUNT(*) FROM {$prefix}configuration"),
            'addons'        => $safe("SELECT COUNT(*) FROM {$prefix}addons"),
            'custom_fields' => $safe("SELECT COUNT(*) FROM {$prefix}customfields"),
        ];
    }

    public function fetch(array $config, string $type, int $limit = 100, int $offset = 0): array
    {
        $pdo = $this->connect($config);
        $prefix = (string) ($config['prefix'] ?? 'tbl');
        return match ($type) {
            'customers'      => $this->fetchCustomers($pdo, $prefix, $limit, $offset),
            'orders'         => $this->fetchOrders($pdo, $prefix, $limit, $offset),
            'invoices'       => $this->fetchInvoices($pdo, $prefix, $limit, $offset),
            'products'       => $this->fetchProducts($pdo, $prefix, $limit, $offset),
            'domains'        => $this->fetchDomains($pdo, $prefix, $limit, $offset),
            'hosting'        => $this->fetchHosting($pdo, $prefix, $limit, $offset),
            'tickets'        => $this->fetchTickets($pdo, $prefix, $limit, $offset),
            'servers'        => $this->fetchServers($pdo, $prefix, $limit, $offset),
            'registrars'     => $this->fetchRegistrars($pdo, $prefix, $limit, $offset),
            'settings'       => $this->fetchSettings($pdo, $prefix, $limit, $offset),
            'addons'         => $this->fetchAddons($pdo, $prefix, $limit, $offset),
            'custom_fields'  => $this->fetchCustomFields($pdo, $prefix, $limit, $offset),
            default          => [],
        };
    }

    // ---- Private ----

    private function connect(array $c): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $c['host'] ?? '127.0.0.1', (int) ($c['port'] ?? 3306), $c['database'] ?? '');
        return new PDO($dsn, $c['username'] ?? '', $c['password'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
    }

    private function fetchCustomers(PDO $pdo, string $p, int $limit, int $offset): array
    {
        $rows = $pdo->query(
            "SELECT id, firstname, lastname, email, companyname, phonenumber, address1, city, country,
                    postcode, tax_id, credit, status, datecreated, password
             FROM {$p}clients ORDER BY id ASC LIMIT $limit OFFSET $offset"
        )->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($r) => [
            'external_id'   => (int) $r['id'],
            'email'         => strtolower(trim((string) $r['email'])),
            'first_name'    => (string) $r['firstname'],
            'last_name'     => (string) $r['lastname'],
            'company'       => (string) ($r['companyname'] ?? '') ?: null,
            'phone'         => (string) ($r['phonenumber'] ?? '') ?: null,
            'address'       => (string) ($r['address1'] ?? '') ?: null,
            'city'          => (string) ($r['city'] ?? '') ?: null,
            'country'       => (string) ($r['country'] ?? 'TR'),
            'postcode'      => (string) ($r['postcode'] ?? '') ?: null,
            'tax_id'        => (string) ($r['tax_id'] ?? '') ?: null,
            'balance'       => (float) ($r['credit'] ?? 0),
            'status'        => ($r['status'] ?? 'Active') === 'Active' ? 'active' : 'suspended',
            'created_at'    => (string) ($r['datecreated'] ?? date('Y-m-d')),
            // WHMCS password: PHPass hash — Ahost'a taşınırken bozulmaz, PasswordHasher::verify yine çalışır
            'password_hash' => (string) ($r['password'] ?? ''),
        ], $rows);
    }

    private function fetchOrders(PDO $pdo, string $p, int $limit, int $offset): array
    {
        $rows = $pdo->query(
            "SELECT o.id, o.ordernum, o.userid, o.amount, o.currency, o.paymentmethod, o.status, o.date, o.notes,
                    c.email
             FROM {$p}orders o LEFT JOIN {$p}clients c ON c.id = o.userid
             ORDER BY o.id ASC LIMIT $limit OFFSET $offset"
        )->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($r) => [
            'external_id'    => (int) $r['id'],
            'order_number'   => (string) $r['ordernum'],
            'customer_email' => strtolower(trim((string) $r['email'])),
            'total'          => (float) $r['amount'],
            'currency'       => (string) $r['currency'],
            'payment_method' => strtolower((string) $r['paymentmethod']),
            'status'         => $this->mapOrderStatus((string) $r['status']),
            'created_at'     => (string) $r['date'],
            'notes'          => (string) ($r['notes'] ?? ''),
        ], $rows);
    }

    private function fetchInvoices(PDO $pdo, string $p, int $limit, int $offset): array
    {
        $rows = $pdo->query(
            "SELECT i.id, i.userid, i.date, i.duedate, i.datepaid, i.subtotal, i.tax, i.total, i.status,
                    c.email
             FROM {$p}invoices i LEFT JOIN {$p}clients c ON c.id = i.userid
             ORDER BY i.id ASC LIMIT $limit OFFSET $offset"
        )->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($r) => [
            'external_id'    => (int) $r['id'],
            'invoice_number' => 'WHMCS-' . $r['id'],
            'customer_email' => strtolower(trim((string) $r['email'])),
            'issue_date'     => (string) $r['date'],
            'due_date'       => (string) $r['duedate'],
            'paid_at'        => !empty($r['datepaid']) && $r['datepaid'] !== '0000-00-00 00:00:00' ? (string) $r['datepaid'] : null,
            'subtotal'       => (float) $r['subtotal'],
            'tax_total'      => (float) $r['tax'],
            'total'          => (float) $r['total'],
            'status'         => $this->mapInvoiceStatus((string) $r['status']),
        ], $rows);
    }

    private function fetchProducts(PDO $pdo, string $p, int $limit, int $offset): array
    {
        $rows = $pdo->query(
            "SELECT id, name, description, type, monthly, annually, hidden, servertype
             FROM {$p}products ORDER BY id ASC LIMIT $limit OFFSET $offset"
        )->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($r) => [
            'external_id' => (int) $r['id'],
            'name'        => (string) $r['name'],
            'description' => (string) ($r['description'] ?? ''),
            'type'        => match (strtolower((string)($r['type'] ?? ''))) {
                'hostingaccount' => 'hosting',
                'reselleraccount'=> 'hosting',
                'server','other' => 'vps',
                default          => 'hosting',
            },
            'monthly_price'  => (float) ($r['monthly'] ?? 0),
            'annually_price' => (float) ($r['annually'] ?? 0),
            'status'         => ((int)($r['hidden'] ?? 0)) === 0 ? 'active' : 'hidden',
        ], $rows);
    }

    private function fetchDomains(PDO $pdo, string $p, int $limit, int $offset): array
    {
        $rows = $pdo->query(
            "SELECT d.id, d.userid, d.domain, d.registrar, d.registrationdate, d.expirydate, d.nextduedate,
                    d.status, d.donotrenew, c.email
             FROM {$p}domains d LEFT JOIN {$p}clients c ON c.id = d.userid
             ORDER BY d.id ASC LIMIT $limit OFFSET $offset"
        )->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($r) => [
            'external_id'       => (int) $r['id'],
            'domain_name'       => strtolower((string) $r['domain']),
            'customer_email'    => strtolower(trim((string) $r['email'])),
            'registrar'         => (string) ($r['registrar'] ?? 'manual'),
            'registration_date' => !empty($r['registrationdate']) && $r['registrationdate'] !== '0000-00-00' ? (string) $r['registrationdate'] : null,
            'expiry_date'       => !empty($r['expirydate']) && $r['expirydate'] !== '0000-00-00' ? (string) $r['expirydate'] : null,
            'next_due_date'     => !empty($r['nextduedate']) && $r['nextduedate'] !== '0000-00-00' ? (string) $r['nextduedate'] : null,
            'status'            => $this->mapDomainStatus((string) $r['status']),
            'auto_renew'        => ((int) ($r['donotrenew'] ?? 0)) === 0 ? 1 : 0,
        ], $rows);
    }

    private function fetchHosting(PDO $pdo, string $p, int $limit, int $offset): array
    {
        $rows = $pdo->query(
            "SELECT h.id, h.userid, h.domain, h.username, h.packageid, h.server, h.domainstatus,
                    h.nextduedate, h.regdate, c.email
             FROM {$p}hosting h LEFT JOIN {$p}clients c ON c.id = h.userid
             ORDER BY h.id ASC LIMIT $limit OFFSET $offset"
        )->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($r) => [
            'external_id'    => (int) $r['id'],
            'domain'         => strtolower((string) $r['domain']),
            'customer_email' => strtolower(trim((string) $r['email'])),
            'username'       => (string) ($r['username'] ?? ''),
            'package_id'     => (int) ($r['packageid'] ?? 0),
            'server_id'      => (int) ($r['server'] ?? 0),
            'status'         => $this->mapHostingStatus((string) $r['domainstatus']),
            'next_due_date'  => !empty($r['nextduedate']) && $r['nextduedate'] !== '0000-00-00' ? (string) $r['nextduedate'] : null,
            'created_at'     => (string) $r['regdate'],
        ], $rows);
    }

    private function fetchTickets(PDO $pdo, string $p, int $limit, int $offset): array
    {
        $rows = $pdo->query(
            "SELECT t.id, t.tid, t.userid, t.name, t.email AS ticket_email, t.subject, t.status,
                    t.urgency, t.date, t.lastreply, c.email AS customer_email
             FROM {$p}tickets t LEFT JOIN {$p}clients c ON c.id = t.userid
             ORDER BY t.id ASC LIMIT $limit OFFSET $offset"
        )->fetchAll(PDO::FETCH_ASSOC);

        // Yanıtları da çek
        $result = [];
        foreach ($rows as $r) {
            $replies = $pdo->prepare("SELECT admin, name, message, date FROM {$p}ticketreplies WHERE tid = ? ORDER BY id ASC");
            $replies->execute([$r['id']]);
            $replyRows = $replies->fetchAll(PDO::FETCH_ASSOC);

            $result[] = [
                'external_id'    => (int) $r['id'],
                'ticket_number'  => (string) $r['tid'],
                'customer_email' => strtolower(trim((string) ($r['customer_email'] ?? $r['ticket_email']))),
                'subject'        => (string) $r['subject'],
                'status'         => $this->mapTicketStatus((string) $r['status']),
                'priority'       => $this->mapPriority((string) ($r['urgency'] ?? 'Medium')),
                'created_at'     => (string) $r['date'],
                'last_reply_at'  => (string) $r['lastreply'],
                'replies'        => array_map(fn($rp) => [
                    'author_type' => !empty($rp['admin']) ? 'admin' : 'customer',
                    'author_name' => (string) ($rp['name'] ?? $rp['admin'] ?? ''),
                    'message'     => (string) $rp['message'],
                    'created_at'  => (string) $rp['date'],
                ], $replyRows),
            ];
        }
        return $result;
    }

    // Status mapping (WHMCS → Ahost)
    private function mapOrderStatus(string $s): string
    {
        return match (strtolower($s)) {
            'active','paid'  => 'paid',
            'pending'        => 'pending',
            'cancelled'      => 'cancelled',
            'fraud'          => 'failed',
            default          => 'pending',
        };
    }
    private function mapInvoiceStatus(string $s): string
    {
        return match (strtolower($s)) {
            'paid'      => 'paid',
            'unpaid'    => 'unpaid',
            'cancelled' => 'cancelled',
            'refunded'  => 'refunded',
            default     => 'unpaid',
        };
    }
    private function mapDomainStatus(string $s): string
    {
        return match (strtolower($s)) {
            'active'   => 'active',
            'pending','pending transfer' => 'pending',
            'expired'  => 'expired',
            'cancelled','fraud' => 'cancelled',
            default    => 'pending',
        };
    }
    private function mapHostingStatus(string $s): string
    {
        return match (strtolower($s)) {
            'active'    => 'active',
            'pending'   => 'pending',
            'suspended' => 'suspended',
            'terminated','cancelled' => 'terminated',
            default     => 'pending',
        };
    }
    private function mapTicketStatus(string $s): string
    {
        return match (strtolower($s)) {
            'open','answered','customer-reply' => 'open',
            'in progress'   => 'open',
            'on hold'       => 'on_hold',
            'closed'        => 'closed',
            default         => 'open',
        };
    }
    private function mapPriority(string $s): string
    {
        return match (strtolower($s)) {
            'low'      => 'low',
            'medium'   => 'medium',
            'high'     => 'high',
            default    => 'medium',
        };
    }

    // ============================================================
    //  YENI: Sunucular, Registrarlar, Ayarlar, Addonlar, Custom Fields
    // ============================================================

    private function fetchServers(PDO $pdo, string $p, int $limit, int $offset): array
    {
        try {
            $sql = "SELECT id, name, hostname, ipaddress, type, active, username, accesshash, secure, port
                    FROM {$p}servers
                    ORDER BY id ASC LIMIT $limit OFFSET $offset";
            $rows = $pdo->query($sql)->fetchAll();
        } catch (\Throwable) {
            return [];
        }
        return array_map(fn($r) => [
            'external_id' => (string) $r['id'],
            'name'        => (string) $r['name'],
            'hostname'    => (string) ($r['hostname'] ?? ''),
            'ip'          => (string) ($r['ipaddress'] ?? ''),
            'panel_type'  => strtolower((string) ($r['type'] ?? 'cpanel')),
            'port'        => (int) ($r['port'] ?? 2087),
            'ssl'         => (int) ($r['secure'] ?? 1) === 1,
            'username'    => (string) ($r['username'] ?? ''),
            'api_token'   => (string) ($r['accesshash'] ?? ''),
            'is_active'   => (int) ($r['active'] ?? 1) === 1,
        ], $rows);
    }

    private function fetchRegistrars(PDO $pdo, string $p, int $limit, int $offset): array
    {
        try {
            // WHMCS'de registrars ayrı tablo değil — tbldomains'ten unique alalım + tblregistrars ayarlarını da bul
            $rows = $pdo->query("SELECT DISTINCT registrar FROM {$p}domains WHERE registrar IS NOT NULL AND registrar != '' LIMIT $limit OFFSET $offset")->fetchAll();
            $result = [];
            foreach ($rows as $r) {
                $name = (string) $r['registrar'];
                // Ek ayar sorgusu (varsa)
                $settings = [];
                try {
                    $s = $pdo->prepare("SELECT setting, value FROM {$p}registrars WHERE registrar = ?");
                    $s->execute([$name]);
                    foreach ($s->fetchAll() as $row) {
                        $settings[$row['setting']] = (string) $row['value'];
                    }
                } catch (\Throwable) {}
                $result[] = [
                    'external_id' => $name,
                    'name'        => $name,
                    'label'       => ucfirst($name),
                    'settings'    => $settings,
                ];
            }
            return $result;
        } catch (\Throwable) {
            return [];
        }
    }

    private function fetchSettings(PDO $pdo, string $p, int $limit, int $offset): array
    {
        try {
            $rows = $pdo->query("SELECT setting, value FROM {$p}configuration ORDER BY setting ASC LIMIT $limit OFFSET $offset")->fetchAll();
        } catch (\Throwable) {
            return [];
        }
        // WHMCS ayarları çok — sadece güvenli/anlamlı olanları map'le
        $mapKey = [
            'CompanyName'       => 'company.name',
            'Email'             => 'company.email',
            'Address1'          => 'company.address',
            'Address2'          => 'company.address2',
            'Country'           => 'company.country',
            'Currency'          => 'general.currency',
            'SystemURL'         => 'general.site_url',
            'Logo'              => 'general.logo',
            'PhoneNumber'       => 'company.phone',
            'PayPalEmail'       => 'payment.paypal_email',
            'DefaultTemplate'   => 'general.default_template',
            'CompanyNumber'     => 'company.tax_id',
            'TaxEnabled'        => 'tax.enabled',
            'Language'          => 'general.language',
        ];
        $result = [];
        foreach ($rows as $r) {
            $srcKey = (string) $r['setting'];
            $dstKey = $mapKey[$srcKey] ?? ('whmcs.' . $srcKey);
            $result[] = [
                'external_id' => $srcKey,
                'key'         => $dstKey,
                'value'       => (string) ($r['value'] ?? ''),
                'is_mapped'   => isset($mapKey[$srcKey]),
            ];
        }
        return $result;
    }

    private function fetchAddons(PDO $pdo, string $p, int $limit, int $offset): array
    {
        try {
            $sql = "SELECT id, name, description, billingcycle, tax
                    FROM {$p}addons
                    ORDER BY id ASC LIMIT $limit OFFSET $offset";
            $rows = $pdo->query($sql)->fetchAll();
        } catch (\Throwable) {
            return [];
        }
        // Her addon için fiyat tblpricing'te
        return array_map(function ($r) use ($pdo, $p) {
            $price = 0.0; $currency = 'TRY';
            try {
                $s = $pdo->prepare("SELECT monthly, currency FROM {$p}pricing WHERE type = 'addon' AND relid = ? LIMIT 1");
                $s->execute([$r['id']]);
                if ($row = $s->fetch()) {
                    $price    = (float) ($row['monthly'] ?? 0);
                    $currency = (string) ($row['currency'] ?? 'TRY');
                }
            } catch (\Throwable) {}
            return [
                'external_id' => (string) $r['id'],
                'name'        => (string) $r['name'],
                'description' => (string) ($r['description'] ?? ''),
                'price'       => $price,
                'currency'    => $currency,
                'period'      => $this->mapBillingCycle((string) ($r['billingcycle'] ?? 'monthly')),
            ];
        }, $rows);
    }

    private function fetchCustomFields(PDO $pdo, string $p, int $limit, int $offset): array
    {
        try {
            $sql = "SELECT id, type, relid, fieldname, fieldtype, description, fieldoptions, required, adminonly
                    FROM {$p}customfields
                    WHERE type IN ('product','domain','client')
                    ORDER BY id ASC LIMIT $limit OFFSET $offset";
            $rows = $pdo->query($sql)->fetchAll();
        } catch (\Throwable) {
            return [];
        }
        return array_map(fn($r) => [
            'external_id' => (string) $r['id'],
            'context'     => (string) $r['type'],           // 'product' | 'domain' | 'client'
            'product_id'  => (int) ($r['relid'] ?? 0),
            'label'       => (string) $r['fieldname'],
            'field_type'  => $this->mapCfType((string) ($r['fieldtype'] ?? 'text')),
            'description' => (string) ($r['description'] ?? ''),
            'options'     => array_values(array_filter(array_map('trim', explode(',', (string) ($r['fieldoptions'] ?? ''))))),
            'is_required' => (int) ($r['required'] ?? 0) === 1,
            'show_on'     => (int) ($r['adminonly'] ?? 0) === 1 ? 'admin_only' : 'order',
        ], $rows);
    }

    private function mapBillingCycle(string $cycle): string
    {
        return match (strtolower($cycle)) {
            'onetime','free'  => 'onetime',
            'monthly'         => 'monthly',
            'quarterly'       => 'quarterly',
            'semi-annually','semiannually' => 'semiannually',
            'annually','yearly' => 'annually',
            'biennially'      => 'biennially',
            'triennially'     => 'triennially',
            default           => 'monthly',
        };
    }

    private function mapCfType(string $t): string
    {
        return match (strtolower($t)) {
            'text'       => 'text',
            'textarea'   => 'textarea',
            'password'   => 'password',
            'dropdown'   => 'select',
            'tickbox'    => 'checkbox',
            'radio'      => 'radio',
            'link'       => 'url',
            default      => 'text',
        };
    }
}
