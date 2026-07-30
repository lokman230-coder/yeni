<?php

declare(strict_types=1);

namespace App\Modules\Ai\Services;

/**
 * AI için sistem prompt üreticisi.
 * Bağlam (public/customer/admin) her istekte ayrı sistem promptu alır.
 * ADMIN AI'ın public'e, PUBLIC AI'ın admin'e yönlendirmesi engellenir.
 */
final class ContextBuilder
{
    public const CTX_PUBLIC   = 'public';
    public const CTX_CUSTOMER = 'customer';
    public const CTX_ADMIN    = 'admin';

    public static function systemPrompt(string $context, array $extra = []): string
    {
        $siteName = 'Ahost Bilişim';
        $base = "Sen {$siteName} platformunun yapay zeka asistanısın. Türkçe cevap ver. Kısa, net ve dostane ol. Bağlam: {$context}.\n";

        return match ($context) {
            self::CTX_ADMIN => $base .
                "Sen ADMIN AI'sın. Sadece admin işlemleriyle ilgili yardım edersin: müşteri arama, ürün yönetimi, siparişler, raporlar, sistem durumu.\n" .
                "KESİN YASAK: Public web sitesi (/, /hosting, /domain vb.) ya da müşteri paneli sayfalarına yönlendirme yapma. Kullanıcı öyle isterse 'Bu ekran müşteri/public tarafına aittir' de.\n" .
                "Aksiyon önerirken JSON formatı: {\"action\": \"navigate\", \"url\": \"/admin/...\"}. Sadece /admin/* URL'lerine yönlendir.",

            self::CTX_CUSTOMER => $base .
                "Sen MÜŞTERİ AI'sın. Müşterinin hizmetleri, faturaları, domainleri, destek talepleri ve builder projelerinde yardımcı olursun.\n" .
                "Yönlendirmelerde /panel/... URL'leri kullan. Ödeme/fatura sorgusunda hassas veri talep etme, panelden bakılmasını öner.\n" .
                "Aksiyon önerirken JSON: {\"action\": \"navigate\", \"url\": \"/panel/...\"}",

            default => $base .
                "Sen PUBLIC (ziyaretçi) AI'sın. Ziyaretçilere hizmetleri tanıtır, doğru sayfaya yönlendirirsin.\n" .
                "Kullanıcı 'site oluştur' derse otomatik login'e atma; şu iki seçeneği sun: (1) AI ile tasarlamak için paket al, (2) AI yardımı olmadan demo dene.\n" .
                "Aksiyon önerirken JSON: {\"action\": \"navigate\", \"url\": \"/...\"} - sadece public URL'ler.",
        };
    }

    /**
     * Bir action önerisinin bağlamla uyumlu olup olmadığını kontrol et.
     * @return bool true = izinli, false = bağlam ihlali (reddet)
     */
    public static function isActionAllowed(string $context, string $action, ?string $url = null): bool
    {
        if ($url === null) return true;

        return match ($context) {
            self::CTX_ADMIN    => str_starts_with($url, '/admin'),
            self::CTX_CUSTOMER => str_starts_with($url, '/panel') || str_starts_with($url, '/sepet') || str_starts_with($url, '/odeme'),
            default            => !str_starts_with($url, '/admin') && !str_starts_with($url, '/panel'),
        };
    }
}
