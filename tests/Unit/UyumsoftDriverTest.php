<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\EInvoice\Drivers\UyumsoftDriver;
use PHPUnit\Framework\TestCase;

/**
 * Uyumsoft driver — envelope + UBL builder + hata yönetimi testleri.
 * Gerçek SOAP çağrısı yapmaz (test hesabı gerektirir).
 */
final class UyumsoftDriverTest extends TestCase
{
    public function test_returns_error_when_no_credentials(): void
    {
        $d = new UyumsoftDriver('', '', true);
        $r = $d->submit(['invoice_number' => 'INV-001']);
        $this->assertFalse($r['success']);
        $this->assertStringContainsString('ayarlanmamış', $r['error']);
    }

    public function test_id_and_label(): void
    {
        $d = new UyumsoftDriver('u', 'p', true);
        $this->assertSame('uyumsoft', $d->id());
        $this->assertStringContainsString('Uyumsoft', $d->label());
        $this->assertStringContainsString('Test', $d->label());
    }

    public function test_production_label_no_test_suffix(): void
    {
        $d = new UyumsoftDriver('u', 'p', false);
        $this->assertStringNotContainsString('Test', $d->label());
    }

    public function test_status_returns_error_without_credentials(): void
    {
        $d = new UyumsoftDriver('', '', true);
        $r = $d->status('abc-uuid');
        $this->assertSame('error', $r['status']);
    }

    public function test_download_pdf_null_without_credentials(): void
    {
        $d = new UyumsoftDriver('', '', true);
        $this->assertNull($d->downloadPdf('abc-uuid'));
    }

    public function test_is_registered_taxpayer_false_without_credentials(): void
    {
        $d = new UyumsoftDriver('', '', true);
        $this->assertFalse($d->isRegisteredTaxpayer('12345678901'));
    }

    public function test_test_connection_reports_missing_creds(): void
    {
        $d = new UyumsoftDriver('', '', true);
        $r = $d->testConnection();
        $this->assertFalse($r['ok']);
        $this->assertNotEmpty($r['error']);
    }

    public function test_ubl_builder_reflects_invoice_fields(): void
    {
        // Reflection ile private static'a eriş
        $r = new \ReflectionMethod(UyumsoftDriver::class, 'buildUblInvoice');
        $r->setAccessible(true);
        $xml = $r->invoke(null, [
            'invoice_number' => 'INV-2026-001',
            'issue_date'     => '2026-07-27',
            'currency'       => 'TRY',
            'customer'       => ['name' => 'Ali Vali', 'tax_id' => '12345678901', 'email' => 'a@b.c'],
            'totals'         => ['subtotal' => 100, 'tax' => 20, 'total' => 120],
            'items'          => [
                ['description' => 'Hosting Business', 'quantity' => 1, 'unit_price' => 100, 'line_total' => 100, 'tax_rate' => 20],
            ],
        ]);
        $this->assertStringContainsString('INV-2026-001', $xml);
        $this->assertStringContainsString('2026-07-27', $xml);
        $this->assertStringContainsString('Ali Vali', $xml);
        $this->assertStringContainsString('12345678901', $xml);
        $this->assertStringContainsString('Hosting Business', $xml);
        $this->assertStringContainsString('urn:oasis:names:specification:ubl:schema:xsd:Invoice-2', $xml);
        $this->assertStringContainsString('EARSIVFATURA', $xml);
    }

    public function test_envelope_includes_wsse_credentials(): void
    {
        $r = new \ReflectionMethod(UyumsoftDriver::class, 'soapEnvelope');
        $r->setAccessible(true);
        $d = new UyumsoftDriver('myuser', 'mypass', true);
        $xml = $r->invoke($d, 'CheckUser', ['vknTckn' => '11111111111']);
        $this->assertStringContainsString('<o:Username>myuser</o:Username>', $xml);
        $this->assertStringContainsString('<o:Password>mypass</o:Password>', $xml);
        $this->assertStringContainsString('<tem:CheckUser>', $xml);
        $this->assertStringContainsString('11111111111', $xml);
    }

    public function test_envelope_escapes_xml_special_chars(): void
    {
        $r = new \ReflectionMethod(UyumsoftDriver::class, 'soapEnvelope');
        $r->setAccessible(true);
        $d = new UyumsoftDriver('u<>&"', 'p"><', true);
        $xml = $r->invoke($d, 'CheckUser', ['vknTckn' => '11']);
        // XML özel karakterleri escape edilmiş olmalı
        $this->assertStringNotContainsString('<o:Username>u<>&"', $xml);
        $this->assertStringContainsString('&lt;', $xml);
        $this->assertStringContainsString('&gt;', $xml);
        $this->assertStringContainsString('&amp;', $xml);
    }
}
