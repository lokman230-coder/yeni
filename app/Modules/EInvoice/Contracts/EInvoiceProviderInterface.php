<?php

declare(strict_types=1);

namespace App\Modules\EInvoice\Contracts;

/**
 * e-Fatura / e-Arşiv sağlayıcı arayüzü.
 * Türkiye'de zorunlu: 5 milyon TL üstü ciro veya belirli sektörler.
 *
 * Desteklenmesi planlanan sağlayıcılar:
 *   - Uyumsoft
 *   - Logo Netsis
 *   - QNB eFinans
 *   - Mikro
 *   - Nilvera / Foriba
 */
interface EInvoiceProviderInterface
{
    public function id(): string;
    public function label(): string;

    /**
     * Fatura oluştur ve GIB'e gönder.
     * @param array $invoice ['invoice_number','issue_date','due_date','customer','items','totals','currency']
     * @return array{success:bool, uuid?:string, invoice_url?:string, error?:string}
     */
    public function submit(array $invoice): array;

    /**
     * Fatura durumunu sorgula (kabul/red).
     * @return array{status:'pending'|'accepted'|'rejected', message?:string}
     */
    public function status(string $uuid): array;

    /**
     * PDF/HTML görüntüsü al.
     */
    public function downloadPdf(string $uuid): ?string;

    /** Alıcının e-fatura mükellefi olup olmadığını kontrol et (VKN ile). */
    public function isRegisteredTaxpayer(string $taxId): bool;

    /** Bağlantı testi. */
    public function testConnection(): array;
}
