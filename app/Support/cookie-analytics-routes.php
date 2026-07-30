<?php
// Cookie consent, policy and first-party analytics.
if (!function_exists('ao_cookie_analytics_ensure_schema')) {
    function ao_cookie_analytics_ensure_schema(): void {
        try {
            db()->exec("CREATE TABLE IF NOT EXISTS cookie_analytics_events (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                visitor_id VARCHAR(80) NOT NULL,
                customer_id INT NULL,
                event_type VARCHAR(60) NOT NULL,
                route VARCHAR(255) NULL,
                path VARCHAR(255) NULL,
                title VARCHAR(255) NULL,
                target VARCHAR(255) NULL,
                label VARCHAR(255) NULL,
                referrer VARCHAR(255) NULL,
                ip_address VARCHAR(80) NULL,
                user_agent VARCHAR(255) NULL,
                meta_json MEDIUMTEXT NULL,
                created_at DATETIME NOT NULL,
                KEY event_type_idx(event_type),
                KEY visitor_idx(visitor_id),
                KEY route_idx(route),
                KEY created_idx(created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
            error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
        }
    }
}

if (!function_exists('ao_cookie_analytics_text')) {
    function ao_cookie_analytics_text($value, int $limit = 255): string {
        $value = trim(preg_replace('/\s+/', ' ', (string)$value));
        return mb_substr($value, 0, $limit, 'UTF-8');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'cookie/track') {
    ao_cookie_analytics_ensure_schema();
    $raw = (string)file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) $data = $_POST;
    $visitor = ao_cookie_analytics_text($data['visitor_id'] ?? ($_COOKIE['ao_visitor_id'] ?? ''), 80);
    if ($visitor === '') $visitor = 'anon-'.bin2hex(random_bytes(8));
    $event = ao_cookie_analytics_text($data['event_type'] ?? 'event', 60);
    $meta = $data['meta'] ?? [];
    if (!is_array($meta)) $meta = ['value'=>(string)$meta];
    try {
        $customer = function_exists('current_customer') ? current_customer() : null;
        db()->prepare('INSERT INTO cookie_analytics_events(visitor_id,customer_id,event_type,route,path,title,target,label,referrer,ip_address,user_agent,meta_json,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,NOW())')
            ->execute([
                $visitor,
                $customer ? (int)($customer['id'] ?? 0) : null,
                $event,
                ao_cookie_analytics_text($data['route'] ?? ''),
                ao_cookie_analytics_text($data['path'] ?? ''),
                ao_cookie_analytics_text($data['title'] ?? ''),
                ao_cookie_analytics_text($data['target'] ?? ''),
                ao_cookie_analytics_text($data['label'] ?? ''),
                ao_cookie_analytics_text($data['referrer'] ?? ''),
                ao_cookie_analytics_text($_SERVER['REMOTE_ADDR'] ?? '', 80),
                ao_cookie_analytics_text($_SERVER['HTTP_USER_AGENT'] ?? ''),
                json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
    } catch (Throwable $e) {
        error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);
    exit;
}

if (in_array($route, ['cerez-politikasi', 'cookie-policy'], true)) {
    site_view('legal/cookie-policy', [
        'pageTitle'=>'Çerez Politikası',
        'metaTitle'=>'Çerez Politikası',
        'metaDescription'=>'Ahost One çerez kullanımı, zorunlu çerezler, analitik çerezler ve tercih yönetimi hakkında bilgilendirme.',
    ]);
    exit;
}

if ($route === 'admin/cookie-analytics') {
    redirect_to('admin/analytics/cookies');
}

if ($route === 'admin/analytics/cookies') {
    require_admin();
    ao_cookie_analytics_ensure_schema();
    $days = max(1, min(365, (int)($_GET['days'] ?? 30)));
    $sinceSql = 'created_at >= DATE_SUB(NOW(), INTERVAL '.$days.' DAY)';
    $summary = ['events'=>0,'visitors'=>0,'views'=>0,'clicks'=>0];
    $topRoutes = $topClicks = $recent = [];
    try {
        $summary['events'] = (int)db()->query("SELECT COUNT(*) FROM cookie_analytics_events WHERE {$sinceSql}")->fetchColumn();
        $summary['visitors'] = (int)db()->query("SELECT COUNT(DISTINCT visitor_id) FROM cookie_analytics_events WHERE {$sinceSql}")->fetchColumn();
        $summary['views'] = (int)db()->query("SELECT COUNT(*) FROM cookie_analytics_events WHERE {$sinceSql} AND event_type='page_view'")->fetchColumn();
        $summary['clicks'] = (int)db()->query("SELECT COUNT(*) FROM cookie_analytics_events WHERE {$sinceSql} AND event_type='click'")->fetchColumn();
        $topRoutes = db()->query("SELECT COALESCE(NULLIF(route,''), path) item, COUNT(*) total FROM cookie_analytics_events WHERE {$sinceSql} AND event_type='page_view' GROUP BY item ORDER BY total DESC LIMIT 12")->fetchAll();
        $topClicks = db()->query("SELECT COALESCE(NULLIF(label,''), target) item, COUNT(*) total FROM cookie_analytics_events WHERE {$sinceSql} AND event_type='click' GROUP BY item ORDER BY total DESC LIMIT 12")->fetchAll();
        $recent = db()->query("SELECT event_type,route,label,created_at FROM cookie_analytics_events WHERE {$sinceSql} ORDER BY id DESC LIMIT 20")->fetchAll();
    } catch (Throwable $e) {
        flash('error', 'Çerez analiz raporu okunamadı: '.$e->getMessage());
    }

    $cards = '<div class="ao-grid four">'
        .ao_runtime_card('Toplam Olay', '<strong>'.number_format($summary['events'],0,',','.').'</strong>')
        .ao_runtime_card('Ziyaretçi', '<strong>'.number_format($summary['visitors'],0,',','.').'</strong>')
        .ao_runtime_card('Sayfa Görüntüleme', '<strong>'.number_format($summary['views'],0,',','.').'</strong>')
        .ao_runtime_card('Tıklama', '<strong>'.number_format($summary['clicks'],0,',','.').'</strong>')
        .'</div>';
    $routeRows = '';
    foreach ($topRoutes as $row) $routeRows .= '<tr><td>'.e($row['item'] ?: '-').'</td><td>'.(int)$row['total'].'</td></tr>';
    $clickRows = '';
    foreach ($topClicks as $row) $clickRows .= '<tr><td>'.e($row['item'] ?: '-').'</td><td>'.(int)$row['total'].'</td></tr>';
    $recentRows = '';
    foreach ($recent as $row) $recentRows .= '<tr><td>'.e($row['event_type']).'</td><td>'.e($row['route'] ?: '-').'</td><td>'.e($row['label'] ?: '-').'</td><td>'.e($row['created_at']).'</td></tr>';
    ao_runtime_shell('Çerez Analizi',
        '<div class="ao-page-head"><div><h2>Çerez Analizi</h2><p>Onay veren ziyaretçi ve müşterilerin anonim sayfa görüntüleme/tıklama eğilimleri.</p></div><form method="get"><input type="hidden" name="days" value="'.$days.'"><select name="days" onchange="this.form.submit()"><option value="7"'.($days===7?' selected':'').'>Son 7 gün</option><option value="30"'.($days===30?' selected':'').'>Son 30 gün</option><option value="90"'.($days===90?' selected':'').'>Son 90 gün</option></select></form></div>'
        .$cards
        .'<div class="ao-grid two"><div class="ao-card"><h3>En Çok Bakılan Sayfalar</h3><table class="ao-table"><tr><th>Sayfa</th><th>Görüntüleme</th></tr>'.($routeRows ?: '<tr><td colspan="2">Veri yok.</td></tr>').'</table></div><div class="ao-card"><h3>En Çok Tıklanan Aksiyonlar</h3><table class="ao-table"><tr><th>Aksiyon</th><th>Tıklama</th></tr>'.($clickRows ?: '<tr><td colspan="2">Veri yok.</td></tr>').'</table></div></div>'
        .'<div class="ao-card"><h3>Son Olaylar</h3><table class="ao-table"><tr><th>Tip</th><th>Sayfa</th><th>Etiket</th><th>Tarih</th></tr>'.($recentRows ?: '<tr><td colspan="4">Veri yok.</td></tr>').'</table></div>'
    );
}
