<?php
/**
 * Site Araçları — sonuç renderer.
 * $render + $data extract edilmiş halde gelir.
 * TÜM stiller themes/default/css/site/tools.css içinde — inline yok.
 */
$_e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$card = function (string $title, string $bodyHtml, bool $full = false) {
    $cls = 'aho-tool-card' . ($full ? ' aho-tool-result-grid--full' : '');
    echo '<div class="' . $cls . '">';
    echo '<div class="aho-tool-card__title">' . htmlspecialchars($title) . '</div>';
    echo $bodyHtml;
    echo '</div>';
};

$badge = function (bool $ok, string $ok_text = 'Var', string $no_text = 'Yok') {
    $cls = $ok ? 'aho-tool-status--ok' : 'aho-tool-status--warn';
    $txt = $ok ? '✓ ' . $ok_text : '✗ ' . $no_text;
    return '<span class="aho-tool-status ' . $cls . '">' . htmlspecialchars($txt) . '</span>';
};

echo '<div class="aho-tool-result-grid">';

switch ($render) {
    case 'ssl':
        $s = $data;
        $html = '<table class="aho-tool-table">';
        $html .= '<tr><th>Durum</th><td>' . $badge(!empty($s['active']), 'Aktif', 'Geçersiz/Yok') . '</td></tr>';
        foreach ([
            'Issuer'      => $s['issuer'] ?? null,
            'Common Name' => $s['subject_cn'] ?? null,
            'Başlangıç'   => $s['valid_from'] ?? null,
            'Bitiş'       => $s['valid_to'] ?? null,
            'Kalan Gün'   => $s['days_left'] ?? null,
        ] as $k => $v) {
            $html .= '<tr><th>' . $_e($k) . '</th><td>' . ($v !== null && $v !== '' ? $_e($v) : '<em style="color:var(--aho-color-ink-400)">Veri bulunamadı</em>') . '</td></tr>';
        }
        $html .= '</table>';
        $card('🔒 SSL Sertifikası', $html, true);
        break;

    case 'dns':
        $html = '';
        foreach ($data as $type => $records) {
            if (empty($records)) continue;
            $html .= '<div class="aho-tool-dns__group">';
            $html .= '<div class="aho-tool-dns__type">' . $_e($type) . ' (' . count($records) . ' kayıt)</div>';
            foreach ($records as $r) {
                $val = $r['value'] ?? '';
                $ttl = $r['ttl'] ?? null;
                $html .= '<div class="aho-tool-dns__record">';
                if (isset($r['priority'])) $html .= '<span class="aho-tool-dns__pri">' . (int)$r['priority'] . '</span>';
                $html .= '<span class="aho-tool-dns__value">' . $_e($val) . '</span>';
                if ($ttl) $html .= '<small>TTL: ' . (int)$ttl . '</small>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        $card('🌐 DNS Kayıtları', $html, true);
        break;

    case 'speed':
        $html = '<div class="aho-tool-metric-grid">';
        $html .= '<div class="aho-tool-metric"><div class="aho-tool-metric__value aho-tool-metric__value--grade">' . $_e($data['grade']) . '</div><div class="aho-tool-metric__label">Not</div></div>';
        $html .= '<div class="aho-tool-metric"><div class="aho-tool-metric__value">' . (int)$data['total_ms'] . ' ms</div><div class="aho-tool-metric__label">Yükleme</div></div>';
        $html .= '<div class="aho-tool-metric"><div class="aho-tool-metric__value">' . (int)$data['size_kb'] . ' KB</div><div class="aho-tool-metric__label">Sayfa Boyutu</div></div>';
        $html .= '<div class="aho-tool-metric"><div class="aho-tool-metric__value">' . (int)$data['http_code'] . '</div><div class="aho-tool-metric__label">HTTP</div></div>';
        $html .= '</div>';
        $html .= '<div class="aho-tool-suggestions"><strong>Öneriler:</strong><ul>';
        foreach ($data['suggestions'] as $s) $html .= '<li>• ' . $_e($s) . '</li>';
        $html .= '</ul></div>';
        $card('⚡ Performans Raporu', $html, true);
        break;

    case 'seo':
        $html = '<table class="aho-tool-table">';
        $html .= '<tr><th>Title</th><td>' . ($data['title'] !== '' ? $_e($data['title']) . ' <small style="color:var(--aho-color-ink-400)">(' . $data['title_length'] . ' karakter)</small>' : '<em>Yok</em>') . '</td></tr>';
        $html .= '<tr><th>Meta Description</th><td>' . ($data['meta_description'] !== '' ? $_e($data['meta_description']) . ' <small style="color:var(--aho-color-ink-400)">(' . $data['desc_length'] . ')</small>' : '<em>Yok</em>') . '</td></tr>';
        $html .= '<tr><th>H1</th><td>' . (count($data['h1']) ? $_e(implode(' | ', $data['h1'])) . ' <small>(' . count($data['h1']) . ' adet)</small>' : '<em>Yok</em>') . '</td></tr>';
        $html .= '<tr><th>H2 / H3 sayısı</th><td>' . (int)$data['h2_count'] . ' / ' . (int)$data['h3_count'] . '</td></tr>';
        $html .= '<tr><th>Kelime sayısı</th><td>' . (int)$data['word_count'] . '</td></tr>';
        $html .= '<tr><th>Görseller</th><td>' . (int)$data['images_total'] . ' toplam, ' . (int)$data['images_no_alt'] . ' adet alt metni eksik</td></tr>';
        $html .= '<tr><th>Linkler</th><td>' . (int)$data['links_internal'] . ' iç, ' . (int)$data['links_external'] . ' dış</td></tr>';
        $html .= '<tr><th>Canonical</th><td>' . ($data['canonical'] ? $_e($data['canonical']) : '<em>Yok</em>') . '</td></tr>';
        $html .= '<tr><th>Robots</th><td>' . ($data['robots'] ? $_e($data['robots']) : '<em>Yok</em>') . '</td></tr>';
        $html .= '<tr><th>Open Graph / Twitter</th><td>' . (int)$data['open_graph_count'] . ' / ' . (int)$data['twitter_card_count'] . ' etiket</td></tr>';
        $html .= '<tr><th>Schema.org JSON-LD</th><td>' . (int)$data['schema_count'] . ' adet</td></tr>';
        $html .= '</table>';
        $card('📊 SEO Analiz Raporu', $html, true);

        if (!empty($data['issues'])) {
            $issueHtml = '<ul>';
            foreach ($data['issues'] as $i) $issueHtml .= '<li>⚠ ' . $_e($i) . '</li>';
            $issueHtml .= '</ul>';
            $card('İyileştirme Önerileri', '<div class="aho-tool-suggestions">' . $issueHtml . '</div>');
        }
        break;

    case 'security-headers':
        $html = '<div style="text-align:center;padding:var(--aho-space-4);margin-bottom:var(--aho-space-4)">';
        $html .= '<div class="aho-tool-metric__value aho-tool-metric__value--grade">' . $_e($data['grade']) . '</div>';
        $html .= '<div style="color:var(--aho-color-ink-500)">' . (int)$data['score'] . ' / 100 skoru</div>';
        $html .= '</div>';
        $html .= '<table class="aho-tool-table">';
        foreach ($data['checks'] as $c) {
            $html .= '<tr>';
            $html .= '<th>' . $_e($c['header']) . '<br><small style="color:var(--aho-color-ink-400);font-weight:400">' . $_e($c['description']) . '</small></th>';
            $html .= '<td>' . $badge($c['present']) . ($c['value'] ? '<br><small style="color:var(--aho-color-ink-500);word-break:break-all">' . $_e($c['value']) . '</small>' : '') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        $card('🛡️ Güvenlik Başlıkları', $html, true);
        break;

    case 'site-analyze':
        $html = '<table class="aho-tool-table">';
        foreach ([
            'URL'             => $data['url'],
            'HTTP Kodu'       => $data['http_code'],
            'Yanıt Süresi'    => $data['ttfb_ms'] . ' ms',
            'Sunucu'          => $data['server'],
            'Powered By'      => $data['powered_by'],
            'SSL Aktif'       => $data['ssl_active'] ? '✓ Evet' : '✗ Hayır',
            'SSL Kalan Gün'   => $data['ssl_days_left'] ?? '—',
            'Sayfa Boyutu'    => $data['size_kb'] . ' KB',
            'Content-Type'    => $data['content_type'],
            'Response Header' => $data['headers_count'] . ' adet',
        ] as $k => $v) {
            $html .= '<tr><th>' . $_e($k) . '</th><td>' . $_e((string) $v) . '</td></tr>';
        }
        $html .= '</table>';
        $card('🩺 Site Analiz Raporu', $html, true);
        break;

    case 'whois':
        $w = $data;
        $html = '<table class="aho-tool-table">';
        foreach ([
            'Domain'       => $w['domain'] ?? null,
            'Registrar'    => $w['registrar'] ?? null,
            'Kayıt Sahibi' => $w['registrant'] ?? null,
            'Oluşturma'    => $w['created'] ?? null,
            'Bitiş'        => $w['expires'] ?? null,
            'Güncellenme'  => $w['updated'] ?? null,
        ] as $k => $v) {
            $val = ($v !== null && $v !== '') ? $_e($v) : '<em style="color:var(--aho-color-ink-400)">Veri bulunamadı</em>';
            $html .= '<tr><th>' . $_e($k) . '</th><td>' . $val . '</td></tr>';
        }
        if (!empty($w['nameservers'])) {
            $html .= '<tr><th>Nameservers</th><td>' . $_e(implode(', ', $w['nameservers'])) . '</td></tr>';
        }
        $html .= '</table>';
        $card('🔍 WHOIS Bilgileri', $html, true);
        break;

    case 'ip-lookup':
        $html = '<table class="aho-tool-table">';
        foreach ([
            'IP'           => $data['ip'] ?? $data['query'] ?? '—',
            'Ülke'         => $data['country'] ?? '—',
            'Bölge'        => $data['regionName'] ?? '—',
            'Şehir'        => $data['city'] ?? '—',
            'Posta Kodu'   => $data['zip'] ?? '—',
            'ISP'          => $data['isp'] ?? '—',
            'Organizasyon' => $data['org'] ?? '—',
            'ASN'          => $data['as'] ?? '—',
        ] as $k => $v) {
            $html .= '<tr><th>' . $_e($k) . '</th><td>' . $_e((string)$v) . '</td></tr>';
        }
        $html .= '</table>';
        $card('📍 IP Bilgileri', $html, true);
        break;

    case 'ping':
        $html = '<div class="aho-tool-metric-grid">';
        $html .= '<div class="aho-tool-metric"><div class="aho-tool-metric__value">' . (int)($data['avg_ms'] ?? 0) . '</div><div class="aho-tool-metric__label">Ortalama (ms)</div></div>';
        $html .= '<div class="aho-tool-metric"><div class="aho-tool-metric__value">' . (int)($data['min_ms'] ?? 0) . '</div><div class="aho-tool-metric__label">Min</div></div>';
        $html .= '<div class="aho-tool-metric"><div class="aho-tool-metric__value">' . (int)($data['max_ms'] ?? 0) . '</div><div class="aho-tool-metric__label">Max</div></div>';
        $html .= '<div class="aho-tool-metric"><div class="aho-tool-metric__value aho-tool-metric__value--success">' . (int)$data['success_rate'] . '%</div><div class="aho-tool-metric__label">Başarı</div></div>';
        $html .= '</div>';
        $html .= '<table class="aho-tool-table"><tr><th>Deneme</th><th>Süre</th><th>Kod</th></tr>';
        foreach ($data['attempts'] as $i => $a) {
            $html .= '<tr><td>#' . ($i+1) . '</td><td>' . ($a['ms'] ?? '—') . ' ms</td><td>' . $_e((string)$a['code']) . '</td></tr>';
        }
        $html .= '</table>';
        $card('📡 Ping Sonuçları', $html, true);
        break;

    case 'http-header':
        $html = '<table class="aho-tool-table"><tr><th>HTTP Kodu</th><td>' . (int)$data['http_code'] . '</td></tr>';
        foreach ($data['headers'] as $k => $v) {
            $html .= '<tr><th>' . $_e($k) . '</th><td style="word-break:break-all">' . $_e($v) . '</td></tr>';
        }
        $html .= '</table>';
        $card('📄 HTTP Response Header', $html, true);
        break;

    case 'robots':
        if ($data['exists']) {
            $html = '<table class="aho-tool-table">';
            $html .= '<tr><th>URL</th><td>' . $_e($data['url']) . '</td></tr>';
            $html .= '<tr><th>User-Agents</th><td>' . $_e(implode(', ', $data['user_agents'])) . '</td></tr>';
            $html .= '<tr><th>Disallow kuralı</th><td>' . (int)$data['disallow_count'] . ' adet</td></tr>';
            if (!empty($data['sitemap_urls'])) {
                $html .= '<tr><th>Sitemap Link(ler)</th><td>' . $_e(implode('<br>', $data['sitemap_urls'])) . '</td></tr>';
            }
            $html .= '</table>';
            $html .= '<pre class="aho-tool-code">' . $_e($data['raw']) . '</pre>';
        } else {
            $html = '<div class="aho-alert aho-alert--warning">robots.txt bulunamadı (HTTP ' . (int)$data['http_code'] . ')</div>';
        }
        $card('🤖 robots.txt', $html, true);
        break;

    case 'sitemap':
        if ($data['exists']) {
            $html = '<table class="aho-tool-table">';
            $html .= '<tr><th>URL</th><td>' . $_e($data['url']) . '</td></tr>';
            $html .= '<tr><th>Toplam URL</th><td>' . (int)$data['url_count'] . '</td></tr>';
            if (($data['sub_sitemap_count'] ?? 0) > 0) $html .= '<tr><th>Alt sitemap</th><td>' . (int)$data['sub_sitemap_count'] . ' adet</td></tr>';
            $html .= '<tr><th>Dosya boyutu</th><td>' . (int)$data['size_kb'] . ' KB</td></tr>';
            $html .= '</table>';
        } else {
            $html = '<div class="aho-alert aho-alert--warning">sitemap.xml bulunamadı</div>';
        }
        $card('🗺️ Sitemap', $html, true);
        break;

    case 'meta':
        $html = '<table class="aho-tool-table">';
        $html .= '<tr><th>Viewport</th><td>' . ($data['viewport'] ? $_e($data['viewport']) : '<em>Yok</em>') . '</td></tr>';
        $html .= '<tr><th>Charset</th><td>' . ($data['charset'] ? $_e($data['charset']) : '<em>Yok</em>') . '</td></tr>';
        $html .= '<tr><th>Favicon</th><td>' . ($data['favicon'] ? $_e($data['favicon']) : '<em>Yok</em>') . '</td></tr>';
        $html .= '</table>';
        if (!empty($data['open_graph'])) {
            $html .= '<h4 style="margin-top:var(--aho-space-4);margin-bottom:var(--aho-space-2)">Open Graph</h4><table class="aho-tool-table">';
            foreach ($data['open_graph'] as $k => $v) $html .= '<tr><th>' . $_e($k) . '</th><td>' . $_e($v) . '</td></tr>';
            $html .= '</table>';
        }
        if (!empty($data['twitter_card'])) {
            $html .= '<h4 style="margin-top:var(--aho-space-4);margin-bottom:var(--aho-space-2)">Twitter Card</h4><table class="aho-tool-table">';
            foreach ($data['twitter_card'] as $k => $v) $html .= '<tr><th>' . $_e($k) . '</th><td>' . $_e($v) . '</td></tr>';
            $html .= '</table>';
        }
        $card('🏷️ Meta Tag Analizi', $html, true);
        break;

    case 'links':
        $html = '<div class="aho-tool-metric-grid">';
        $html .= '<div class="aho-tool-metric"><div class="aho-tool-metric__value">' . (int)$data['internal_count'] . '</div><div class="aho-tool-metric__label">İç Link</div></div>';
        $html .= '<div class="aho-tool-metric"><div class="aho-tool-metric__value">' . (int)$data['external_count'] . '</div><div class="aho-tool-metric__label">Dış Link</div></div>';
        $html .= '<div class="aho-tool-metric"><div class="aho-tool-metric__value">' . (int)$data['nofollow_count'] . '</div><div class="aho-tool-metric__label">Nofollow</div></div>';
        $html .= '</div>';
        if (!empty($data['internal'])) {
            $html .= '<h4 style="margin-top:var(--aho-space-4);margin-bottom:var(--aho-space-2)">İç Linkler (ilk 30)</h4><ul class="aho-tool-link-list">';
            foreach ($data['internal'] as $l) $html .= '<li>' . $_e($l) . '</li>';
            $html .= '</ul>';
        }
        if (!empty($data['external'])) {
            $html .= '<h4 style="margin-top:var(--aho-space-4);margin-bottom:var(--aho-space-2)">Dış Linkler (ilk 30)</h4><ul class="aho-tool-link-list">';
            foreach ($data['external'] as $l) $html .= '<li>' . $_e($l) . '</li>';
            $html .= '</ul>';
        }
        $card('🔗 Link Analizi', $html, true);
        break;

    case 'images':
        $html = '<div class="aho-tool-metric-grid">';
        $html .= '<div class="aho-tool-metric"><div class="aho-tool-metric__value">' . (int)$data['total'] . '</div><div class="aho-tool-metric__label">Toplam</div></div>';
        $html .= '<div class="aho-tool-metric"><div class="aho-tool-metric__value aho-tool-metric__value--warning">' . (int)$data['no_alt'] . '</div><div class="aho-tool-metric__label">Alt Yok</div></div>';
        $html .= '<div class="aho-tool-metric"><div class="aho-tool-metric__value aho-tool-metric__value--success">' . (int)$data['coverage_percent'] . '%</div><div class="aho-tool-metric__label">Kapsama</div></div>';
        $html .= '</div>';
        if (!empty($data['images'])) {
            $html .= '<div class="aho-tool-image-list"><table class="aho-tool-table"><tr><th>SRC</th><th>ALT</th></tr>';
            foreach ($data['images'] as $img) {
                $altShow = $img['has_alt'] ? $_e($img['alt']) : '<em style="color:var(--aho-color-warning)">Yok</em>';
                $html .= '<tr><td>' . $_e($img['src']) . '</td><td>' . $altShow . '</td></tr>';
            }
            $html .= '</table></div>';
        }
        $card('🖼️ Görsel Alt Analizi', $html, true);
        break;

    case 'valuation':
        $v = $data;
        $html = '<div style="text-align:center;padding:var(--aho-space-6) 0">';
        $html .= '<div class="aho-tool-valuation__amount">$' . number_format($v['estimated_value_usd'], 0, ',', '.') . '</div>';
        $html .= '<div class="aho-tool-valuation__label">Tahmini piyasa değeri</div>';
        $html .= '<div class="aho-tool-valuation__potential">Ticari Potansiyel: <strong>' . $_e($v['commercial_potential']) . '</strong></div>';
        $html .= '</div>';
        $html .= '<div class="aho-tool-scores">';
        foreach (['TLD'=>$v['scores']['tld'],'Uzunluk'=>$v['scores']['length'],'Marka'=>$v['scores']['brand'],'Yaş'=>$v['scores']['age'],'SEO'=>$v['scores']['seo']] as $lbl=>$sc) {
            $html .= '<div class="aho-tool-score"><div class="aho-tool-score__label">' . $lbl . '</div><div class="aho-tool-score__bar"><div class="aho-tool-score__fill" style="width:' . (int)$sc . '%"></div></div><div class="aho-tool-score__value">' . (int)$sc . '</div></div>';
        }
        $html .= '</div>';
        $html .= '<div class="aho-tool-score aho-tool-score--overall"><div class="aho-tool-score__label">Genel Skor</div><div class="aho-tool-score__bar"><div class="aho-tool-score__fill" style="width:' . (int)$v['scores']['overall'] . '%"></div></div><div class="aho-tool-score__value">' . (int)$v['scores']['overall'] . '/100</div></div>';
        if (!empty($v['risks'])) {
            $html .= '<div class="aho-tool-suggestions"><strong>Risk Notları:</strong><ul>';
            foreach ($v['risks'] as $r) $html .= '<li>• ' . $_e($r) . '</li>';
            $html .= '</ul></div>';
        }
        $card('💎 Domain Değerleme', $html, true);
        break;

    default:
        echo '<pre class="aho-tool-code">' . htmlspecialchars(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . '</pre>';
}

echo '</div>';
