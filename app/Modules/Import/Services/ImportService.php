<?php

declare(strict_types=1);

namespace App\Modules\Import\Services;

use App\Core\Database\Connection;
use App\Modules\Import\ImportManager;
use App\Services\Auth\PasswordHasher;
use App\Services\Logger\Logger;
use App\Support\Encrypter;

/**
 * Ana import servisi.
 *
 * Akış:
 *   1. Admin panelden config gir → test connection
 *   2. Type seç (customers, orders, ...) → job oluştur
 *   3. Batch batch fetch + normalize + insert
 *   4. Her insert için import_mappings kaydet (duplicate önleme, delta sync)
 *
 * Kullanım:
 *   ImportService::createJob('whmcs', $config, 'customers')
 *   ImportService::runJob($jobId, 100)  // 100 kayıt işle
 *   Cron ile devam ettir veya elle "Devam Et" butonu
 */
final class ImportService
{
    /** Yeni bir job oluştur — pending durumda. */
    public static function createJob(string $source, array $config, string $type, ?int $adminId = null): int
    {
        $driver = ImportManager::driver($source);
        if (!$driver) throw new \InvalidArgumentException("Bilinmeyen kaynak: $source");
        $counts = $driver->counts($config);
        $total = (int) ($counts[$type] ?? 0);

        return Connection::insert('import_jobs', [
            'source'             => $source,
            'config_encrypted'   => Encrypter::encrypt(json_encode($config, JSON_UNESCAPED_UNICODE)),
            'type'               => $type,
            'status'             => 'pending',
            'total'              => $total,
            'started_by_admin_id'=> $adminId,
        ]);
    }

    /**
     * Bir batch işle (100 kayıt vb). Job tamamlanana kadar tekrar çağrılabilir.
     * @return array{done:bool, imported:int, skipped:int, errors:int, offset:int}
     */
    public static function runJob(int $jobId, int $batchSize = 50): array
    {
        $job = Connection::selectOne("SELECT * FROM import_jobs WHERE id = ?", [$jobId]);
        if (!$job) throw new \RuntimeException("Job bulunamadı: $jobId");
        $processedBefore = (int)$job['imported'] + (int)$job['skipped'] + (int)$job['errors'];
        $incompleteBefore = (int)$job['total'] > 0 && $processedBefore < (int)$job['total'];
        $retryCompleted = in_array($job['status'], ['completed', 'failed'], true)
            && ((int)($job['errors'] ?? 0) > 0 || $incompleteBefore);
        if ($job['status'] === 'completed' && !$retryCompleted) return ['done' => true, 'imported' => (int)$job['imported'], 'skipped' => (int)$job['skipped'], 'errors' => (int)$job['errors'], 'offset' => (int)$job['imported']];

        if ($job['status'] === 'pending' || $retryCompleted) {
            Connection::update('import_jobs', ['status' => 'running', 'started_at' => date('Y-m-d H:i:s')], 'id = ?', [$jobId]);
        }

        $config = json_decode(Encrypter::decrypt((string) $job['config_encrypted']), true) ?: [];
        $driver = ImportManager::driver((string) $job['source']);
        if (!$driver) throw new \RuntimeException("Driver yok: {$job['source']}");

        if ($retryCompleted) {
            $counts = $driver->counts($config);
            $freshTotal = (int)($counts[(string)$job['type']] ?? 0);
            if ($freshTotal > 0 && $freshTotal !== (int)$job['total']) {
                $job['total'] = $freshTotal;
            }
        }

        $offset = $retryCompleted ? 0 : (int) $job['imported'] + (int) $job['skipped'] + (int) $job['errors'];
        $rows = $driver->fetch($config, (string) $job['type'], $batchSize, $offset);

        $imported = $retryCompleted ? 0 : (int) $job['imported'];
        $skipped  = $retryCompleted ? 0 : (int) $job['skipped'];
        $errors   = $retryCompleted ? 0 : (int) $job['errors'];
        $errorLog = $retryCompleted ? '' : (string) ($job['error_log'] ?? '');

        foreach ($rows as $row) {
            try {
                $status = self::importRow((string) $job['source'], (string) $job['type'], $row);
                if ($status === 'imported') $imported++;
                elseif ($status === 'skipped') $skipped++;
                elseif ($status === 'error') {
                    $errors++;
                    $errorLog .= "[" . date('H:i:s') . "] " . ($row['external_id'] ?? '?') . ": Importer error status returned\n";
                    if (strlen($errorLog) > 20000) $errorLog = substr($errorLog, -20000);
                }
            } catch (\Throwable $e) {
                $errors++;
                $errorLog .= "[" . date('H:i:s') . "] " . ($row['external_id'] ?? '?') . ": " . $e->getMessage() . "\n";
                if (strlen($errorLog) > 20000) $errorLog = substr($errorLog, -20000);
            }
        }

        $done = count($rows) < $batchSize; // batch dolmadıysa bitti
        $processedAfter = $imported + $skipped + $errors;
        $hasProblems = $errors > 0 || ((int)$job['total'] > 0 && $processedAfter < (int)$job['total']);
        $finalStatus = $done ? ($hasProblems ? 'failed' : 'completed') : 'running';

        Connection::update('import_jobs', [
            'imported'     => $imported,
            'skipped'      => $skipped,
            'errors'       => $errors,
            'error_log'    => $errorLog ?: null,
            'total'        => (int)$job['total'],
            'status'       => $finalStatus,
            'completed_at' => $done ? date('Y-m-d H:i:s') : null,
        ], 'id = ?', [$jobId]);

        return ['done' => $done && !$hasProblems, 'imported' => $imported, 'skipped' => $skipped, 'errors' => $errors, 'offset' => $processedAfter];
    }

    /**
     * Tek bir kaydı Ahost'a insert et. Duplicate ise skip.
     * @return string 'imported'|'skipped'
     */
    private static function importRow(string $source, string $type, array $row): string
    {
        $extId = (string) ($row['external_id'] ?? '');
        // Duplicate check
        if ($extId !== '') {
            $existing = Connection::selectOne(
                "SELECT id FROM import_mappings WHERE source = ? AND entity_type = ? AND external_id = ?",
                [$source, $type, $extId]
            );
            if ($existing) return 'skipped';
        }

        return match ($type) {
            'customers'      => self::importCustomer($source, $row),
            'products'       => self::importProduct($source, $row),
            'orders'         => self::importOrder($source, $row),
            'invoices'       => self::importInvoice($source, $row),
            'domains'        => self::importDomain($source, $row),
            'hosting'        => self::importHostingAccount($source, $row),
            'tickets'        => self::importTicket($source, $row),
            'servers'        => self::importServer($source, $row),
            'registrars'     => self::importRegistrar($source, $row),
            'settings'       => self::importSetting($source, $row),
            'addons'         => self::importAddon($source, $row),
            'custom_fields'  => self::importCustomField($source, $row),
            'full_config'    => self::importFullConfig($source, $row),
            default          => 'skipped',
        };
    }

    // ---- YENİ importer'lar (servers, registrars, settings, addons, custom_fields) ----

    private static function importServer(string $source, array $row): string
    {
        $extId = (string) ($row['external_id'] ?? $row['name'] ?? '');
        if ($extId === '') return 'skipped';
        $existing = self::mapFind($source, 'servers', $extId);
        if ($existing) return 'skipped';

        try {
            // hosting_servers şeması
            $pdo = Connection::pdo();
            $cols = [];
            try {
                foreach ($pdo->query("SHOW COLUMNS FROM hosting_servers") as $c) {
                    $cols[$c['Field']] = true;
                }
            } catch (\Throwable) {}

            // Panel type enum: cpanel/da/plesk/manual — normalize
            $panelIn = strtolower((string) ($row['panel_type'] ?? 'cpanel'));
            $panelNorm = match ($panelIn) {
                'cpanel','whm' => 'cpanel',
                'directadmin','da' => 'da',
                'plesk' => 'plesk',
                default => 'manual',
            };

            $data = [
                'name'       => substr((string) $row['name'], 0, 100),
                'hostname'   => (string) ($row['hostname'] ?? ''),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if (isset($cols['ip']))          $data['ip']          = (string) ($row['ip'] ?? '');
            if (isset($cols['ip_address']))  $data['ip_address']  = (string) ($row['ip'] ?? '');
            if (isset($cols['panel']))       $data['panel']       = $panelNorm;
            if (isset($cols['panel_type']))  $data['panel_type']  = $panelNorm;
            if (isset($cols['type']))        $data['type']        = $panelNorm;
            if (isset($cols['port']))        $data['port']        = (int) ($row['port'] ?? 2087);
            if (isset($cols['use_ssl']))     $data['use_ssl']     = !empty($row['ssl']) ? 1 : 0;
            if (isset($cols['ssl']))         $data['ssl']         = !empty($row['ssl']) ? 1 : 0;
            if (isset($cols['username']))    $data['username']    = (string) ($row['username'] ?? '');
            if (isset($cols['api_key_encrypted']) && !empty($row['api_token'])) {
                $data['api_key_encrypted'] = Encrypter::encrypt((string) $row['api_token']);
            }
            if (isset($cols['is_active']))    $data['is_active']    = !empty($row['is_active']) ? 1 : 0;
            if (isset($cols['status']))       $data['status']       = !empty($row['is_active']) ? 'active' : 'inactive';
            if (isset($cols['imported_from'])) $data['imported_from'] = $source;

            $localId = Connection::insert('hosting_servers', $data);
            self::mapAdd($source, 'servers', $extId, (int)$localId);
            return 'imported';
        } catch (\Throwable) {
            return 'error';
        }
    }

    private static function importRegistrar(string $source, array $row): string
    {
        $extId = (string) ($row['external_id'] ?? '');
        if ($extId === '') return 'skipped';
        $existing = self::mapFind($source, 'registrars', $extId);
        if ($existing) return 'skipped';

        try {
            // registrars tablosu varsa insert, yoksa settings'e yaz
            try {
                $localId = Connection::insert('registrars', [
                    'code'          => (string) $row['name'],
                    'label'         => (string) ($row['label'] ?? ucfirst($row['name'])),
                    'settings'      => json_encode($row['settings'] ?? [], JSON_UNESCAPED_UNICODE),
                    'is_active'     => 0, // güvenlik: manuel onay
                    'imported_from' => $source,
                    'created_at'    => date('Y-m-d H:i:s'),
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable) {
                // Fallback: settings tablosuna yaz
                $localId = time();
                foreach (($row['settings'] ?? []) as $k => $v) {
                    self::putSetting("registrar.{$row['name']}.$k", (string) $v);
                }
            }
            self::mapAdd($source, 'registrars', $extId, (int)$localId);
            return 'imported';
        } catch (\Throwable) {
            return 'error';
        }
    }

    private static function importSetting(string $source, array $row): string
    {
        $key = (string) ($row['key'] ?? '');
        $value = (string) ($row['value'] ?? '');
        if ($key === '' || !($row['is_mapped'] ?? false)) return 'skipped';
        // Sadece güvenli/mapped keyleri geçir
        try {
            self::putSetting($key, $value);
            return 'imported';
        } catch (\Throwable) {
            return 'error';
        }
    }

    private static function importFullConfig(string $source, array $row): string
    {
        $extId = (string) ($row['external_id'] ?? '');
        if ($extId === '') return 'skipped';

        $existing = Connection::selectOne(
            "SELECT id FROM external_import_records WHERE source = ? AND entity_type = ? AND external_id = ?",
            [$source, 'full_config', $extId]
        );
        if ($existing) return 'skipped';

        $map = (array) ($row['map'] ?? []);
        $mapped = 0;
        foreach ((array) ($map['settings'] ?? []) as $setting) {
            $key = (string) ($setting['key'] ?? '');
            if ($key === '') continue;
            self::putSettingValue(
                $key,
                (string) ($setting['value'] ?? ''),
                (string) ($setting['type'] ?? 'string'),
                (string) ($setting['group'] ?? 'whmcs')
            );
            $mapped++;
        }

        if (!empty($map['currency']) && is_array($map['currency'])) {
            self::upsertCurrency($map['currency']);
            $mapped++;
        }

        Connection::insert('external_import_records', [
            'source' => $source,
            'entity_type' => 'full_config',
            'external_id' => $extId,
            'source_table' => (string) ($row['source_table'] ?? ''),
            'payload_json' => json_encode($row['payload'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'map_status' => $mapped > 0 ? 'mapped' : 'needs_review',
            'notes' => $mapped > 0 ? "Mapped {$mapped} field(s)." : 'Archived for manual review.',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return 'imported';
    }

    private static function normalizeAddonPeriod(string $period): string
    {
        $p = strtolower(trim($period));
        return match ($p) {
            'once','onetime','one_time','one-time','setup' => 'onetime',
            '3_months','quarterly','quarter' => 'quarterly',
            '6_months','semiannual','semi-annually' => 'semiannually',
            '12_months','annual','annually','yearly' => 'annually',
            '24_months','biennial' => 'biennially',
            '36_months','triennial' => 'triennially',
            default => 'monthly',
        };
    }

    private static function importAddon(string $source, array $row): string
    {
        $extId = (string) ($row['external_id'] ?? '');
        if ($extId === '') return 'skipped';
        if (self::mapFind($source, 'addons', $extId)) return 'skipped';

        try {
            $localId = Connection::insert('product_addons', [
                'product_id'      => null,  // Genel addon — sonradan ürüne bağlanabilir
                'name'            => (string) $row['name'],
                'slug'            => \App\Support\Slug::make((string) $row['name']),
                'description'     => (string) ($row['description'] ?? '') ?: null,
                'price'           => (float) ($row['price'] ?? 0),
                'currency'        => (string) ($row['currency'] ?? 'TRY'),
                'period'          => self::normalizeAddonPeriod((string) ($row['period'] ?? 'monthly')),
                'is_active'       => 1,
                'sort_order'      => 0,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);
            self::mapAdd($source, 'addons', $extId, (int)$localId);
            return 'imported';
        } catch (\Throwable) {
            return 'error';
        }
    }

    private static function normalizeOptions(mixed $value): array
    {
        if (is_array($value)) return array_values(array_filter(array_map('strval', $value), fn($v) => $v !== ''));
        if (is_string($value) && trim($value) !== '') {
            $json = json_decode($value, true);
            if (is_array($json)) return self::normalizeOptions($json);
            return array_values(array_filter(array_map('trim', preg_split('/[,;|\\r\\n]+/', $value) ?: [])));
        }
        return [];
    }

    private static function importCustomField(string $source, array $row): string
    {
        $extId = (string) ($row['external_id'] ?? '');
        if ($extId === '') return 'skipped';
        if (self::mapFind($source, 'custom_fields', $extId)) return 'skipped';

        // WHMCS'de client-level custom field'lar farklı tabloda — sadece product context olanları alıyoruz
        if (($row['context'] ?? '') !== 'product') return 'skipped';

        $productExtId = (int) ($row['product_id'] ?? 0);
        $productLocalId = null;
        if ($productExtId > 0) {
            $productLocalId = self::mapFind($source, 'products', (string)$productExtId);
        }
        if (!$productLocalId) return 'skipped'; // Ürün henüz aktarılmamış — sonra tekrar

        try {
            $options = $row['options'] ?? [];
            $localId = Connection::insert('product_custom_fields', [
                'product_id'  => (int) $productLocalId,
                'label'       => (string) $row['label'],
                'field_key'   => \App\Support\Slug::make((string) $row['label']),
                'field_type'  => (string) ($row['field_type'] ?? 'text'),
                'options'     => ($options = self::normalizeOptions($row['options'] ?? [])) ? json_encode($options, JSON_UNESCAPED_UNICODE) : null,
                'is_required' => !empty($row['is_required']) ? 1 : 0,
                'is_active'   => 1,
                'show_on'     => (string) ($row['show_on'] ?? 'order'),
                'sort_order'  => 0,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
            self::mapAdd($source, 'custom_fields', $extId, (int)$localId);
            return 'imported';
        } catch (\Throwable) {
            return 'error';
        }
    }

    /** Yardımcı: mapping look-up */
    private static function mapFind(string $source, string $type, string $extId): ?int
    {
        $row = Connection::selectOne(
            "SELECT local_id FROM import_mappings WHERE source = ? AND entity_type = ? AND external_id = ?",
            [$source, $type, $extId]
        );
        return $row ? (int) $row['local_id'] : null;
    }

    /** Yardımcı: setting kaydı */
    private static function putSetting(string $key, string $value): void
    {
        self::putSettingValue($key, $value, 'string', explode('.', $key)[0] ?? 'general');
    }

    private static function putSettingValue(string $key, string $value, string $type = 'string', string $group = 'general'): void
    {
        $existing = Connection::selectOne("SELECT id FROM settings WHERE `key` = ?", [$key]);
        $stored = $type === 'encrypted' && $value !== '' ? Encrypter::encrypt($value) : $value;
        $type = $type === 'encrypted' ? 'encrypted' : 'string';
        if ($existing) {
            Connection::update('settings', [
                'value' => $stored,
                'type' => $type,
                'group' => $group,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$existing['id']]);
        } else {
            Connection::insert('settings', [
                'key'   => $key,
                'value' => $stored,
                'type'  => $type,
                'group' => $group,
            ]);
        }
    }

    private static function upsertCurrency(array $currency): void
    {
        $code = strtoupper((string) ($currency['currency'] ?? ''));
        if ($code === '' || strlen($code) !== 3) return;
        $rate = max(0.000001, (float) ($currency['rate'] ?? 1));
        $symbol = (string) ($currency['prefix'] ?? '');
        if ($symbol === '') $symbol = (string) ($currency['suffix'] ?? '');

        $existing = Connection::selectOne("SELECT id FROM currency_rates WHERE currency = ?", [$code]);
        $data = [
            'currency' => $code,
            'rate' => $code === 'TRY' ? 1 : $rate,
            'margin_percent' => 0,
            'source' => 'manual',
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $cols = array_column(Connection::select("SHOW COLUMNS FROM currency_rates"), 'Field');
            if (in_array('symbol', $cols, true)) $data['symbol'] = $symbol;
            if (in_array('is_active', $cols, true)) $data['is_active'] = 1;
        } catch (\Throwable) {}

        if ($existing) {
            Connection::update('currency_rates', $data, 'id = ?', [$existing['id']]);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            Connection::insert('currency_rates', $data);
        }
    }

    // ---- Individual importers ----

    private static function importCustomer(string $source, array $row): string
    {
        $email = (string) $row['email'];
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException("Geçersiz email: $email");
        }
        // Email zaten Ahost'ta mevcut mu?
        $existing = Connection::selectOne("SELECT id FROM customers WHERE email = ?", [$email]);
        if ($existing) {
            self::mapAdd($source, 'customers', (string) $row['external_id'], (int) $existing['id']);
            return 'skipped';
        }
        $hash = (string) ($row['password_hash'] ?? '');
        // Hash boşsa geçici bir şifre oluştur (kullanıcı reset ile alacak)
        if ($hash === '' || strlen($hash) < 20) {
            $hash = PasswordHasher::hash(bin2hex(random_bytes(16)));
        }
        $id = Connection::insert('customers', [
            'email'            => $email,
            'password_hash'    => $hash,
            'first_name'       => mb_substr((string)($row['first_name'] ?? ''), 0, 100),
            'last_name'        => mb_substr((string)($row['last_name'] ?? ''), 0, 100),
            'phone'            => $row['phone'] ?? null,
            'company'          => $row['company'] ?? null,
            'tax_id'           => $row['tax_id'] ?? null,
            'country'          => (string)($row['country'] ?? 'TR'),
            'city'             => $row['city'] ?? null,
            'address'          => $row['address'] ?? null,
            'postcode'         => $row['postcode'] ?? null,
            'balance'          => (float)($row['balance'] ?? 0),
            'status'           => (string)($row['status'] ?? 'active'),
            'imported_from'    => $source,
            'created_at'       => self::safeDate($row['created_at'] ?? null),
        ]);
        self::mapAdd($source, 'customers', (string) $row['external_id'], $id);
        return 'imported';
    }

    private static function importProduct(string $source, array $row): string
    {
        // Slug otomatik
        $slug = \App\Support\Slug::unique((string) $row['name'], 'products', 'slug');
        $id = Connection::insert('products', [
            'name'          => (string) $row['name'],
            'slug'          => $slug,
            'description'   => (string)($row['description'] ?? ''),
            'type'          => (string)($row['type'] ?? 'hosting'),
            'status'        => 'hidden', // ithal edilen ürünler önce gizli, admin sonra aktive eder
            'imported_from' => $source,
        ]);
        self::importProductPrices($id, (array) ($row['prices'] ?? []));
        self::mapAdd($source, 'products', (string) $row['external_id'], $id);
        return 'imported';
    }

    private static function importProductPrices(int $productId, array $prices): void
    {
        $sort = 0;
        foreach ($prices as $price) {
            $amount = (float) ($price['price'] ?? -1);
            if ($amount < 0) continue;
            $period = (string) ($price['period'] ?? 'monthly');
            if (!in_array($period, ['onetime','monthly','quarterly','semiannually','annually','biennially','triennially'], true)) {
                $period = 'monthly';
            }
            $currency = strtoupper((string) ($price['currency'] ?? 'TRY'));
            if (strlen($currency) !== 3) $currency = 'TRY';

            try {
                Connection::insert('product_prices', [
                    'product_id' => $productId,
                    'period' => $period,
                    'source_currency' => $currency,
                    'source_price' => $amount,
                    'is_active' => 1,
                    'sort_order' => $sort++,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable) {
                // Unique period/currency collisions are safe to ignore during import.
            }
        }
    }

    private static function importOrder(string $source, array $row): string
    {
        $cid = self::ensureCustomerForImport($source, $row);
        // Email'e göre bul
        if (!$cid && !empty($row['customer_email'])) {
            $c = Connection::selectOne("SELECT id FROM customers WHERE email = ?", [$row['customer_email']]);
            if ($c) $cid = (int) $c['id'];
        }
        if (!$cid) throw new \RuntimeException("Sipariş için müşteri yok: {$row['customer_email']}");

        $id = Connection::insert('orders', [
            'order_number'   => (string) $row['order_number'],
            'customer_id'    => $cid,
            'status'         => (string) $row['status'],
            'subtotal'       => (float) $row['total'],
            'tax_total'      => 0,
            'total'          => (float) $row['total'],
            'currency'       => (string)($row['currency'] ?? 'TRY'),
            'payment_method' => (string)($row['payment_method'] ?? 'manual'),
            'notes'          => (string)($row['notes'] ?? '') ?: null,
            'imported_from'  => $source,
            'created_at'     => self::safeDate($row['created_at'] ?? null),
            'paid_at'        => !empty($row['paid_at']) ? self::safeDate($row['paid_at']) : null,
        ]);
        self::mapAdd($source, 'orders', (string) $row['external_id'], $id);
        return 'imported';
    }

    private static function importInvoice(string $source, array $row): string
    {
        $cid = self::ensureCustomerForImport($source, $row);
        if (!$cid) throw new \RuntimeException("Fatura için müşteri yok");
        $paid = (float) ($row['paid_total'] ?? ($row['status'] === 'paid' ? (float)$row['total'] : 0));
        $id = Connection::insert('invoices', [
            'invoice_number' => (string) $row['invoice_number'],
            'customer_id'    => $cid,
            'status'         => (string) $row['status'],
            'issue_date'     => self::safeDate($row['issue_date'] ?? null, 'Y-m-d'),
            'due_date'       => self::safeDate($row['due_date'] ?? null, 'Y-m-d'),
            'paid_at'        => !empty($row['paid_at']) ? self::safeDate($row['paid_at']) : null,
            'subtotal'       => (float) ($row['subtotal'] ?? $row['total']),
            'tax_total'      => (float) ($row['tax_total'] ?? 0),
            'total'          => (float) $row['total'],
            'paid_total'     => $paid,
            'balance'        => max(0, (float)$row['total'] - $paid),
            'imported_from'  => $source,
        ]);
        self::mapAdd($source, 'invoices', (string) $row['external_id'], $id);
        return 'imported';
    }

    private static function importDomain(string $source, array $row): string
    {
        $cid = self::ensureCustomerForImport($source, $row);
        if (!$cid) throw new \RuntimeException("Domain için müşteri yok");
        // Aynı domain zaten var mı?
        $exists = Connection::selectOne("SELECT id FROM domains WHERE domain_name = ?", [$row['domain_name']]);
        if ($exists) {
            self::mapAdd($source, 'domains', (string) $row['external_id'], (int) $exists['id']);
            return 'skipped';
        }
        $id = Connection::insert('domains', [
            'customer_id'       => $cid,
            'domain_name'       => (string) $row['domain_name'],
            'registration_date' => !empty($row['registration_date']) ? self::safeDate($row['registration_date'], 'Y-m-d') : null,
            'expiry_date'       => !empty($row['expiry_date']) ? self::safeDate($row['expiry_date'], 'Y-m-d') : null,
            'next_due_date'     => !empty($row['next_due_date']) ? self::safeDate($row['next_due_date'], 'Y-m-d') : null,
            'status'            => (string) $row['status'],
            'auto_renew'        => (int) ($row['auto_renew'] ?? 1),
            'imported_from'    => $source,
        ]);
        self::mapAdd($source, 'domains', (string) $row['external_id'], $id);
        return 'imported';
    }

    private static function importHostingAccount(string $source, array $row): string
    {
        $cid = self::ensureCustomerForImport($source, $row);
        if (!$cid) throw new \RuntimeException("Hosting için müşteri yok");
        $passwordPlain = (string) ($row['password_plain'] ?? '');
        $notes = 'Imported from: ' . $source;
        if (!empty($row['password_import_note'])) {
            $notes .= "\n" . (string) $row['password_import_note'];
        }
        $productId = null;
        if (!empty($row['package_id'])) {
            $productId = self::mapFind($source, 'products', (string) $row['package_id']);
        }

        $data = [
            'customer_id'  => $cid,
            'product_id'   => $productId,
            'domain'       => (string) ($row['domain'] ?? ''),
            'username'     => (string) ($row['username'] ?? '') ?: null,
            'package'      => 'imported',
            'status'       => (string) $row['status'],
            'next_due_date'=> !empty($row['next_due_date']) ? self::safeDate($row['next_due_date'], 'Y-m-d') : null,
            'notes'        => 'İthal edildi: ' . $source,
            'imported_from'=> $source,
        ];
        $data['notes'] = $notes;
        if ($passwordPlain !== '') {
            $data['password_encrypted'] = Encrypter::encrypt($passwordPlain);
        }

        $id = Connection::insert('hosting_accounts', $data);
        self::mapAdd($source, 'hosting', (string) $row['external_id'], $id);
        return 'imported';
    }

    private static function importTicket(string $source, array $row): string
    {
        $cid = self::ensureCustomerForImport($source, $row);
        if (!$cid) throw new \RuntimeException("Ticket için müşteri yok");
        $id = Connection::insert('tickets', [
            'ticket_number'  => (string) $row['ticket_number'],
            'customer_id'    => $cid,
            'subject'        => mb_substr((string) $row['subject'], 0, 255),
            'priority'       => (string) $row['priority'],
            'status'         => (string) $row['status'],
            'imported_from'  => $source,
            'created_at'     => self::safeDate($row['created_at'] ?? null),
            'last_reply_at'  => !empty($row['last_reply_at']) ? self::safeDate($row['last_reply_at']) : null,
        ]);
        self::mapAdd($source, 'tickets', (string) $row['external_id'], $id);

        // Yanıtları da aktar
        foreach ((array)($row['replies'] ?? []) as $r) {
            Connection::insert('ticket_replies', [
                'ticket_id'   => $id,
                'author_type' => (string) ($r['author_type'] ?? 'customer'),
                'author_id'   => ($r['author_type'] ?? '') === 'admin' ? null : $cid,
                'message'     => (string) $r['message'],
                'is_internal' => 0,
                'created_at'  => self::safeDate($r['created_at'] ?? null),
            ]);
        }
        return 'imported';
    }

    // ---- Helpers ----

    private static function mapAdd(string $source, string $type, string $extId, int $localId): void
    {
        if ($extId === '') return;
        try {
            Connection::insert('import_mappings', [
                'source'      => $source,
                'entity_type' => $type,
                'external_id' => $extId,
                'local_id'    => $localId,
            ]);
        } catch (\Throwable) { /* duplicate — ignore */ }
    }

    private static function mapGet(string $source, string $type, string $extId): ?int
    {
        try {
            $r = Connection::selectOne(
                "SELECT local_id FROM import_mappings WHERE source = ? AND entity_type = ? AND external_id = ?",
                [$source, $type, $extId]
            );
            return $r ? (int) $r['local_id'] : null;
        } catch (\Throwable) { return null; }
    }

    private static function ensureCustomerForImport(string $source, array $row): int
    {
        $email = strtolower(trim((string)($row['customer_email'] ?? $row['email'] ?? '')));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $existing = self::customerIdByEmail($email);
            if ($existing) {
                return $existing;
            }
        } else {
            $seed = preg_replace('/[^a-z0-9]+/i', '-', $source . '-' . ($row['external_id'] ?? uniqid('', true)));
            $email = strtolower(trim($seed, '-')) . '@import.local';
        }

        $existing = self::customerIdByEmail($email);
        if ($existing) {
            return $existing;
        }

        $cols = self::tableColumns('customers');
        $data = [
            'email' => $email,
            'password_hash' => PasswordHasher::hash(bin2hex(random_bytes(16))),
            'first_name' => mb_substr((string)($row['first_name'] ?? $row['name'] ?? 'Imported'), 0, 100),
            'last_name' => mb_substr((string)($row['last_name'] ?? 'Customer'), 0, 100),
            'status' => 'active',
            'country' => (string)($row['country'] ?? 'TR'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        foreach ([
            'phone' => 'phone',
            'company' => 'company',
            'tax_id' => 'tax_id',
            'city' => 'city',
            'address' => 'address',
            'postcode' => 'postcode',
            'imported_from' => null,
        ] as $column => $sourceKey) {
            if (in_array($column, $cols, true)) {
                $data[$column] = $sourceKey === null ? $source : ($row[$sourceKey] ?? null);
            }
        }

        foreach (array_keys($data) as $column) {
            if (!in_array($column, $cols, true)) {
                unset($data[$column]);
            }
        }

        $id = Connection::insert('customers', $data);
        $external = (string)($row['customer_external_id'] ?? $row['userid'] ?? $row['customer_email'] ?? $row['external_id'] ?? $email);
        self::mapAdd($source, 'customers', $external, $id);
        return $id;
    }

    private static function tableColumns(string $table): array
    {
        static $cache = [];
        if (isset($cache[$table])) {
            return $cache[$table];
        }

        try {
            $rows = Connection::select("SHOW COLUMNS FROM {$table}");
            return $cache[$table] = array_map('strtolower', array_column($rows, 'Field'));
        } catch (\Throwable) {
            return $cache[$table] = [];
        }
    }

    private static function customerIdByEmail(string $email): ?int
    {
        if ($email === '') return null;
        try {
            $r = Connection::selectOne("SELECT id FROM customers WHERE email = ?", [$email]);
            return $r ? (int) $r['id'] : null;
        } catch (\Throwable) { return null; }
    }

    private static function safeDate(?string $s, string $format = 'Y-m-d H:i:s'): string
    {
        if (!$s || $s === '0000-00-00' || $s === '0000-00-00 00:00:00') return date($format);
        $t = strtotime($s);
        return $t ? date($format, $t) : date($format);
    }
}
