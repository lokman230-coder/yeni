<?php
/**
 * Ahost One - Ücretsiz SEO Analizi (Demo)
 * Temel SEO kontrolü - Detaylı analiz için kayıt olun
 */
$seo_analysis = null;
$error = null;
if ($_POST['action'] === 'analyze' && !empty($_POST['url'])) {
    $url = filter_var($_POST['url'], FILTER_SANITIZE_URL);
    if (!preg_match('/^https?:\/\//i', $url)) $url = 'https://' . $url;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    $html = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!$html || $httpcode !== 200) {
        $error = "Siteye erişilemedi";
    } else {
        $score = 100;
        $issues = [];
        $details = [];
        // Sadece 5 temel kontrol
        preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $title);
        $titleText = !empty($title[1]) ? trim($title[1]) : '';
        $details['title'] = ['text' => $titleText, 'ok' => !empty($titleText) && strlen($titleText) >= 10];
        if (empty($titleText) || strlen($titleText) < 10) { $score -= 25; $issues[] = 'Başlık eksik veya çok kısa'; }
        preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $desc);
        $descText = !empty($desc[1]) ? trim($desc[1]) : '';
        $details['description'] = ['text' => $descText, 'ok' => !empty($descText)];
        if (empty($descText)) { $score -= 25; $issues[] = 'Meta description yok'; }
        if (!preg_match('/<meta[^>]*name=["\']viewport["\']/i', $html)) { $score -= 25; $issues[] = 'Mobil uyumlu değil'; }
        else { $details['viewport'] = true; }
        preg_match_all('/<img[^>]*>/i', $html, $imgs);
        $noAlt = 0;
        foreach ($imgs[0] as $img) { if (!preg_match('/alt=["\'][^"\']+["\']/', $img)) $noAlt++; }
        if ($noAlt > count($imgs[0]) * 0.5) { $score -= 15; $issues[] = 'Çoğu görselde alt etiketi yok'; }
        $text = strip_tags($html);
        if (str_word_count($text) < 200) { $score -= 10; $issues[] = 'İçerik yetersiz'; }
        $seo_analysis = ['url' => $url, 'score' => max(0, $score), 'issues' => $issues, 'httpcode' => $httpcode, 'details' => $details];
    }
}
?>

<section class="ao-site-content ao-seo-analyzer-page">
  <div class="ao-content-shell">
<div class="ao-tool-container">
    <div class="header">
        <h1>🔍 Ücretsiz SEO Analizi</h1>
        <p>Web sitenizin SEO performansını öğrenin</p>
    </div>
    
    <div class="card">
        <h2>📊 Hızlı Analiz</h2>
        <form method="POST">
            <input type="hidden" name="action" value="analyze">
            <div class="input-group">
                <input type="url" name="url" placeholder="https://siteniz.com" required value="<?= htmlspecialchars($_POST['url'] ?? '') ?>">
                <button type="submit">🔍 Analiz Et</button>
            </div>
        </form>
    </div>
    
    <?php if ($error): ?>
    <div class="card" style="border-left:4px solid #ef4444">
        <p style="color:#dc2626">❌ <?= htmlspecialchars($error) ?></p>
    </div>
    <?php elseif ($seo_analysis): ?>
    <div class="score-box">
        <div class="score"><?= $seo_analysis['score'] ?><span>/100</span></div>
        <p><?= $seo_analysis['score'] >= 70 ? 'İyi performans!' : ($seo_analysis['score'] >= 40 ? 'Orta seviye' : 'İyileştirme gerekli') ?></p>
    </div>
    
    <div class="card">
        <h2>📋 Temel Kontroller</h2>
        <ul class="check-list">
            <li>
                <?php if (!empty($seo_analysis['details']['title']['ok'])): ?>
                <span class="check-ok">✅</span>
                <?php else: ?>
                <span class="check-no">❌</span>
                <?php endif; ?>
                Başlık (Title)
            </li>
            <li>
                <?php if (!empty($seo_analysis['details']['description']['ok'])): ?>
                <span class="check-ok">✅</span>
                <?php else: ?>
                <span class="check-no">❌</span>
                <?php endif; ?>
                Meta Açıklama
            </li>
            <li>
                <?php if (!empty($seo_analysis['details']['viewport'])): ?>
                <span class="check-ok">✅</span>
                <?php else: ?>
                <span class="check-no">❌</span>
                <?php endif; ?>
                Mobil Uyumluluk
            </li>
        </ul>
        
        <?php if (!empty($seo_analysis['issues'])): ?>
        <h3 style="margin-top:25px">⚠️ Sorunlar</h3>
        <ul class="issues">
            <?php foreach ($seo_analysis['issues'] as $issue): ?>
            <li><?= htmlspecialchars($issue) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
    
    <div class="locked">
        <p>🔒 <strong>Detaylı analiz için kayıt olun:</strong></p>
        <p style="font-size:.85rem;color:#64748b;margin-bottom:15px">• 20+ SEO öğesi kontrolü<br>• Analiz tarihçesi<br>• Rakip karşılaştırma<br>• Detaylı raporlar</p>
        <a href="/kayit">Ücretsiz Kayıt Ol</a>
    </div>
    <?php endif; ?>
    
    <div class="features">
        <h3>💡 Daha İyi Sonuçlar İçin</h3>
        <div class="features-grid">
            <div class="feature"><div class="feature-icon">📝</div><h4>Başlık</h4><p>50-60 karakter arası</p></div>
            <div class="feature"><div class="feature-icon">📋</div><h4>Açıklama</h4><p>150-160 karakter</p></div>
            <div class="feature"><div class="feature-icon">📱</div><h4>Mobil</h4><p>Responsive tasarım</p></div>
            <div class="feature"><div class="feature-icon">🖼️</div><h4>Görseller</h4><p>Alt etiketleri</p></div>
        </div>
    </div>
    
    <div class="cta-box" style="margin-top:25px">
        <h3>🚀 Profesyonel SEO Hizmeti</h3>
        <p>Ahost One ile sitenizi üst sıralara taşıyın</p>
        <a href="/" class="cta-btn">Detaylı Bilgi →</a>
    </div>
</div>
  </div>
</section>
