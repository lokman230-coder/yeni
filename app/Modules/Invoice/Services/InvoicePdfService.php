<?php

declare(strict_types=1);

namespace App\Modules\Invoice\Services;

use App\Core\Database\Connection;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Fatura PDF üreteci — Dompdf ile.
 *
 * Kullanım:
 *   $bin = InvoicePdfService::render($invoiceId);
 *   Response::download($bin, 'invoice-INV-...-.pdf', 'application/pdf');
 */
final class InvoicePdfService
{
    public static function render(int $invoiceId): ?string
    {
        $invoice = Connection::selectOne(
            "SELECT i.*, c.email AS customer_email, c.first_name, c.last_name, c.company,
                    c.address, c.tax_id, c.tax_office, c.city, c.postcode
             FROM invoices i
             LEFT JOIN customers c ON c.id = i.customer_id
             WHERE i.id = ? LIMIT 1", [$invoiceId]
        );
        if (!$invoice) return null;

        $items = Connection::select("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id ASC", [$invoiceId]);

        // Firma bilgisi
        $companyName = self::setting('site.name', (string) env('APP_NAME', 'Ahost Bilişim'));
        $companyTax  = self::setting('company.tax_id', '');
        $companyAddress = self::setting('company.address', '');

        $html = self::html($invoice, $items, [
            'company_name'    => $companyName,
            'company_tax'     => $companyTax,
            'company_address' => $companyAddress,
        ]);

        $opts = new Options();
        $opts->set('defaultFont', 'DejaVu Sans');
        $opts->set('isRemoteEnabled', false);
        $opts->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($opts);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }

    private static function setting(string $key, string $default = ''): string
    {
        try {
            $parts = explode('.', $key, 2);
            $row = Connection::selectOne("SELECT `value` FROM settings WHERE `group`=? AND `key`=?", [$parts[0] ?? '', $parts[1] ?? '']);
            return $row ? (string) $row['value'] : $default;
        } catch (\Throwable) { return $default; }
    }

    private static function html(array $inv, array $items, array $company): string
    {
        $fullName = trim(($inv['first_name'] ?? '') . ' ' . ($inv['last_name'] ?? ''));
        $currency = htmlspecialchars((string) $inv['currency'], ENT_HTML5);

        $statusBadge = match ($inv['status']) {
            'paid' => '<span style="background:#059669;color:#fff;padding:4px 12px;border-radius:12px;font-size:12px">✓ ÖDENDİ</span>',
            'partially_paid' => '<span style="background:#d97706;color:#fff;padding:4px 12px;border-radius:12px;font-size:12px">◐ KISMİ</span>',
            'overdue' => '<span style="background:#dc2626;color:#fff;padding:4px 12px;border-radius:12px;font-size:12px">⚠️ VADESİ GEÇTİ</span>',
            'cancelled' => '<span style="background:#6b7280;color:#fff;padding:4px 12px;border-radius:12px;font-size:12px">İPTAL</span>',
            'refunded' => '<span style="background:#6b7280;color:#fff;padding:4px 12px;border-radius:12px;font-size:12px">İADE</span>',
            default => '<span style="background:#f59e0b;color:#fff;padding:4px 12px;border-radius:12px;font-size:12px">ÖDENMEMİŞ</span>',
        };

        $itemsHtml = '';
        foreach ($items as $it) {
            $itemsHtml .= '<tr>
                <td style="padding:10px;border-bottom:1px solid #e5e7eb">' . htmlspecialchars((string) $it['description'], ENT_HTML5) . '</td>
                <td style="padding:10px;border-bottom:1px solid #e5e7eb;text-align:center">' . (int) $it['quantity'] . '</td>
                <td style="padding:10px;border-bottom:1px solid #e5e7eb;text-align:right">' . number_format((float) $it['unit_price'], 2, ',', '.') . ' ' . $currency . '</td>
                <td style="padding:10px;border-bottom:1px solid #e5e7eb;text-align:right">%' . number_format((float) $it['tax_rate'], 0, ',', '.') . '</td>
                <td style="padding:10px;border-bottom:1px solid #e5e7eb;text-align:right;font-weight:600">' . number_format((float) $it['line_total'], 2, ',', '.') . ' ' . $currency . '</td>
            </tr>';
        }

        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 13px; color: #111; padding: 20px; }
        h1 { color: #0ea5e9; margin: 0 0 4px; font-size: 28px }
        .row { display: table; width: 100%; }
        .col { display: table-cell; vertical-align: top; }
        table { border-collapse: collapse; width: 100%; }
        th { padding: 10px; background: #f9fafb; text-align: left; font-size: 12px; color: #6b7280; border-bottom: 2px solid #0ea5e9 }
        </style></head><body>
        <div class="row" style="margin-bottom:24px">
            <div class="col" style="width:60%">
                <h1>' . htmlspecialchars($company['company_name'], ENT_HTML5) . '</h1>
                <div style="color:#6b7280;font-size:12px">' . nl2br(htmlspecialchars($company['company_address'], ENT_HTML5)) . '</div>
                ' . ($company['company_tax'] ? '<div style="color:#6b7280;font-size:12px">VKN: ' . htmlspecialchars($company['company_tax'], ENT_HTML5) . '</div>' : '') . '
            </div>
            <div class="col" style="width:40%;text-align:right">
                <div style="font-size:18px;font-weight:700;color:#0ea5e9">FATURA</div>
                <div style="font-family:monospace;margin-top:4px">' . htmlspecialchars((string) $inv['invoice_number'], ENT_HTML5) . '</div>
                <div style="margin-top:8px">' . $statusBadge . '</div>
            </div>
        </div>

        <div class="row" style="margin-bottom:20px;background:#f9fafb;padding:16px;border-radius:8px">
            <div class="col" style="width:50%">
                <div style="font-size:11px;color:#6b7280;text-transform:uppercase;margin-bottom:4px">FATURA EDİLEN</div>
                <div style="font-weight:600;font-size:14px">' . htmlspecialchars($fullName ?: $inv['customer_email'], ENT_HTML5) . '</div>
                ' . ($inv['company'] ? '<div>' . htmlspecialchars((string) $inv['company'], ENT_HTML5) . '</div>' : '') . '
                <div style="font-size:12px;color:#6b7280">' . htmlspecialchars((string) $inv['customer_email'], ENT_HTML5) . '</div>
                ' . ($inv['address'] ? '<div style="font-size:12px;color:#6b7280;margin-top:4px">' . nl2br(htmlspecialchars((string) $inv['address'], ENT_HTML5)) . '</div>' : '') . '
                ' . ($inv['tax_id'] ? '<div style="font-size:12px;color:#6b7280">VKN/TCKN: ' . htmlspecialchars((string) $inv['tax_id'], ENT_HTML5) . ($inv['tax_office'] ? ' / ' . htmlspecialchars((string) $inv['tax_office'], ENT_HTML5) : '') . '</div>' : '') . '
            </div>
            <div class="col" style="width:50%;text-align:right">
                <div style="font-size:11px;color:#6b7280;text-transform:uppercase">DÜZENLEME TARİHİ</div>
                <div style="font-weight:600">' . date('d.m.Y', strtotime((string) $inv['issue_date'])) . '</div>
                <div style="font-size:11px;color:#6b7280;text-transform:uppercase;margin-top:8px">VADE TARİHİ</div>
                <div style="font-weight:600">' . date('d.m.Y', strtotime((string) $inv['due_date'])) . '</div>
            </div>
        </div>

        <table style="margin-bottom:20px">
            <thead><tr>
                <th>Açıklama</th>
                <th style="text-align:center">Adet</th>
                <th style="text-align:right">Birim Fiyat</th>
                <th style="text-align:right">KDV</th>
                <th style="text-align:right">Tutar</th>
            </tr></thead>
            <tbody>' . $itemsHtml . '</tbody>
        </table>

        <div class="row">
            <div class="col" style="width:60%">&nbsp;</div>
            <div class="col" style="width:40%">
                <table style="border:1px solid #e5e7eb;border-radius:8px">
                    <tr><td style="padding:8px 12px;color:#6b7280">Ara Toplam</td><td style="padding:8px 12px;text-align:right">' . number_format((float) $inv['subtotal'], 2, ',', '.') . ' ' . $currency . '</td></tr>
                    ' . ($inv['discount_total'] > 0 ? '<tr><td style="padding:8px 12px;color:#059669">İndirim</td><td style="padding:8px 12px;text-align:right;color:#059669">-' . number_format((float) $inv['discount_total'], 2, ',', '.') . ' ' . $currency . '</td></tr>' : '') . '
                    <tr><td style="padding:8px 12px;color:#6b7280">KDV</td><td style="padding:8px 12px;text-align:right">' . number_format((float) $inv['tax_total'], 2, ',', '.') . ' ' . $currency . '</td></tr>
                    <tr style="background:#0ea5e9;color:#fff"><td style="padding:12px;font-weight:700">TOPLAM</td><td style="padding:12px;text-align:right;font-weight:700;font-size:18px">' . number_format((float) $inv['total'], 2, ',', '.') . ' ' . $currency . '</td></tr>
                    ' . ($inv['balance'] > 0.01 ? '<tr><td style="padding:8px 12px;color:#d97706">Kalan</td><td style="padding:8px 12px;text-align:right;color:#d97706;font-weight:600">' . number_format((float) $inv['balance'], 2, ',', '.') . ' ' . $currency . '</td></tr>' : '') . '
                </table>
            </div>
        </div>

        <div style="margin-top:40px;padding-top:20px;border-top:1px solid #e5e7eb;color:#9ca3af;font-size:11px;text-align:center">
            Bu fatura ' . htmlspecialchars($company['company_name'], ENT_HTML5) . ' tarafından ' . date('d.m.Y H:i') . ' tarihinde elektronik olarak oluşturulmuştur.
        </div>
        </body></html>';
    }
}
