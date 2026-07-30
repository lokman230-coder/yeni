<?php

declare(strict_types=1);

namespace App\Modules\Hosting\Services;

use App\Core\Database\Connection;
use App\Modules\Hosting\HostingManager;
use App\Services\Logger\Logger;
use App\Support\Encrypter;

/**
 * Otomatik provisioning — sipariş "paid" olunca hosting hesabı açar.
 *
 * Akış:
 *   1. Sipariş ödendiğinde InvoiceService::markPaid → ReferralService::onOrderPaid + ProvisionService::provisionOrder
 *   2. ProvisionService, order_items'i döner:
 *      - Ürün type='hosting' ise: hosting_accounts + cPanel/DA/Plesk driver create
 *      - Ürün type='domain' ise: (Registrar tarafı — Faz 6d dışı, mevcut Domain modülü halleder)
 *      - Ürün type='vps' ise: manual (admin bakar)
 *   3. Hesap açıldıktan sonra hosting_accounts.status='active', order_items.status='active'
 *   4. Herhangi bir adım fail → status='pending', notes'a hata, admin bildirimi
 *
 * Server seçimi: products.server_group_id → hosting_servers WHERE server_group=... AND max_accounts > current_accounts
 * → en az yüklü sunucu (least-loaded)
 */
final class ProvisionService
{
    /**
     * @return array{ok:bool, provisioned:int, skipped:int, errors:array<int,string>}
     */
    public static function provisionOrder(int $orderId): array
    {
        $order = Connection::selectOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        if (!$order) return ['ok' => false, 'provisioned' => 0, 'skipped' => 0, 'errors' => ["Order #$orderId bulunamadı"]];
        if ($order['status'] !== 'paid') {
            return ['ok' => false, 'provisioned' => 0, 'skipped' => 0, 'errors' => ["Sipariş henüz ödenmemiş (status={$order['status']})"]];
        }

        $items = Connection::select("SELECT * FROM order_items WHERE order_id = ?", [$orderId]);
        $customer = Connection::selectOne("SELECT * FROM customers WHERE id = ?", [$order['customer_id']]);

        $out = ['ok' => true, 'provisioned' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($items as $item) {
            if ($item['status'] === 'active') { $out['skipped']++; continue; }
            $product = Connection::selectOne("SELECT * FROM products WHERE id = ?", [$item['product_id']]);
            if (!$product) { $out['errors'][] = "Item #{$item['id']}: ürün bulunamadı"; continue; }

            $result = match ($product['type']) {
                'hosting', 'email_hosting', 'radio_hosting' => self::provisionHosting($item, $product, $customer, $order),
                'vps', 'dedicated' => self::provisionServer($item, $product, $customer, $order),
                'domain'  => ['ok' => true, 'note' => 'Domain modülü tarafından işleniyor'],
                default   => ['ok' => true, 'note' => "Bu ürün tipi için otomasyon gerekmiyor ({$product['type']})"],
            };

            if ($result['ok']) {
                Connection::update('order_items', [
                    'status'       => 'active',
                    'activated_at' => date('Y-m-d H:i:s'),
                    'next_due_date'=> self::calcNextDue((string) $item['period']),
                ], 'id = ?', [$item['id']]);
                $out['provisioned']++;
            } else {
                $out['errors'][] = "Item #{$item['id']}: " . ($result['error'] ?? 'unknown');
                $out['ok'] = false;
            }
        }

        Logger::info('Provision tamamlandı', ['order' => $orderId, 'result' => $out]);
        return $out;
    }

    /**
     * Hosting hesap açılışı — server seç + cPanel/DA/Plesk create + kayıt.
     */
    private static function provisionHosting(array $item, array $product, ?array $customer, array $order): array
    {
        // 0) Domain adı
        $domain = trim((string) ($item['domain_name'] ?? ''));
        if ($domain === '') {
            $domain = 'auto-' . strtolower(bin2hex(random_bytes(3))) . '.temp.ahost.web.tr';
        }

        // 1) Zaten var mı?
        $existing = Connection::selectOne("SELECT id FROM hosting_accounts WHERE order_item_id = ?", [$item['id']]);
        if ($existing) return ['ok' => true, 'note' => "Hesap zaten mevcut (id={$existing['id']})"];

        // 2) Server seç
        $server = self::pickServer((int) ($product['server_group_id'] ?? 0));
        if (!$server) {
            // Manuel akış — kayıt aç, admin bakar
            $accountId = Connection::insert('hosting_accounts', [
                'order_item_id' => (int) $item['id'],
                'customer_id'   => (int) $order['customer_id'],
                'product_id'    => (int) $product['id'],
                'server_id'     => null,
                'domain'        => $domain,
                'username'      => self::generateUsername($domain),
                'package'       => (string) $product['name'],
                'status'        => 'pending',
                'notes'         => 'Sunucu bulunamadı — manuel provisioning gerekli',
                'next_due_date' => self::calcNextDue((string) $item['period']),
            ]);
            self::notifyAdminManual($accountId, $domain);
            return ['ok' => true, 'note' => "Manuel provisioning kuyruğunda (id=$accountId)"];
        }

        // 3) Kimlik + şifre üret
        $username = self::generateUsername($domain);
        $password = self::generatePassword();

        // 4) Driver ile hesap oluştur
        $driver = HostingManager::forServer((int) $server['id']);
        $req = [
            'domain'   => $domain,
            'username' => $username,
            'password' => $password,
            'package'  => (string) ($product['slug'] ?? $product['name']),
            'email'    => (string) ($customer['email'] ?? ''),
            'plan'     => (string) $product['name'],
            'quota'    => 0,
        ];
        $r = $driver->createAccount($req);

        // 5) Kayıt oluştur
        $accountId = Connection::insert('hosting_accounts', [
            'order_item_id'     => (int) $item['id'],
            'customer_id'       => (int) $order['customer_id'],
            'product_id'        => (int) $product['id'],
            'server_id'         => (int) $server['id'],
            'domain'            => $domain,
            'username'          => $username,
            'password_encrypted'=> Encrypter::encrypt($password),
            'package'           => (string) $product['name'],
            'status'            => $r['success'] ? 'active' : 'pending',
            'notes'             => $r['success'] ? 'Otomatik açıldı' : 'Hata: ' . ($r['message'] ?? '?'),
            'next_due_date'     => self::calcNextDue((string) $item['period']),
        ]);

        if ($r['success']) {
            Connection::query("UPDATE hosting_servers SET current_accounts = current_accounts + 1 WHERE id = ?", [$server['id']]);
            self::installAccountCrons($driver, $accountId, $username, $domain);
            self::notifyCustomer((int) $order['customer_id'], $domain, $username, $password, (string) $server['hostname']);
            return ['ok' => true, 'note' => "Hesap oluşturuldu (id=$accountId)"];
        }
        return ['ok' => false, 'error' => 'Panel hatası: ' . ($r['message'] ?? 'unknown')];
    }

    private static function installAccountCrons(object $driver, int $accountId, string $username, string $domain): void
    {
        try {
            if (!AccountCronService::enabled() || !method_exists($driver, 'installCronJobs')) {
                return;
            }

            $jobs = AccountCronService::jobs($username, $domain);
            if (!$jobs) {
                return;
            }

            $result = $driver->installCronJobs($username, $jobs);
            $installed = (int)($result['installed'] ?? 0);
            $errors = array_filter((array)($result['errors'] ?? []));
            $note = $installed > 0 ? " | Cron otomatik eklendi ($installed)" : ' | Cron otomatik eklenemedi';
            if ($errors) {
                $note .= ': ' . mb_substr(implode('; ', $errors), 0, 180);
            }

            Connection::query(
                "UPDATE hosting_accounts SET notes = CONCAT(COALESCE(notes, ''), ?) WHERE id = ?",
                [$note, $accountId]
            );
        } catch (\Throwable $e) {
            Logger::warning('Hosting cron auto install failed: ' . $e->getMessage(), ['account_id' => $accountId, 'username' => $username]);
        }
    }

    private static function provisionServer(array $item, array $product, ?array $customer, array $order): array
    {
        // VPS provisioning gerçek entegrasyonu yok — Faz 6e/6f (Proxmox API veya SolusVM)
        // Şimdilik pending oluştur, admin manuel açar
        $existing = Connection::selectOne("SELECT id FROM hosting_accounts WHERE order_item_id = ?", [$item['id']]);
        if ($existing) return ['ok' => true, 'note' => 'Kayıt var'];
        $provider = (string) ($product['automation_provider'] ?? $product['server_provider'] ?? 'manual');
        $accountId = Connection::insert('hosting_accounts', [
            'order_item_id' => (int) $item['id'],
            'customer_id'   => (int) $order['customer_id'],
            'product_id'    => (int) $product['id'],
            'server_id'     => null,
            'domain'        => (string) ($item['domain_name'] ?? 'vps-' . $item['id']),
            'username'      => null,
            'package'       => (string) $product['name'],
            'status'        => 'pending',
            'notes'         => 'VPS/Dedicated provisioning admin tarafından açılacak',
            'next_due_date' => self::calcNextDue((string) $item['period']),
        ]);
        self::notifyAdminManual($accountId, (string) ($item['domain_name'] ?? '—'));
        try {
            Connection::insert('vps_provisioning_jobs', [
                'hosting_account_id' => $accountId,
                'order_item_id' => (int) $item['id'],
                'customer_id' => (int) $order['customer_id'],
                'provider' => $provider ?: 'manual',
                'plan' => (string) $product['name'],
                'payload_json' => json_encode([
                    'domain' => (string) ($item['domain_name'] ?? 'vps-' . $item['id']),
                    'period' => (string) ($item['period'] ?? 'monthly'),
                    'product_id' => (int) $product['id'],
                    'order_id' => (int) $order['id'],
                    'custom_fields' => $item['custom_fields'] ?? null,
                ], JSON_UNESCAPED_UNICODE),
                'status' => $provider && $provider !== 'manual' ? 'queued' : 'manual_review',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
        }
        return ['ok' => true, 'note' => "VPS bekleyen (id=$accountId)"];
    }

    /** En az yüklü aktif sunucuyu seç. */
    private static function pickServer(int $groupId): ?array
    {
        try {
            if ($groupId > 0) {
                $row = Connection::selectOne(
                    "SELECT * FROM hosting_servers
                     WHERE is_active = 1
                       AND (server_group = (SELECT slug FROM server_groups WHERE id = ?) OR server_group IS NULL)
                       AND (max_accounts IS NULL OR current_accounts < max_accounts)
                       AND panel != 'manual'
                     ORDER BY current_accounts ASC LIMIT 1",
                    [$groupId]
                );
                if ($row) return $row;
            }
            return Connection::selectOne(
                "SELECT * FROM hosting_servers
                 WHERE is_active = 1
                   AND (max_accounts IS NULL OR current_accounts < max_accounts)
                   AND panel != 'manual'
                 ORDER BY current_accounts ASC LIMIT 1"
            );
        } catch (\Throwable) {
            return null;
        }
    }

    /** ornek.com → orne1234 (max 8 char, cPanel uyumlu) */
    private static function generateUsername(string $domain): string
    {
        $base = preg_replace('/[^a-z0-9]/', '', strtolower(strtok($domain, '.'))) ?: 'user';
        $base = substr($base, 0, 4);
        return $base . random_int(1000, 9999);
    }

    /** Güçlü random şifre (16 char, karışık) */
    private static function generatePassword(): string
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#$%';
        $out = '';
        for ($i = 0; $i < 16; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $out;
    }

    private static function calcNextDue(string $period): ?string
    {
        return match ($period) {
            'monthly'       => date('Y-m-d', strtotime('+1 month')),
            'quarterly'     => date('Y-m-d', strtotime('+3 months')),
            'semiannually'  => date('Y-m-d', strtotime('+6 months')),
            'annually'      => date('Y-m-d', strtotime('+1 year')),
            'biennially'    => date('Y-m-d', strtotime('+2 years')),
            'triennially'   => date('Y-m-d', strtotime('+3 years')),
            'onetime'       => null,
            default         => date('Y-m-d', strtotime('+1 month')),
        };
    }

    private static function notifyCustomer(int $customerId, string $domain, string $username, string $password, string $serverHost): void
    {
        try {
            $c = Connection::selectOne("SELECT email, first_name, phone FROM customers WHERE id = ?", [$customerId]);
            if (!$c) return;

            // 1) MAIL
            if (class_exists(\App\Services\Mail\Mailer::class)) {
                try {
                    \App\Services\Mail\Mailer::send(
                        'hosting_activated',
                        (string) $c['email'],
                        [
                            'first_name' => (string) ($c['first_name'] ?? ''),
                            'domain'     => $domain,
                            'username'   => $username,
                            'password'   => $password,
                            'server'     => $serverHost,
                            'panel_url'  => 'https://' . $serverHost . ':2083',
                        ],
                        (string) ($c['first_name'] ?? '') ?: null
                    );
                } catch (\Throwable $e) {
                    Logger::warning('Hosting activated mail failed: ' . $e->getMessage());
                }
            }

            // 2) SMS (opsiyonel — telefon varsa ve SMS driver ayarlıysa)
            $smsEnabled = (bool) \App\Services\Settings\SettingsManager::get('sms.hosting_notify', '0');
            if ($smsEnabled && !empty($c['phone']) && class_exists(\App\Services\Sms\SmsManager::class)) {
                try {
                    $msg = "Ahost: Hosting hesabiniz aktif. Domain: $domain, Kullanici: $username. Sifre e-postaniza gonderildi.";
                    \App\Services\Sms\SmsManager::send((string)$c['phone'], $msg);
                } catch (\Throwable $e) {
                    Logger::warning('Hosting activated SMS failed: ' . $e->getMessage());
                }
            }

            // 3) Activity log
            try {
                \App\Services\Logger\ActivityLog::log(
                    'hosting.activated',
                    'customer', $customerId,
                    "Hosting aktif: $domain (server: $serverHost)"
                );
            } catch (\Throwable) {}
        } catch (\Throwable) {}
    }

    private static function notifyAdminManual(int $accountId, string $domain): void
    {
        try {
            $tableExists = Connection::selectOne("SHOW TABLES LIKE 'notifications'");
            if (!$tableExists) return;
            // Tüm süper adminlere bildirim
            $admins = Connection::select("SELECT id FROM admins WHERE is_active = 1");
            foreach ($admins as $a) {
                Connection::insert('notifications', [
                    'user_type' => 'admin',
                    'user_id'   => (int) $a['id'],
                    'type'      => 'provision_manual',
                    'title'     => "Manuel provisioning: $domain",
                    'body'      => "Sipariş için otomatik hesap açılamadı, manuel açman gerekli.",
                    'url'       => '/admin/siparisler',
                    'icon'      => '⚠️',
                ]);
            }
        } catch (\Throwable) {}
    }
}
