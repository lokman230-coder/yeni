<?php

declare(strict_types=1);

namespace App\Modules\EInvoice\Drivers;

use App\Modules\EInvoice\Contracts\EInvoiceProviderInterface;

/**
 * e-Fatura devre dışı bırakıldığında kullanılan no-op driver.
 * Sistemin çökmemesini garanti eder.
 */
final class NoopDriver implements EInvoiceProviderInterface
{
    public function id(): string    { return 'noop'; }
    public function label(): string { return 'e-Fatura Devre Dışı'; }
    public function submit(array $invoice): array { return ['success' => true, 'uuid' => null]; }
    public function status(string $uuid): array   { return ['status' => 'pending']; }
    public function downloadPdf(string $uuid): ?string { return null; }
    public function isRegisteredTaxpayer(string $taxId): bool { return false; }
    public function testConnection(): array { return ['success' => true, 'message' => 'No-op mode']; }
}
