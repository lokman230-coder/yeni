<?php

declare(strict_types=1);

namespace App\Modules\Import\Contracts;

/**
 * Dış panel driver arayüzü.
 *
 * Her panel (WHMCS, WISECP, Blesta, HostBill) için ayrı driver yazılır.
 * Driver kaynak DB'ye bağlanır, sorgular çeker, normalize ederek Ahost formatına döndürür.
 *
 * Normalize edilen tipler:
 *   - customers    → ['email','first_name','last_name','phone','company','tax_id','address','city','country','password_hash'?,'balance','status','created_at']
 *   - orders       → ['external_id','order_number','customer_email','total','currency','status','payment_method','created_at','paid_at','items':[...]]
 *   - invoices     → ['external_id','invoice_number','customer_email','total','paid_total','balance','status','issue_date','due_date','paid_at','items':[...]]
 *   - products     → ['external_id','name','slug','type','price','currency','description','status']
 *   - domains      → ['external_id','domain_name','customer_email','registrar','registration_date','expiry_date','status','auto_renew']
 *   - hosting      → ['external_id','domain','customer_email','username','server_name','package','status','next_due_date']
 *   - tickets      → ['external_id','ticket_number','customer_email','subject','status','priority','created_at','replies':[...]]
 */
interface ImportSourceInterface
{
    /** Panel adı: 'whmcs', 'wisecp', 'blesta', 'hostbill' */
    public function id(): string;
    public function label(): string;

    /** Bağlantı config alanları (form için) — key => ['label','type','required','hint'] */
    public function configFields(): array;

    /** Config ile bağlantı testi. */
    public function testConnection(array $config): array;

    /** Toplam kayıt sayıları (özet ekranı için) — kullanıcı ne kadar veri geleceğini görsün */
    public function counts(array $config): array;

    /**
     * Belirli bir tipten kayıtları normalize edilmiş formatta çek.
     * @param string $type    'customers'|'orders'|'invoices'|'products'|'domains'|'hosting'|'tickets'
     * @param int    $limit   Sayfa boyutu
     * @param int    $offset  Sayfa offset
     * @return array<int, array<string, mixed>>
     */
    public function fetch(array $config, string $type, int $limit = 100, int $offset = 0): array;
}
