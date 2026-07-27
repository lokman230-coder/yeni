<?php

declare(strict_types=1);

namespace App\Modules\Ai\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\Ai\Services\ContentGenerator;

/**
 * Admin AJAX API — AI ile içerik üretimi.
 * Ürün formu, blog editörü, sayfa SEO alanlarından çağrılır.
 */
final class ContentApiController
{
    public function product(Request $request): Response
    {
        $name = trim((string) $request->input('name', ''));
        $type = (string) $request->input('type', 'hosting');
        if ($name === '') return Response::json(['ok' => false, 'error' => 'Ürün adı gerekli']);
        return Response::json(ContentGenerator::productDescription(['name' => $name, 'type' => $type]));
    }

    public function blog(Request $request): Response
    {
        $topic = trim((string) $request->input('topic', ''));
        if ($topic === '') return Response::json(['ok' => false, 'error' => 'Konu gerekli']);
        $angle = trim((string) $request->input('angle', ''));
        return Response::json(ContentGenerator::blogPost($topic, $angle ?: null));
    }

    public function seo(Request $request): Response
    {
        $title = trim((string) $request->input('title', ''));
        $content = trim((string) $request->input('content', ''));
        if ($title === '') return Response::json(['ok' => false, 'error' => 'Sayfa başlığı gerekli']);
        return Response::json(ContentGenerator::seoMeta($title, $content));
    }

    public function ticketReply(Request $request): Response
    {
        $ticketId = (int) $request->input('ticket_id', 0);
        if ($ticketId === 0) return Response::json(['ok' => false, 'error' => 'Ticket ID gerekli']);
        return Response::json(\App\Modules\Ai\Services\TicketAssistant::suggestReply($ticketId));
    }
}
