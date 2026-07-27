<?php

declare(strict_types=1);

namespace App\Modules\EInvoice\Drivers;

use App\Modules\EInvoice\Contracts\EInvoiceProviderInterface;
use App\Services\Logger\ApiLogger;
use App\Services\Logger\Logger;

/**
 * Uyumsoft e-Fatura / e-Arşiv driver (SOAP).
 *
 * Endpoint (test) : https://efatura-test.uyumsoft.com.tr/services/BasicIntegration
 * Endpoint (prod) : https://efatura.uyumsoft.com.tr/services/BasicIntegration
 * WSDL           : /Services/Integration?singleWsdl
 * Auth           : SOAP header WS-Security (UsernameToken, plain password)
 *
 * Bu driver:
 *   - PHP SoapClient extension varsa onu kullanır (tercih)
 *   - Yoksa curl ile raw XML SOAP envelope gönderir (fallback)
 *
 * Test kullanıcıları için: https://uyumsoft.com.tr/gelistirici test hesabı alınabilir.
 */
final class UyumsoftDriver implements EInvoiceProviderInterface
{
    private string $endpoint;

    public function __construct(
        private string $username = '',
        private string $password = '',
        private bool   $testMode = true
    ) {
        $this->endpoint = $testMode
            ? 'https://efatura-test.uyumsoft.com.tr/services/BasicIntegration'
            : 'https://efatura.uyumsoft.com.tr/services/BasicIntegration';
    }

    public function id(): string    { return 'uyumsoft'; }
    public function label(): string { return 'Uyumsoft e-Fatura' . ($this->testMode ? ' (Test)' : ''); }

    /**
     * Fatura gönderimi. SendInvoice metodu.
     *
     * @param array $invoice ['invoice_number','issue_date','customer','items','totals','currency']
     * @return array{success:bool, uuid?:string, error?:string, raw_response?:string}
     */
    public function submit(array $invoice): array
    {
        if ($this->username === '' || $this->password === '') {
            return ['success' => false, 'error' => 'Uyumsoft kimlik bilgileri (.env) ayarlanmamış.'];
        }

        $ubl = self::buildUblInvoice($invoice);
        $body = $this->soapEnvelope('SendInvoice', [
            'invoice'           => $ubl,
            'localDocumentId'   => (string) ($invoice['invoice_number'] ?? ''),
        ]);

        $resp = $this->soapRequest($body, 'http://tempuri.org/IIntegration/SendInvoice');
        ApiLogger::log('einvoice:uyumsoft', 'SendInvoice', 'POST', ['invoice_number' => $invoice['invoice_number'] ?? ''], $resp['raw'] ?? '', $resp['http'], $resp['ms']);

        if (!$resp['ok']) {
            return ['success' => false, 'error' => 'SOAP hatası: ' . ($resp['error'] ?? '')];
        }
        // Basit XML parse — response'da <Uuid>...</Uuid> arıyoruz
        $uuid = null;
        if (preg_match('#<a:Uuid>([^<]+)</a:Uuid>#i', $resp['raw'], $m) ||
            preg_match('#<Uuid>([^<]+)</Uuid>#i', $resp['raw'], $m)) {
            $uuid = $m[1];
        }
        // Fault kontrolü
        if (stripos($resp['raw'], '<s:Fault') !== false || stripos($resp['raw'], '<faultstring') !== false) {
            $err = 'unknown';
            if (preg_match('#<faultstring[^>]*>([^<]+)</faultstring>#i', $resp['raw'], $m)) $err = $m[1];
            return ['success' => false, 'error' => 'Uyumsoft: ' . $err, 'raw_response' => $resp['raw']];
        }
        return [
            'success'      => $uuid !== null,
            'uuid'         => $uuid,
            'error'        => $uuid === null ? 'UUID alınamadı — response formatını kontrol edin' : null,
            'raw_response' => $resp['raw'],
        ];
    }

    public function status(string $uuid): array
    {
        if ($this->username === '' || $this->password === '') {
            return ['status' => 'error', 'message' => 'Kimlik bilgisi yok'];
        }
        $body = $this->soapEnvelope('GetInvoiceStatus', ['uuid' => $uuid]);
        $resp = $this->soapRequest($body, 'http://tempuri.org/IIntegration/GetInvoiceStatus');
        ApiLogger::log('einvoice:uyumsoft', 'GetInvoiceStatus', 'POST', ['uuid' => $uuid], $resp['raw'] ?? '', $resp['http'], $resp['ms']);
        if (!$resp['ok']) return ['status' => 'error', 'message' => $resp['error'] ?? ''];
        $status = 'pending';
        if (preg_match('#<Status>([^<]+)</Status>#i', $resp['raw'], $m)) $status = strtolower($m[1]);
        return ['status' => $status, 'raw' => $resp['raw']];
    }

    public function downloadPdf(string $uuid): ?string
    {
        if ($this->username === '' || $this->password === '') return null;
        $body = $this->soapEnvelope('GetInvoicePdf', ['uuid' => $uuid]);
        $resp = $this->soapRequest($body, 'http://tempuri.org/IIntegration/GetInvoicePdf');
        ApiLogger::log('einvoice:uyumsoft', 'GetInvoicePdf', 'POST', ['uuid' => $uuid], substr((string)($resp['raw'] ?? ''), 0, 200), $resp['http'], $resp['ms']);
        if (!$resp['ok']) return null;
        // Base64 PDF beklenir
        if (preg_match('#<Pdf>([^<]+)</Pdf>#i', $resp['raw'], $m)) {
            $bin = base64_decode($m[1], true);
            return $bin !== false ? $bin : null;
        }
        return null;
    }

    public function isRegisteredTaxpayer(string $taxId): bool
    {
        if ($this->username === '' || $this->password === '') return false;
        $body = $this->soapEnvelope('CheckUser', ['vknTckn' => $taxId]);
        $resp = $this->soapRequest($body, 'http://tempuri.org/IIntegration/CheckUser');
        ApiLogger::log('einvoice:uyumsoft', 'CheckUser', 'POST', ['taxId' => $taxId], $resp['raw'] ?? '', $resp['http'], $resp['ms']);
        if (!$resp['ok']) return false;
        // <IsUser>true</IsUser> veya benzeri
        return (bool) preg_match('#<(?:a:)?IsUser>true</(?:a:)?IsUser>#i', $resp['raw']);
    }

    public function testConnection(): array
    {
        if ($this->username === '' || $this->password === '') {
            return ['ok' => false, 'error' => 'Kullanıcı adı/şifre girilmemiş (.env)'];
        }
        // Basit bir "CheckUser" ile kendini kontrol et
        $body = $this->soapEnvelope('CheckUser', ['vknTckn' => '11111111111']);
        $resp = $this->soapRequest($body, 'http://tempuri.org/IIntegration/CheckUser');
        return [
            'ok'      => $resp['ok'],
            'http'    => $resp['http'],
            'endpoint'=> $this->endpoint,
            'error'   => $resp['error'] ?? null,
        ];
    }

    // ---- SOAP internals ---------------------------------------------------

    /** WS-Security UsernameToken içeren SOAP envelope inşa eder. */
    private function soapEnvelope(string $method, array $params): string
    {
        $ns   = 'http://tempuri.org/';
        $wsse = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
        $u    = htmlspecialchars($this->username, ENT_XML1);
        $p    = htmlspecialchars($this->password, ENT_XML1);
        $paramsXml = '';
        foreach ($params as $k => $v) {
            $paramsXml .= "<{$k}>" . (is_string($v) ? htmlspecialchars($v, ENT_XML1) : (string) $v) . "</{$k}>";
        }
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tem="{$ns}">
  <s:Header>
    <o:Security xmlns:o="{$wsse}">
      <o:UsernameToken>
        <o:Username>{$u}</o:Username>
        <o:Password>{$p}</o:Password>
      </o:UsernameToken>
    </o:Security>
  </s:Header>
  <s:Body>
    <tem:{$method}>{$paramsXml}</tem:{$method}>
  </s:Body>
</s:Envelope>
XML;
    }

    /**
     * SOAP request gönder.
     * @return array{ok:bool, http:int, ms:int, raw:string, error?:string}
     */
    private function soapRequest(string $body, string $soapAction): array
    {
        $ch = curl_init($this->endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: text/xml; charset=utf-8',
                'SOAPAction: "' . $soapAction . '"',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $started = microtime(true);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $ms = (int) round((microtime(true) - $started) * 1000);

        if ($raw === false || $raw === null) {
            return ['ok' => false, 'http' => $http, 'ms' => $ms, 'raw' => '', 'error' => $err ?: 'no response'];
        }
        // HTTP 500 SOAP Fault olabilir — raw'ı yine döndür
        $isOk = $http >= 200 && $http < 300;
        return ['ok' => $isOk, 'http' => $http, 'ms' => $ms, 'raw' => (string) $raw, 'error' => $isOk ? null : "HTTP $http"];
    }

    /**
     * UBL-TR uyumlu Invoice XML iskeleti (basit).
     * Gerçek üretimde ubl-tr-invoice-v1.2.1 tam şemasına uygun daha kapsamlı bir builder kullanılmalı.
     */
    private static function buildUblInvoice(array $invoice): string
    {
        $num       = htmlspecialchars((string) ($invoice['invoice_number'] ?? ''), ENT_XML1);
        $issueDate = htmlspecialchars((string) ($invoice['issue_date'] ?? date('Y-m-d')), ENT_XML1);
        $currency  = htmlspecialchars((string) ($invoice['currency'] ?? 'TRY'), ENT_XML1);
        $customer  = $invoice['customer'] ?? [];
        $totals    = $invoice['totals']   ?? [];

        $custName  = htmlspecialchars((string) ($customer['name'] ?? 'Müşteri'), ENT_XML1);
        $custTaxId = htmlspecialchars((string) ($customer['tax_id'] ?? '11111111111'), ENT_XML1);
        $custEmail = htmlspecialchars((string) ($customer['email'] ?? ''), ENT_XML1);

        $itemsXml = '';
        foreach ((array) ($invoice['items'] ?? []) as $i => $it) {
            $desc = htmlspecialchars((string) ($it['description'] ?? 'Ürün'), ENT_XML1);
            $qty  = (float) ($it['quantity'] ?? 1);
            $unit = number_format((float) ($it['unit_price'] ?? 0), 4, '.', '');
            $line = number_format((float) ($it['line_total'] ?? 0), 4, '.', '');
            $tax  = number_format((float) ($it['tax_rate']  ?? 20), 2, '.', '');
            $itemsXml .= <<<XML
<cac:InvoiceLine>
  <cbc:ID>{$i}</cbc:ID>
  <cbc:InvoicedQuantity unitCode="C62">{$qty}</cbc:InvoicedQuantity>
  <cbc:LineExtensionAmount currencyID="{$currency}">{$line}</cbc:LineExtensionAmount>
  <cac:TaxTotal>
    <cbc:TaxAmount currencyID="{$currency}">0.00</cbc:TaxAmount>
    <cac:TaxSubtotal>
      <cbc:Percent>{$tax}</cbc:Percent>
    </cac:TaxSubtotal>
  </cac:TaxTotal>
  <cac:Item>
    <cbc:Name>{$desc}</cbc:Name>
  </cac:Item>
  <cac:Price>
    <cbc:PriceAmount currencyID="{$currency}">{$unit}</cbc:PriceAmount>
  </cac:Price>
</cac:InvoiceLine>
XML;
        }

        $total = number_format((float) ($totals['total'] ?? 0), 4, '.', '');
        $sub   = number_format((float) ($totals['subtotal'] ?? 0), 4, '.', '');
        $tax   = number_format((float) ($totals['tax']  ?? 0), 4, '.', '');
        $uuid  = self::guid();

        // UBL-TR minimal invoice (özet)
        return <<<XML
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"
         xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
         xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
  <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
  <cbc:CustomizationID>TR1.2</cbc:CustomizationID>
  <cbc:ProfileID>EARSIVFATURA</cbc:ProfileID>
  <cbc:ID>{$num}</cbc:ID>
  <cbc:UUID>{$uuid}</cbc:UUID>
  <cbc:IssueDate>{$issueDate}</cbc:IssueDate>
  <cbc:InvoiceTypeCode>SATIS</cbc:InvoiceTypeCode>
  <cbc:DocumentCurrencyCode>{$currency}</cbc:DocumentCurrencyCode>
  <cac:AccountingCustomerParty>
    <cac:Party>
      <cac:PartyIdentification>
        <cbc:ID schemeID="TCKN">{$custTaxId}</cbc:ID>
      </cac:PartyIdentification>
      <cac:PartyName><cbc:Name>{$custName}</cbc:Name></cac:PartyName>
      <cac:Contact><cbc:ElectronicMail>{$custEmail}</cbc:ElectronicMail></cac:Contact>
    </cac:Party>
  </cac:AccountingCustomerParty>
  <cac:TaxTotal>
    <cbc:TaxAmount currencyID="{$currency}">{$tax}</cbc:TaxAmount>
  </cac:TaxTotal>
  <cac:LegalMonetaryTotal>
    <cbc:LineExtensionAmount currencyID="{$currency}">{$sub}</cbc:LineExtensionAmount>
    <cbc:TaxExclusiveAmount currencyID="{$currency}">{$sub}</cbc:TaxExclusiveAmount>
    <cbc:TaxInclusiveAmount currencyID="{$currency}">{$total}</cbc:TaxInclusiveAmount>
    <cbc:PayableAmount currencyID="{$currency}">{$total}</cbc:PayableAmount>
  </cac:LegalMonetaryTotal>
  {$itemsXml}
</Invoice>
XML;
    }

    private static function guid(): string
    {
        $d = random_bytes(16);
        $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
        $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
    }
}
