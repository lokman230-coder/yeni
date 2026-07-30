<?php

declare(strict_types=1);

namespace App\Modules\Ticket\Services;

use App\Core\Database\Connection;
use App\Services\Logger\Logger;

/**
 * Ticket dosya eki yönetimi.
 * Dosyalar: storage/uploads/tickets/{ticket_id}/{stored_name}
 *
 * Güvenlik:
 *   - Max 10 MB
 *   - Sadece izin verilen MIME tipler (image, pdf, txt, log, zip)
 *   - Dosya adı rastgele üretilir (path traversal engel)
 *   - .htaccess ile PHP execution engellenir
 */
final class AttachmentService
{
    private const MAX_SIZE = 10 * 1024 * 1024; // 10 MB
    private const ALLOWED_MIMES = [
        'image/png', 'image/jpeg', 'image/gif', 'image/webp',
        'application/pdf', 'text/plain', 'application/zip',
        'application/x-gzip', 'application/gzip',
        'application/octet-stream', // fallback
    ];
    private const ALLOWED_EXT = ['png','jpg','jpeg','gif','webp','pdf','txt','log','zip','gz'];

    public static function baseDir(): string
    {
        return AHO_ROOT . '/storage/uploads/tickets';
    }

    /**
     * $_FILES['attachment'] veya benzeri array kabul eder.
     * @return array{ok:bool, id?:int, error?:string}
     */
    public static function save(int $ticketId, ?int $replyId, string $uploaderType, int $uploaderId, array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'error' => 'Dosya seçilmedi'];
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Upload hatası kodu: ' . $file['error']];
        }
        if ($file['size'] > self::MAX_SIZE) {
            return ['ok' => false, 'error' => 'Dosya 10 MB\'tan büyük olamaz'];
        }

        $orig = (string) ($file['name'] ?? 'file');
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            return ['ok' => false, 'error' => 'Bu dosya tipine izin verilmiyor. İzin verilen: ' . implode(', ', self::ALLOWED_EXT)];
        }

        $mime = mime_content_type($file['tmp_name']) ?: 'application/octet-stream';
        if (!in_array($mime, self::ALLOWED_MIMES, true) && !str_starts_with($mime, 'image/')) {
            return ['ok' => false, 'error' => 'MIME tipine izin verilmiyor: ' . $mime];
        }

        $dir = self::baseDir() . '/' . $ticketId;
        if (!is_dir($dir)) {
            @mkdir($dir, 0770, true);
            @file_put_contents(self::baseDir() . '/.htaccess', "Options -Indexes\n<FilesMatch \"\\.(php|phtml|php3|php4|php5|phar)$\">\n  Require all denied\n</FilesMatch>\n");
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = $dir . '/' . $storedName;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['ok' => false, 'error' => 'Dosya taşınamadı'];
        }
        @chmod($dest, 0640);

        try {
            $id = Connection::insert('ticket_attachments', [
                'ticket_id'     => $ticketId,
                'reply_id'      => $replyId,
                'uploader_type' => $uploaderType,
                'uploader_id'   => $uploaderId,
                'original_name' => mb_substr($orig, 0, 255),
                'stored_name'   => $storedName,
                'mime'          => $mime,
                'size_bytes'    => (int) $file['size'],
            ]);
            return ['ok' => true, 'id' => $id];
        } catch (\Throwable $e) {
            @unlink($dest);
            Logger::warning('Ticket attachment DB insert failed: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Kayıt hatası'];
        }
    }

    public static function forTicket(int $ticketId): array
    {
        try {
            return Connection::select("SELECT * FROM ticket_attachments WHERE ticket_id = ? ORDER BY id ASC", [$ticketId]);
        } catch (\Throwable) { return []; }
    }

    /**
     * Path ve MIME döndür — controller download için kullanır.
     * @return array{path:string, mime:string, name:string}|null
     */
    public static function fetch(int $attachmentId, int $ticketId): ?array
    {
        $row = Connection::selectOne(
            "SELECT * FROM ticket_attachments WHERE id = ? AND ticket_id = ?",
            [$attachmentId, $ticketId]
        );
        if (!$row) return null;
        $path = self::baseDir() . '/' . $ticketId . '/' . $row['stored_name'];
        if (!is_file($path)) return null;
        return [
            'path' => $path,
            'mime' => (string) $row['mime'],
            'name' => (string) $row['original_name'],
        ];
    }
}
