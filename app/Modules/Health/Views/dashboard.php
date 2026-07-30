<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>Health Center</h1>
            <p>Sistem sağlığı, veritabanı, disk, PHP uzantıları ve güvenlik durumu.</p>
        </div>
        <a href="/health" target="_blank" class="aho-btn aho-btn--outline aho-btn--sm">Public /health JSON</a>
    </div>

    <div class="aho-feature-grid" style="gap:var(--aho-space-4)">
        <?php foreach ($checks as $section): ?>
            <div class="aho-card">
                <div class="aho-card__header">
                    <div>
                        <h3 class="aho-card__title" style="margin:0"><?= e($section['label']) ?></h3>
                    </div>
                    <?php
                    $status = $section['status'] ?? 'ok';
                    $cls = match($status) { 'ok' => 'active', 'warning' => 'pending', default => 'suspended' };
                    $lbl = match($status) { 'ok' => '✓ Sağlıklı', 'warning' => '⚠ Uyarı', default => '✗ Sorun' };
                    ?>
                    <span class="aho-admin-badge aho-admin-badge--<?= $cls ?>"><?= $lbl ?></span>
                </div>
                <table class="aho-domain-table">
                    <?php foreach ($section['items'] as $item): ?>
                        <tr>
                            <th><?= e($item['label']) ?></th>
                            <td>
                                <?= e((string) $item['value']) ?>
                                <?php if (isset($item['ok'])): ?>
                                    <span style="margin-left:8px"><?= $item['ok'] ? '✅' : '❌' ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Faz 6f: Uptime Probe -->
    <?php if (!empty($probes)): ?>
    <div class="aho-card" style="margin-top:20px;padding:0;overflow:auto">
        <div style="padding:16px 24px;border-bottom:1px solid var(--aho-color-border)">
            <h3 style="margin:0">📡 Uptime Probe</h3>
            <div style="font-size:12px;color:var(--aho-color-ink-500);margin-top:4px">Public URL'lerin dış görünümü (HTTP + SSL)</div>
        </div>
        <table style="width:100%;border-collapse:collapse;font-size:13px">
            <thead style="background:var(--aho-color-ink-50);text-align:left">
                <tr>
                    <th style="padding:10px 16px">URL</th>
                    <th style="padding:10px 16px;text-align:center">HTTP</th>
                    <th style="padding:10px 16px;text-align:right">Yanıt (ms)</th>
                    <th style="padding:10px 16px;text-align:center">SSL</th>
                    <th style="padding:10px 16px;text-align:center">Durum</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($probes as $url => $p): ?>
                <tr style="border-top:1px solid var(--aho-color-border)">
                    <td style="padding:10px 16px;font-family:monospace;font-size:12px"><?= e($url) ?></td>
                    <td style="padding:10px 16px;text-align:center;font-weight:600">
                        <span style="color:<?= ($p['http_code']??0) < 400 ? '#059669' : '#dc2626' ?>"><?= (int)($p['http_code'] ?? 0) ?></span>
                    </td>
                    <td style="padding:10px 16px;text-align:right;color:<?= ($p['response_time_ms']??0) > 2000 ? '#dc2626' : (($p['response_time_ms']??0) > 800 ? '#d97706' : '#059669') ?>">
                        <?= (int)($p['response_time_ms'] ?? 0) ?> ms
                    </td>
                    <td style="padding:10px 16px;text-align:center">
                        <?php if (isset($p['ssl_valid'])): ?>
                            <?php if ($p['ssl_valid']): ?>
                                <span style="color:#059669">✓</span>
                                <span style="font-size:11px;color:var(--aho-color-ink-500)"><?= (int)$p['ssl_expires_in_days'] ?> gün</span>
                            <?php else: ?>
                                <span style="color:#dc2626">✗ Süresi geçti</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color:var(--aho-color-ink-400)">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:10px 16px;text-align:center">
                        <?php if ($p['ok']): ?>
                            <span style="padding:3px 10px;font-size:11px;border-radius:10px;color:#065f46;background:#d1fae5">✓ UP</span>
                        <?php else: ?>
                            <span style="padding:3px 10px;font-size:11px;border-radius:10px;color:#991b1b;background:#fee2e2">✗ DOWN</span>
                            <?php if (!empty($p['error'])): ?>
                                <div style="font-size:11px;color:#dc2626;margin-top:2px"><?= e($p['error']) ?></div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php $view->endSection(); ?>
