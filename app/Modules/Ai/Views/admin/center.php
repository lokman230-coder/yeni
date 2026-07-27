<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
$max = 1;
foreach ($byDay as $d) $max = max($max, (int)$d['c']);
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>🤖 AI Center</h1>
            <p>Yapay zeka kullanım istatistikleri ve son çağrılar.</p>
        </div>
        <a href="/admin/ayarlar?group=ai" class="aho-btn aho-btn--primary">⚙️ AI Ayarları</a>
    </div>

    <!-- Sağlayıcı durumu -->
    <div class="aho-card" style="padding:20px;margin-bottom:20px;<?= $provider === 'openai' && $hasKey ? 'background:linear-gradient(135deg,#f0fdf4 0%,#d1fae5 100%);border-left:4px solid #059669' : ($provider === 'heuristic' ? 'background:#f0f9ff;border-left:4px solid #0ea5e9' : 'background:#fef3c7;border-left:4px solid #d97706') ?>">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
            <div>
                <div style="font-size:11px;color:var(--aho-color-ink-500);text-transform:uppercase">Aktif Sağlayıcı</div>
                <div style="font-size:22px;font-weight:700;margin-top:4px">
                    <?= match($provider) {
                        'openai'    => '🧠 OpenAI (' . e($model) . ')',
                        'heuristic' => '⚡ Heuristic (Ücretsiz, kural-tabanlı)',
                        default     => '❌ Devre dışı',
                    } ?>
                </div>
                <?php if ($provider === 'openai' && !$hasKey): ?>
                    <div style="color:#d97706;font-size:13px;margin-top:6px">⚠️ OpenAI seçili ama API key yok — Heuristic fallback devrede</div>
                <?php endif; ?>
            </div>
            <?php if ($provider === 'openai' && $hasKey): ?>
                <div style="text-align:right">
                    <div style="font-size:11px;color:var(--aho-color-ink-500)">BU AY TOKEN</div>
                    <div style="font-size:24px;font-weight:700;color:#059669"><?= number_format($metrics['tokens_month']) ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Metrik grid -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:20px">
        <div class="aho-card" style="padding:16px">
            <div style="font-size:11px;color:var(--aho-color-ink-500)">TOPLAM ÇAĞRI</div>
            <div style="font-size:24px;font-weight:700;margin-top:4px"><?= (int)$metrics['total'] ?></div>
        </div>
        <div class="aho-card" style="padding:16px">
            <div style="font-size:11px;color:var(--aho-color-ink-500)">BUGÜN</div>
            <div style="font-size:24px;font-weight:700;color:#0891b2;margin-top:4px"><?= (int)$metrics['today'] ?></div>
        </div>
        <div class="aho-card" style="padding:16px">
            <div style="font-size:11px;color:var(--aho-color-ink-500)">BU AY</div>
            <div style="font-size:24px;font-weight:700;color:#0891b2;margin-top:4px"><?= (int)$metrics['month'] ?></div>
        </div>
        <div class="aho-card" style="padding:16px">
            <div style="font-size:11px;color:var(--aho-color-ink-500)">🎨 SİTE ÜRETİLDİ</div>
            <div style="font-size:24px;font-weight:700;color:#8b5cf6;margin-top:4px"><?= (int)$metrics['sites_generated'] ?></div>
        </div>
    </div>

    <!-- Günlük çağrı grafiği (basit SVG bar chart) -->
    <?php if (!empty($byDay)): ?>
    <div class="aho-card" style="padding:20px;margin-bottom:20px">
        <h3 style="margin-top:0;font-size:15px">📈 Son 30 Gün Kullanım</h3>
        <div style="display:flex;align-items:flex-end;gap:2px;height:120px;padding:10px 0;overflow-x:auto">
            <?php foreach ($byDay as $d):
                $h = max(2, (int) round($d['c'] / $max * 100));
            ?>
                <div style="flex:1;min-width:12px;background:#0ea5e9;height:<?= $h ?>%;border-radius:2px 2px 0 0" title="<?= e($d['d']) ?>: <?= (int)$d['c'] ?> çağrı"></div>
            <?php endforeach; ?>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--aho-color-ink-500);margin-top:6px">
            <span><?= e($byDay[0]['d'] ?? '') ?></span>
            <span><?= e(end($byDay)['d'] ?? '') ?></span>
        </div>
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px">
        <!-- Son çağrılar -->
        <div class="aho-card" style="padding:0;overflow:auto">
            <div style="padding:14px 20px;border-bottom:1px solid var(--aho-color-border)">
                <h3 style="margin:0;font-size:15px">💬 Son 20 AI Çağrısı</h3>
            </div>
            <?php if (empty($recent)): ?>
                <div style="padding:32px;text-align:center;color:var(--aho-color-ink-500);font-size:13px">Henüz AI çağrısı yok.</div>
            <?php else: ?>
                <table style="width:100%;border-collapse:collapse;font-size:13px">
                    <thead style="background:var(--aho-color-ink-50);text-align:left">
                        <tr>
                            <th style="padding:10px 16px">Zaman</th>
                            <th style="padding:10px 16px">Bağlam</th>
                            <th style="padding:10px 16px">Prov.</th>
                            <th style="padding:10px 16px">Prompt</th>
                            <th style="padding:10px 16px;text-align:right">Token</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recent as $r): ?>
                        <tr style="border-top:1px solid var(--aho-color-border)">
                            <td style="padding:10px 16px;font-size:11px;color:var(--aho-color-ink-500)"><?= e(date('d.m H:i', strtotime((string)$r['created_at']))) ?></td>
                            <td style="padding:10px 16px">
                                <span style="padding:2px 8px;font-size:10px;border-radius:8px;background:#f3f4f6;color:#374151;font-weight:600"><?= e($r['context']) ?></span>
                            </td>
                            <td style="padding:10px 16px;font-size:11px">
                                <?= $r['provider'] === 'openai' ? '🧠' : '⚡' ?> <?= e($r['provider']) ?>
                            </td>
                            <td style="padding:10px 16px;font-size:12px;color:var(--aho-color-ink-700);max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($r['prompt_short']) ?></td>
                            <td style="padding:10px 16px;text-align:right;font-family:monospace;font-size:12px"><?= (int)$r['tokens_used'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Bağlam dağılımı -->
        <div class="aho-card" style="padding:20px">
            <h3 style="margin-top:0;font-size:15px">🎯 Bağlam Dağılımı (30 gün)</h3>
            <?php if (empty($byContext)): ?>
                <div style="text-align:center;color:var(--aho-color-ink-500);font-size:13px;padding:24px 0">Veri yok</div>
            <?php else: ?>
                <?php $total = array_sum(array_column($byContext, 'cnt')); ?>
                <?php foreach ($byContext as $c): $pct = $total > 0 ? round($c['cnt'] / $total * 100) : 0; ?>
                    <div style="margin-bottom:12px">
                        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
                            <span style="font-weight:600"><?= e($c['context']) ?></span>
                            <span style="color:var(--aho-color-ink-500)"><?= (int)$c['cnt'] ?> (<?= $pct ?>%)</span>
                        </div>
                        <div style="background:#e5e7eb;border-radius:6px;height:8px;overflow:hidden">
                            <div style="width:<?= $pct ?>%;height:100%;background:#0ea5e9"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $view->endSection(); ?>
