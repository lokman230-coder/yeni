<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\Auth\AuthService;
use App\Services\Mail\Mailer;

/**
 * Admin > Ayarlar > SMTP Test butonu için AJAX endpoint.
 * .env veya settings tablosundaki mevcut SMTP ayarlarını kullanır.
 */
final class SmtpTestController
{
    public function send(Request $request): Response
    {
        $admin = AuthService::admin();
        $to = trim((string) $request->input('to', $admin['email'] ?? ''));
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return Response::json(['ok' => false, 'error' => 'Geçerli bir e-posta adresi girin.']);
        }

        $subject = 'Ahost Bilişim — SMTP Test (' . date('Y-m-d H:i:s') . ')';
        $body = '<div style="font-family:system-ui,sans-serif;padding:20px">
            <h2 style="color:#059669">✓ SMTP çalışıyor!</h2>
            <p>Bu e-posta <strong>Ahost Bilişim</strong> admin panelinden gönderilen bir test mesajıdır.</p>
            <ul>
                <li>Gönderen: ' . htmlspecialchars((string) env('MAIL_FROM_ADDRESS', 'noreply@ahost.web.tr'), ENT_HTML5) . '</li>
                <li>Zaman: ' . date('c') . '</li>
                <li>Host: ' . htmlspecialchars((string) env('MAIL_HOST', '-'), ENT_HTML5) . ':' . htmlspecialchars((string) env('MAIL_PORT', '-'), ENT_HTML5) . '</li>
            </ul>
            <p style="color:#6b7280;font-size:12px">Bu mail gerçek müşterilere gönderilmedi.</p>
        </div>';

        try {
            $r = Mailer::sendRaw($to, $subject, $body, $admin['first_name'] ?? null,
                "SMTP test - " . date('Y-m-d H:i:s'), true); // immediate
            if ($r['ok']) {
                return Response::json(['ok' => true, 'message' => "✓ Test mail gönderildi: $to"]);
            }
            return Response::json(['ok' => false, 'error' => 'Gönderim başarısız: ' . ($r['error'] ?? 'unknown')]);
        } catch (\Throwable $e) {
            return Response::json(['ok' => false, 'error' => 'Hata: ' . $e->getMessage()]);
        }
    }
}
