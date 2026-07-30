<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\Referral\Services\ReferralService;
use Closure;

/**
 * Public URL'lere gelen ?ref=CODE parametresini yakalar, cookie kurar.
 * Herhangi bir sayfada mevcut olabilir → LocaleMiddleware benzeri global bir katman.
 */
final class ReferralCaptureMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $ref = (string) $request->query('ref', '');
        if ($ref !== '' && preg_match('/^[A-Z0-9]{4,32}$/', $ref)) {
            try {
                ReferralService::captureVisit($ref, [
                    'ip'          => $request->ip(),
                    'user_agent'  => $request->userAgent(),
                    'landing_url' => (string) ($_SERVER['REQUEST_URI'] ?? ''),
                    'referer_url' => (string) ($_SERVER['HTTP_REFERER'] ?? ''),
                ]);
            } catch (\Throwable) {
                // Sessiz geç — referral middleware kritik path'i bozmasın
            }
        }
        return $next($request);
    }
}
