<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Core\Database\Connection;
use App\Core\Env;
use App\Services\Logger\Logger;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

/**
 * SMTP mailer + template render + kuyruğa alma.
 * PHPMailer paketi kullanır. .env'den config okur.
 */
final class Mailer
{
    /**
     * Mail'i hemen gönder veya kuyruğa al (default: queue).
     *
     * @param string $templateKey Şablon anahtarı (email_templates.template_key)
     * @param string $toEmail
     * @param array  $variables   Şablondaki {{var}} yerine geçecek değerler
     * @return array{ok:bool, queued?:bool, error?:string}
     */
    public static function send(string $templateKey, string $toEmail, array $variables = [], ?string $toName = null, bool $immediate = false): array
    {
        $tpl = self::template($templateKey);
        if (!$tpl) {
            return ['ok' => false, 'error' => "Şablon bulunamadı: {$templateKey}"];
        }

        $subject = self::render($tpl['subject'], $variables);
        $bodyHtml = self::render((string)$tpl['body_html'], $variables);
        $bodyText = self::render((string)($tpl['body_text'] ?? strip_tags((string)$tpl['body_html'])), $variables);

        if ($immediate) {
            return self::sendNow($toEmail, $toName, $subject, $bodyHtml, $bodyText);
        }
        return self::queue($toEmail, $toName, $subject, $bodyHtml, $bodyText, $templateKey);
    }

    /** Doğrudan HTML gönder (şablonsuz). */
    public static function sendRaw(string $toEmail, string $subject, string $bodyHtml, ?string $toName = null, ?string $bodyText = null, bool $immediate = false): array
    {
        if ($immediate) {
            return self::sendNow($toEmail, $toName, $subject, $bodyHtml, $bodyText);
        }
        return self::queue($toEmail, $toName, $subject, $bodyHtml, $bodyText);
    }

    public static function template(string $key): ?array
    {
        try {
            return Connection::selectOne(
                "SELECT * FROM email_templates WHERE template_key = ? AND is_active = 1", [$key]
            );
        } catch (\Throwable) { return null; }
    }

    private static function render(string $tpl, array $vars): string
    {
        foreach ($vars as $k => $v) {
            $tpl = str_replace('{{' . $k . '}}', (string)$v, $tpl);
        }
        // Kalan {{...}} placeholder'ları boş bırak
        return preg_replace('/\{\{[a-zA-Z0-9_]+\}\}/', '', $tpl) ?? $tpl;
    }

    public static function queue(string $toEmail, ?string $toName, string $subject, string $bodyHtml, ?string $bodyText = null, ?string $templateKey = null): array
    {
        try {
            $id = Connection::insert('mail_queue', [
                'to_email'     => $toEmail,
                'to_name'      => $toName,
                'subject'      => $subject,
                'body_html'    => $bodyHtml,
                'body_text'    => $bodyText ?? strip_tags($bodyHtml),
                'template_key' => $templateKey,
                'status'       => 'pending',
                'attempts'     => 0,
                'scheduled_at' => date('Y-m-d H:i:s'),
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);
            return ['ok' => true, 'queued' => true, 'id' => $id];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Kuyruktaki pending mailleri işle (cron çağırır).
     * @return array{sent:int, failed:int}
     */
    public static function processQueue(int $batch = 20): array
    {
        // Kurtarma: bir önceki koşumda 'sending' durumunda takılı kalmış kayıtlar
        // (ör. PHP süreci SMTP zaman aşımı sırasında öldürüldüyse) 5 dakikadan
        // eskiyse tekrar 'pending'e çekilir, aksi halde asla yeniden denenmezler.
        try {
            Connection::query(
                "UPDATE mail_queue SET status = 'pending'
                 WHERE status = 'sending' AND updated_at < (NOW() - INTERVAL 5 MINUTE)"
            );
        } catch (\Throwable) {
            // Yoksay — kurtarma adımı en kötü ihtimalle bir sonraki koşuma kalır.
        }

        try {
            $rows = Connection::select(
                "SELECT * FROM mail_queue
                 WHERE status = 'pending' AND (scheduled_at IS NULL OR scheduled_at <= NOW())
                 ORDER BY id ASC LIMIT " . (int)$batch
            );
        } catch (\Throwable) {
            return ['sent' => 0, 'failed' => 0];
        }

        $sent = 0; $failed = 0;
        foreach ($rows as $r) {
            $newAttempts = (int)$r['attempts'] + 1;
            Connection::update('mail_queue', ['status' => 'sending', 'attempts' => $newAttempts], 'id = ?', [$r['id']]);
            $res = self::sendNow($r['to_email'], $r['to_name'], $r['subject'], $r['body_html'], $r['body_text']);
            if ($res['ok']) {
                Connection::update('mail_queue', [
                    'status'  => 'sent',
                    'sent_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$r['id']]);
                $sent++;
            } else {
                Connection::update('mail_queue', [
                    // Güncellenmiş (yeni) deneme sayısına göre karar ver — eski değere
                    // göre kontrol etmek "failed" durumuna geçişi bir deneme geciktirirdi.
                    'status' => $newAttempts >= 3 ? 'failed' : 'pending',
                    'error'  => $res['error'] ?? '',
                ], 'id = ?', [$r['id']]);
                $failed++;
            }
        }
        return ['sent' => $sent, 'failed' => $failed];
    }

    /** SMTP üzerinden anında gönder. */
    public static function sendNow(string $toEmail, ?string $toName, string $subject, string $bodyHtml, ?string $bodyText = null): array
    {
        if (!class_exists(PHPMailer::class)) {
            return ['ok' => false, 'error' => 'PHPMailer yüklü değil (composer install çalıştırın).'];
        }

        $mail = new PHPMailer(true);
        try {
            // Admin panelden değiştirilebilir (settings tablosu) — .env fallback
            $sm = \App\Services\Settings\SettingsManager::class;
            $host     = (string) $sm::get('mail.host',           '', 'MAIL_HOST');
            $port     = (int)    $sm::get('mail.port',          587, 'MAIL_PORT');
            $user     = (string) $sm::get('mail.username',       '', 'MAIL_USERNAME');
            $pass     = (string) $sm::get('mail.password',       '', 'MAIL_PASSWORD');
            $enc      = strtolower((string) $sm::get('mail.encryption', 'tls', 'MAIL_ENCRYPTION'));
            $from     = (string) $sm::get('mail.from_address',   'no-reply@ahost.web.tr', 'MAIL_FROM_ADDRESS');
            $fromName = (string) $sm::get('mail.from_name',      'Ahost Bilişim',         'MAIL_FROM_NAME');

            if ($host === '') {
                Logger::warning('Mail SMTP host tanımlı değil — mail atlanıyor.');
                return ['ok' => false, 'error' => 'SMTP host tanımlı değil'];
            }

            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = $port;
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            // SMTP sunucusu yanıt vermezse (down, firewall, yanlış ayar) cron/istek
            // sürecinin PHPMailer'ın 300sn'lik varsayılanı kadar kilitlenmesini önler.
            $mail->Timeout = 10;
            $mail->SMTPKeepAlive = false;

            if ($user !== '') {
                $mail->SMTPAuth = true;
                $mail->Username = $user;
                $mail->Password = $pass;
            }
            if ($enc === 'ssl') $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            elseif ($enc === 'tls') $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

            $mail->setFrom($from, $fromName);
            $mail->addAddress($toEmail, $toName ?: '');
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body    = $bodyHtml;
            $mail->AltBody = $bodyText ?: strip_tags($bodyHtml);

            $mail->send();
            Logger::info('Mail sent', ['to' => $toEmail, 'subject' => $subject]);
            return ['ok' => true];
        } catch (\Throwable $e) {
            Logger::error('Mail send failed', ['to' => $toEmail, 'error' => $e->getMessage()]);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
