<?php

declare(strict_types=1);

namespace App\Services\Currency;

use App\Core\Database\Connection;
use App\Services\Logger\ApiLogger;
use App\Services\Logger\Logger;

/**
 * Kur güncelleme servisi — TCMB birincil kaynak, exchangerate.host fallback.
 *
 * TCMB XML: https://www.tcmb.gov.tr/kurlar/today.xml
 *   → Günlük gösterge kurları (hafta içi 15:30 sonrası, hafta sonu Cuma kuru)
 *   → ForexBuying/ForexSelling değerleri "1 birim yabancı = X TRY" formatında
 *
 * exchangerate.host (fallback, ücretsiz):
 *   → https://api.exchangerate.host/latest?base=TRY&symbols=USD,EUR,GBP,...
 *   → Aynı formatı vermez → 1/rate ile TRY tabanına çevrilir
 *
 * Sonuç: currency_rates tablosuna yazılır ('source' kolonuyla).
 */
final class CurrencyRateUpdater
{
    /** @return array{updated:int,skipped:int,errors:array<int,string>,source:string} */
    public static function updateAll(): array
    {
        $currencies = self::activeCurrencies();
        $rates = self::fetchFromTcmb($currencies);
        $source = 'tcmb';

        if (empty($rates)) {
            Logger::warning('TCMB kur alınamadı, exchangerate.host deneniyor');
            $rates = self::fetchFromExchangerateHost($currencies);
            $source = 'exchangerate.host';
        }

        if (empty($rates)) {
            return ['updated' => 0, 'skipped' => 0, 'errors' => ['Hiçbir kaynak yanıt vermedi'], 'source' => 'none'];
        }

        $updated = 0;
        $skipped = 0;
        $errors = [];
        $now = date('Y-m-d H:i:s');

        foreach ($rates as $code => $rate) {
            if ($code === 'TRY') { $skipped++; continue; }
            if ($rate <= 0) { $errors[] = "$code: 0 rate"; continue; }
            try {
                $exists = Connection::selectOne("SELECT id FROM currency_rates WHERE currency = ?", [$code]);
                if ($exists) {
                    Connection::update('currency_rates',
                        ['rate' => $rate, 'source' => $source, 'updated_at' => $now],
                        'id = ?', [$exists['id']]
                    );
                } else {
                    Connection::insert('currency_rates', [
                        'currency'       => $code,
                        'symbol'         => self::symbol($code),
                        'rate'           => $rate,
                        'margin_percent' => 0,
                        'is_active'      => 1,
                        'source'         => $source,
                        'updated_at'     => $now,
                    ]);
                }
                $updated++;
            } catch (\Throwable $e) {
                $errors[] = "$code: " . $e->getMessage();
            }
        }

        Logger::info('Kurlar güncellendi', ['source' => $source, 'updated' => $updated, 'errors' => count($errors)]);
        return ['updated' => $updated, 'skipped' => $skipped, 'errors' => $errors, 'source' => $source];
    }

    /** @return string[] Sadece TRY dışındaki aktif para birimleri */
    private static function activeCurrencies(): array
    {
        try {
            $rows = Connection::select("SELECT currency FROM currency_rates WHERE is_active = 1 AND currency != 'TRY'");
            $codes = array_map(fn($r) => strtoupper($r['currency']), $rows);
            if (empty($codes)) return ['USD', 'EUR', 'GBP'];
            return $codes;
        } catch (\Throwable) {
            return ['USD', 'EUR', 'GBP'];
        }
    }

    /**
     * TCMB XML çekimi.
     * Örnek çıktı: <Currency Kod="USD" CurrencyCode="USD"><ForexSelling>32.5410</ForexSelling></Currency>
     * @param string[] $wanted
     * @return array<string,float>
     */
    public static function fetchFromTcmb(array $wanted): array
    {
        $url = 'https://www.tcmb.gov.tr/kurlar/today.xml';
        $started = microtime(true);
        $ctx = stream_context_create(['http' => ['timeout' => 8, 'user_agent' => 'AhostBilisim/1.0']]);
        $xml = @file_get_contents($url, false, $ctx);
        $ms = (int) round((microtime(true) - $started) * 1000);
        ApiLogger::log('tcmb', 'today.xml', 'GET', [], is_string($xml) ? substr($xml, 0, 500) : '', $xml ? 200 : 0, $ms);

        if (!$xml) return [];
        libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml);
        if (!$doc) return [];

        $out = [];
        $wanted = array_map('strtoupper', $wanted);
        foreach ($doc->Currency as $c) {
            $code = strtoupper((string) $c['CurrencyCode']);
            if (!in_array($code, $wanted, true)) continue;
            $sell = (float) str_replace(',', '.', (string) $c->ForexSelling);
            $unit = max(1, (int) $c->Unit);
            if ($sell <= 0) continue;
            $out[$code] = $sell / $unit; // 1 birim yabancı = X TRY
        }
        return $out;
    }

    /**
     * exchangerate.host fallback (base TRY).
     * @param string[] $wanted
     * @return array<string,float>
     */
    public static function fetchFromExchangerateHost(array $wanted): array
    {
        $symbols = implode(',', $wanted);
        $url = 'https://api.exchangerate.host/latest?base=TRY&symbols=' . urlencode($symbols);
        $started = microtime(true);
        $ctx = stream_context_create(['http' => ['timeout' => 8]]);
        $body = @file_get_contents($url, false, $ctx);
        $ms = (int) round((microtime(true) - $started) * 1000);
        ApiLogger::log('exchangerate.host', 'latest', 'GET', ['base'=>'TRY','symbols'=>$symbols], is_string($body) ? substr($body,0,500) : '', $body ? 200 : 0, $ms);

        if (!$body) return [];
        $data = json_decode($body, true);
        if (!is_array($data) || empty($data['rates'])) return [];
        $out = [];
        foreach ($data['rates'] as $code => $rate) {
            $rate = (float) $rate;
            if ($rate <= 0) continue;
            // base=TRY → rate USD=0.031 → 1 USD = 1/0.031 TRY
            $out[strtoupper($code)] = 1.0 / $rate;
        }
        return $out;
    }

    private static function symbol(string $code): string
    {
        return match (strtoupper($code)) {
            'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'TRY' => '₺',
            'JPY' => '¥', 'CHF' => '₣', default => $code,
        };
    }
}
