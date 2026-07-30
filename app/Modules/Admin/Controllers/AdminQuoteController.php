<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Modules\Invoice\Services\InvoiceService;

/**
 * "Teklifler" (Quotes) — müşteri profili altında teklif oluştur, gönder,
 * onaylanınca faturaya çevir (InvoiceService::createManual).
 */
final class AdminQuoteController
{
    public function create(Request $request): Response
    {
        $customerId = (int) $request->param('id');
        $customer = Connection::selectOne('SELECT id, email, first_name, last_name FROM customers WHERE id = ?', [$customerId]);
        if (!$customer) return Response::notFound();

        return Response::html((new View())->render('admin::quotes.form', [
            'title'    => 'Yeni Teklif',
            'customer' => $customer,
            'quote'    => null,
            'items'    => [],
        ]));
    }

    public function store(Request $request): Response
    {
        $customerId = (int) $request->param('id');
        $customer = Connection::selectOne('SELECT id, email FROM customers WHERE id = ?', [$customerId]);
        if (!$customer) return Response::notFound();

        [$id, $error] = $this->saveQuote($request, $customerId, null);
        if ($error) {
            SessionManager::flash('error', $error);
            return Response::redirect('/admin/musteriler/' . $customerId . '/teklif-olustur');
        }

        SessionManager::flash('success', 'Teklif oluşturuldu.');
        return Response::redirect('/admin/musteriler/' . $customerId . '#teklif');
    }

    public function edit(Request $request): Response
    {
        $quoteId = (int) $request->param('quoteId');
        $quote = Connection::selectOne('SELECT * FROM quotes WHERE id = ?', [$quoteId]);
        if (!$quote) return Response::notFound();
        $customer = Connection::selectOne('SELECT id, email, first_name, last_name FROM customers WHERE id = ?', [$quote['customer_id']]);
        $items = Connection::select('SELECT * FROM quote_items WHERE quote_id = ? ORDER BY sort_order ASC, id ASC', [$quoteId]);

        return Response::html((new View())->render('admin::quotes.form', [
            'title'    => 'Teklif #' . $quote['id'] . ' — ' . $quote['quote_number'],
            'customer' => $customer,
            'quote'    => $quote,
            'items'    => $items,
        ]));
    }

    public function update(Request $request): Response
    {
        $quoteId = (int) $request->param('quoteId');
        $quote = Connection::selectOne('SELECT * FROM quotes WHERE id = ?', [$quoteId]);
        if (!$quote) return Response::notFound();
        if ($quote['status'] !== 'draft') {
            SessionManager::flash('error', 'Sadece taslak teklifler düzenlenebilir.');
            return Response::redirect('/admin/teklifler/' . $quoteId);
        }

        [, $error] = $this->saveQuote($request, (int) $quote['customer_id'], $quoteId);
        if ($error) {
            SessionManager::flash('error', $error);
            return Response::redirect('/admin/teklifler/' . $quoteId . '/duzenle');
        }

        SessionManager::flash('success', 'Teklif güncellendi.');
        return Response::redirect('/admin/teklifler/' . $quoteId);
    }

    public function show(Request $request): Response
    {
        $quoteId = (int) $request->param('quoteId');
        $quote = Connection::selectOne('SELECT * FROM quotes WHERE id = ?', [$quoteId]);
        if (!$quote) return Response::notFound();
        $customer = Connection::selectOne('SELECT id, email, first_name, last_name FROM customers WHERE id = ?', [$quote['customer_id']]);
        $items = Connection::select('SELECT * FROM quote_items WHERE quote_id = ? ORDER BY sort_order ASC, id ASC', [$quoteId]);

        return Response::html((new View())->render('admin::quotes.show', [
            'title'    => 'Teklif — ' . $quote['quote_number'],
            'customer' => $customer,
            'quote'    => $quote,
            'items'    => $items,
        ]));
    }

    public function send(Request $request): Response
    {
        $quoteId = (int) $request->param('quoteId');
        $quote = Connection::selectOne('SELECT * FROM quotes WHERE id = ?', [$quoteId]);
        if (!$quote) return Response::notFound();

        Connection::update('quotes', [
            'status'     => 'sent',
            'sent_at'    => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$quoteId]);

        try {
            $customer = Connection::selectOne('SELECT email FROM customers WHERE id = ?', [$quote['customer_id']]);
            if ($customer && function_exists('ao_send_email_notification')) {
                ao_send_email_notification(
                    (string) $customer['email'],
                    'Teklifiniz: ' . $quote['subject'],
                    "Merhaba,\n\n" . $quote['subject'] . " için hazırladığımız teklif ekte/panelinizdedir.\nToplam: " . number_format((float) $quote['total'], 2) . ' ' . $quote['currency'],
                    'quote_sent'
                );
            }
        } catch (\Throwable) {}

        SessionManager::flash('success', 'Teklif gönderildi olarak işaretlendi.');
        return Response::redirect('/admin/teklifler/' . $quoteId);
    }

    /** Teklifi kabul et → faturaya çevir. */
    public function accept(Request $request): Response
    {
        $quoteId = (int) $request->param('quoteId');
        $quote = Connection::selectOne('SELECT * FROM quotes WHERE id = ?', [$quoteId]);
        if (!$quote) return Response::notFound();
        if ($quote['status'] === 'accepted' && !empty($quote['converted_invoice_id'])) {
            SessionManager::flash('error', 'Bu teklif zaten faturaya çevrilmiş.');
            return Response::redirect('/admin/teklifler/' . $quoteId);
        }

        $items = Connection::select('SELECT * FROM quote_items WHERE quote_id = ?', [$quoteId]);
        if (!$items) {
            SessionManager::flash('error', 'Teklifte kalem yok, faturaya çevrilemez.');
            return Response::redirect('/admin/teklifler/' . $quoteId);
        }

        $invoiceItems = array_map(fn($it) => [
            'description' => $it['description'],
            'quantity'    => (int) $it['quantity'],
            'unit_price'  => (float) $it['unit_price'],
            'tax_rate'    => (float) $it['tax_rate'],
        ], $items);

        $invoiceId = InvoiceService::createManual(
            (int) $quote['customer_id'],
            $invoiceItems,
            (string) $quote['currency'],
            'Teklif #' . $quote['quote_number'] . ' onayından oluşturuldu.'
        );

        Connection::update('quotes', [
            'status'               => 'accepted',
            'accepted_at'          => date('Y-m-d H:i:s'),
            'converted_invoice_id' => $invoiceId,
            'updated_at'           => date('Y-m-d H:i:s'),
        ], 'id = ?', [$quoteId]);

        \App\Services\Logger\ActivityLog::log('quote.accepted', 'quote', $quoteId, 'Teklif kabul edildi, fatura #' . $invoiceId . ' oluşturuldu.');
        SessionManager::flash('success', "✓ Teklif kabul edildi, fatura #$invoiceId oluşturuldu.");
        return Response::redirect('/admin/musteriler/' . $quote['customer_id'] . '#teklif');
    }

    public function decline(Request $request): Response
    {
        $quoteId = (int) $request->param('quoteId');
        $quote = Connection::selectOne('SELECT * FROM quotes WHERE id = ?', [$quoteId]);
        if (!$quote) return Response::notFound();

        Connection::update('quotes', [
            'status'      => 'declined',
            'declined_at' => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ], 'id = ?', [$quoteId]);

        SessionManager::flash('success', 'Teklif reddedildi olarak işaretlendi.');
        return Response::redirect('/admin/musteriler/' . $quote['customer_id'] . '#teklif');
    }

    public function destroy(Request $request): Response
    {
        $quoteId = (int) $request->param('quoteId');
        $quote = Connection::selectOne('SELECT customer_id FROM quotes WHERE id = ?', [$quoteId]);
        if (!$quote) return Response::notFound();

        Connection::query('DELETE FROM quote_items WHERE quote_id = ?', [$quoteId]);
        Connection::query('DELETE FROM quotes WHERE id = ?', [$quoteId]);

        SessionManager::flash('success', 'Teklif silindi.');
        return Response::redirect('/admin/musteriler/' . $quote['customer_id'] . '#teklif');
    }

    /** @return array{0:?int,1:?string} */
    private function saveQuote(Request $request, int $customerId, ?int $quoteId): array
    {
        $subject = trim((string) $request->input('subject', ''));
        if ($subject === '') {
            return [null, 'Konu zorunludur.'];
        }

        $descriptions = (array) $request->input('item_description', []);
        $quantities   = (array) $request->input('item_quantity', []);
        $unitPrices   = (array) $request->input('item_unit_price', []);
        $taxRates     = (array) $request->input('item_tax_rate', []);

        $items = [];
        $subtotal = 0.0;
        $taxTotal = 0.0;
        foreach ($descriptions as $i => $desc) {
            $desc = trim((string) $desc);
            $qty = max(1, (int) ($quantities[$i] ?? 1));
            $price = (float) ($unitPrices[$i] ?? 0);
            $tax = (float) ($taxRates[$i] ?? 0);
            if ($desc === '' || $price <= 0) continue;
            $lineTotal = $qty * $price;
            $subtotal += $lineTotal;
            $taxTotal += $lineTotal * ($tax / 100);
            $items[] = compact('desc', 'qty', 'price', 'tax', 'lineTotal');
        }
        if (!$items) {
            return [null, 'En az bir geçerli kalem eklemelisin.'];
        }
        $total = $subtotal + $taxTotal;
        $currency = strtoupper((string) $request->input('currency', 'TRY')) ?: 'TRY';
        $validUntil = trim((string) $request->input('valid_until', '')) ?: null;
        $notes = trim((string) $request->input('notes', '')) ?: null;

        if ($quoteId === null) {
            $quoteId = Connection::insert('quotes', [
                'quote_number' => self::generateNumber(),
                'customer_id'  => $customerId,
                'subject'      => $subject,
                'status'       => 'draft',
                'valid_until'  => $validUntil,
                'subtotal'     => $subtotal,
                'tax_total'    => $taxTotal,
                'total'        => $total,
                'currency'     => $currency,
                'notes'        => $notes,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);
        } else {
            Connection::update('quotes', [
                'subject'     => $subject,
                'valid_until' => $validUntil,
                'subtotal'    => $subtotal,
                'tax_total'   => $taxTotal,
                'total'       => $total,
                'currency'    => $currency,
                'notes'       => $notes,
                'updated_at'  => date('Y-m-d H:i:s'),
            ], 'id = ?', [$quoteId]);
            Connection::query('DELETE FROM quote_items WHERE quote_id = ?', [$quoteId]);
        }

        foreach ($items as $i => $it) {
            Connection::insert('quote_items', [
                'quote_id'   => $quoteId,
                'description'=> $it['desc'],
                'quantity'   => $it['qty'],
                'unit_price' => $it['price'],
                'tax_rate'   => $it['tax'],
                'line_total' => $it['lineTotal'],
                'sort_order' => $i,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return [$quoteId, null];
    }

    private static function generateNumber(): string
    {
        return 'QUO-' . date('Ym') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }
}
