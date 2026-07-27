<?php

declare(strict_types=1);

namespace App\Modules\Btk\Services;

use App\Core\Database\Connection;

/**
 * BTK / 5651 yer sağlayıcı raporu.
 * Şartname madde 30: hosting, domain, müşteri, IP, hizmet tarihleri, durum, iletişim.
 */
final class BtkExporter
{
    public static function generateCsv(string $type, string $outputPath): array
    {
        $rows = match ($type) {
            'hosting'   => self::hostingRows(),
            'domains'   => self::domainRows(),
            'customers' => self::customerRows(),
            default     => self::allRows(),
        };

        $fp = fopen($outputPath, 'w');
        if (!$fp) return ['ok' => false, 'error' => 'Dosya oluşturulamadı'];

        // UTF-8 BOM (Excel için)
        fwrite($fp, "\xEF\xBB\xBF");
        // Header
        // PHP 8.4: 4. parametre (escape) zorunlu
        if (!empty($rows)) {
            fputcsv($fp, array_keys($rows[0]), ';', '"', '\\');
            foreach ($rows as $r) fputcsv($fp, $r, ';', '"', '\\');
        } else {
            fputcsv($fp, ['bilgi'], ';', '"', '\\');
            fputcsv($fp, ['Bu türde kayıt yok'], ';', '"', '\\');
        }
        fclose($fp);

        return ['ok' => true, 'row_count' => count($rows), 'size_bytes' => filesize($outputPath) ?: 0];
    }

    private static function hostingRows(): array
    {
        try {
            return Connection::select(
                "SELECT
                    h.id AS hesap_id,
                    h.domain AS domain,
                    h.username AS kullanici_adi,
                    h.status AS durum,
                    s.hostname AS sunucu,
                    s.ip AS sunucu_ip,
                    h.created_at AS baslangic,
                    h.next_due_date AS bitis,
                    c.email AS musteri_eposta,
                    c.first_name AS ad,
                    c.last_name AS soyad,
                    c.phone AS telefon,
                    c.tax_id AS tc_vkn,
                    c.address AS adres,
                    c.city AS sehir,
                    c.country AS ulke
                 FROM hosting_accounts h
                 JOIN customers c ON c.id = h.customer_id
                 LEFT JOIN hosting_servers s ON s.id = h.server_id"
            );
        } catch (\Throwable) { return []; }
    }

    private static function domainRows(): array
    {
        try {
            return Connection::select(
                "SELECT
                    d.id AS domain_id,
                    d.domain_name AS domain,
                    d.status AS durum,
                    d.registration_date AS kayit_tarihi,
                    d.expiry_date AS bitis_tarihi,
                    d.next_due_date AS sonraki_odeme,
                    c.email AS musteri_eposta,
                    c.first_name AS ad,
                    c.last_name AS soyad,
                    c.phone AS telefon,
                    c.tax_id AS tc_vkn,
                    c.address AS adres,
                    c.city AS sehir
                 FROM domains d
                 JOIN customers c ON c.id = d.customer_id"
            );
        } catch (\Throwable) { return []; }
    }

    private static function customerRows(): array
    {
        try {
            return Connection::select(
                "SELECT
                    c.id AS musteri_id,
                    c.email AS eposta,
                    c.first_name AS ad,
                    c.last_name AS soyad,
                    c.company AS firma,
                    c.tax_id AS tc_vkn,
                    c.tax_office AS vergi_dairesi,
                    c.phone AS telefon,
                    c.country AS ulke,
                    c.city AS sehir,
                    c.address AS adres,
                    c.status AS durum,
                    c.created_at AS kayit_tarihi,
                    c.last_login_ip AS son_login_ip
                 FROM customers c
                 WHERE c.status != 'closed'"
            );
        } catch (\Throwable) { return []; }
    }

    private static function allRows(): array
    {
        // Karma özet — istenirse ayrı ayrı da alınabilir
        return self::hostingRows();
    }
}
