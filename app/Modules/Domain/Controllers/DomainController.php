<?php

declare(strict_types=1);

namespace App\Modules\Domain\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View;
use App\Modules\Domain\Services\SslService;
use App\Modules\Domain\Services\ValuationService;
use App\Modules\Registrar\RegistrarManager;

final class DomainController
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $result = null;

        if ($q !== '') {
            $q = strtolower(preg_replace('#^https?://#', '', $q) ?? $q);
            $q = preg_replace('#^www\.#', '', $q) ?? $q;
            $q = preg_replace('#/.*$#', '', $q) ?? $q;

            $registrar = RegistrarManager::default();
            $main = $registrar->check([$q]);
            $mainInfo = $main[$q] ?? ['available' => false];

            // TLD önerileri
            $sld = explode('.', $q)[0];
            $suggestions = [];
            foreach (['com', 'net', 'org', 'com.tr', 'io', 'dev', 'app', 'tech'] as $tld) {
                $candidate = $sld . '.' . $tld;
                if ($candidate === $q) continue;
                $check = $registrar->check([$candidate]);
                $suggestions[$candidate] = $check[$candidate] ?? ['available' => false];
            }

            // Ana domain kayıtlıysa WHOIS + DNS + SSL + Değerleme
            $whois = null; $dns = null; $ssl = null; $valuation = null;
            if (empty($mainInfo['available'])) {
                $whois = $registrar->whois($q);
                $dns   = $registrar->dnsRecords($q);
                $ssl   = SslService::check($q);
                $valuation = ValuationService::evaluate($q, $whois, $dns, $ssl);
            }

            $result = [
                'query'       => $q,
                'main'        => $mainInfo,
                'suggestions' => $suggestions,
                'whois'       => $whois,
                'dns'         => $dns,
                'ssl'         => $ssl,
                'valuation'   => $valuation,
            ];
        }

        $view = new View();
        return Response::html($view->render('domain::search', [
            'title'  => 'Domain Sorgulama',
            'result' => $result,
            'q'      => $q,
        ]));
    }
}
