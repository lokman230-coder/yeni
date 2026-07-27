<?php

declare(strict_types=1);

namespace App\Modules\Invoice\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Modules\Payment\PaymentManager;
use App\Services\Auth\AuthService;

/**
 * Müşteri panelinden direkt fatura ödeme.
 * URL: /odeme/{invoiceId}
 * Bakiye / Kart / Havale seçenekleri sunar.
 */
final class InvoicePayController
{
    /** Fatura ödeme sayfası */
    public function show(Request $request): Response
    {
        if (!AuthService::isCustomer()) {
            SessionManager::set('after_login_redirect', $request->path());
            return Response::redirect('/giris');
        }
        $customer = AuthService::customer();
        $id = (int) $request->param('id');

        $invoice = Connection::selectOne(
            "SELECT i.*, o.order_number FROM invoices i LEFT JOIN orders o ON o.id = i.order_id
             WHERE i.id = ? AND i.customer_id = ? LIMIT 1",
            [$id, $customer['id']]
        );
        if (!$invoice) return Response::notFound('Fatura bulunamadı');

        if (in_array($invoice['status'], ['paid','cancelled','refunded'], true)) {
            SessionManager::flash('info', 'Bu fatura zaten ödendi veya iptal edildi.');
            return Response::redirect('/panel/faturalarim');
        }

        // Customer bakiyesi
        $customerFull = Connection::selectOne("SELECT balance FROM customers WHERE id = ?", [$customer['id']]);
        $balance = (float) ($customerFull['balance'] ?? 0);

        return Response::html((new View())->render('invoice::pay', [
            'title'    => 'Fatura #' . $invoice['invoice_number'] . ' Öde',
            'invoice'  => $invoice,
            'customer' => $customer,
            'balance'  => $balance,
            'gateways' => PaymentManager::available(),
        ]));
    }

    /** Fatura ödeme işlemi */
    public function process(Request $request): Response
    {
        if (!AuthService::isCustomer()) return Response::redirect('/giris');
        $customer = AuthService::customer();
        $id = (int) $request->param('id');
        $method = (string) $request->input('method', 'paytr');

        $invoice = Connection::selectOne("SELECT * FROM invoices WHERE id = ? AND customer_id = ?", [$id, $customer['id']]);
        if (!$invoice) return Response::notFound();

        // BAKİYE İLE ÖDEME
        if ($method === 'balance') {
            $amount = (float) $invoice['balance'];
            if (!\App\Services\Credit\CreditService::canPay((int)$customer['id'], $amount)) {
                SessionManager::flash('error', 'Yetersiz bakiye. Önce bakiye yükleyin.');
                return Response::redirect('/panel/bakiye');
            }

            // Bakiyeden düş
            $r = \App\Services\Credit\CreditService::payInvoice((int)$customer['id'], $id, $amount);
            if (!$r['ok']) {
                SessionManager::flash('error', $r['error'] ?? 'Bakiye ile ödeme başarısız');
                return Response::redirect('/odeme/' . $id);
            }

            // Fatura'yı paid işaretle
            Connection::update('invoices', [
                'paid_total' => (float)$invoice['paid_total'] + $amount,
                'balance'    => 0,
                'status'     => 'paid',
                'paid_at'    => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$id]);

            // Payment kaydı
            Connection::insert('payments', [
                'invoice_id'   => $id,
                'order_id'     => $invoice['order_id'],
                'customer_id'  => $customer['id'],
                'method'       => 'balance',
                'amount'       => $amount,
                'currency'     => $invoice['currency'],
                'status'       => 'success',
                'processed_at' => date('Y-m-d H:i:s'),
                'notes'        => 'Bakiye ile ödendi (kredi #' . $r['credit_id'] . ')',
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);

            // Sipariş varsa paid yap
            if ($invoice['order_id']) {
                Connection::update('orders', ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')],
                    'id = ? AND status = ?', [$invoice['order_id'], 'pending']);
            }

            // Bakiye yükleme faturasıysa: bakiyeyi de yükle
            if (str_starts_with($invoice['invoice_number'], 'BKY-')) {
                \App\Services\Credit\CreditService::record((int)$customer['id'], $amount, 'payment', [
                    'invoice_id'  => $id,
                    'description' => 'Bakiye yükleme: ' . $invoice['invoice_number'],
                ]);
            }

            SessionManager::flash('success', "✓ Fatura bakiye ile ödendi. Yeni bakiye: " . number_format($r['balance'], 2) . " TRY");
            return Response::redirect('/panel/faturalarim');
        }

        // HAVALE
        if ($method === 'bank_transfer') {
            // Bekleyen payment oluştur (admin onayı ile paid olacak)
            Connection::insert('payments', [
                'invoice_id'  => $id,
                'order_id'    => $invoice['order_id'],
                'customer_id' => $customer['id'],
                'method'      => 'bank_transfer',
                'amount'      => (float)$invoice['balance'],
                'currency'    => $invoice['currency'],
                'status'      => 'pending',
                'notes'       => 'Havale bildirimi — admin onayı bekleniyor',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);

            $iban = \App\Services\Settings\SettingsManager::get('company.iban', 'Ayarlar > Firma\'dan IBAN gir');
            $bank = \App\Services\Settings\SettingsManager::get('company.bank_name', '');

            SessionManager::flash('info',
                "🏦 Havale Bilgileri:\n\n" .
                "IBAN: $iban\n" .
                "Banka: $bank\n" .
                "Tutar: " . number_format((float)$invoice['balance'], 2) . " " . $invoice['currency'] . "\n" .
                "Açıklama: FATURA-" . $invoice['invoice_number'] . "\n\n" .
                "Havale sonrası ödemeniz admin onayı ile tamamlanır (genelde 1-4 saat)."
            );
            return Response::redirect('/panel/faturalarim');
        }

        // Gateway (PayTR/iyzico/Papara/Shopier) — çekout process'e delegate
        // Basitleştirilmiş: PayTR init edip formu gösterir
        SessionManager::flash('info', "Ödeme sağlayıcı ($method) üzerinden ödeme başlatılıyor... (Test modu — canlıda gateway kredensiyalleri gerekli)");
        return Response::redirect('/panel/faturalarim');
    }
}
