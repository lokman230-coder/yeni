<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>💱 Kur Yönetimi</h1>
            <p>TCMB kuru + kar marjı = müşteriye gösterilen kur. Marjı değiştirin, aktif/pasif ayarlayın veya TCMB'den anlık güncelleyin.</p>
        </div>
        <div style="display:flex;gap:8px">
            <form method="post" action="/admin/kur-yonetimi/refresh" style="margin:0">
                <?= csrf() ?>
                <button type="submit" class="aho-btn aho-btn--primary">🔄 TCMB'den Şimdi Çek</button>
            </form>
        </div>
    </div>

    <?php if (!empty($success)): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>
    <?php if (!empty($info)): ?><div class="aho-alert aho-alert--info"><?= e($info) ?></div><?php endif; ?>

    <div class="aho-card" style="margin-bottom:16px">
        <div style="padding:12px 16px;background:var(--aho-color-primary-50, #eff6ff);border-left:4px solid var(--aho-color-primary-600);font-size:14px;line-height:1.6">
            <strong>💡 Formül:</strong> <code>Görünen Kur = TCMB Kuru × (1 + Marj% ÷ 100)</code><br>
            Örn: USD TCMB=47.25, Marj=%2 → Görünen: <strong>48.20 TRY</strong> (fiyatlar bu kur ile çevrilir)
        </div>
    </div>

    <form method="post" action="/admin/kur-yonetimi/kaydet">
        <?= csrf() ?>
        <div class="aho-card" style="padding:0;overflow:auto">
            <table class="aho-table" style="width:100%;border-collapse:collapse;font-size:14px">
                <thead style="background:var(--aho-color-ink-50);text-align:left">
                    <tr>
                        <th style="padding:12px">Kod</th>
                        <th style="padding:12px">Sembol</th>
                        <th style="padding:12px;text-align:right">TCMB Kuru</th>
                        <th style="padding:12px;text-align:center">Marj (%)</th>
                        <th style="padding:12px;text-align:right">Görünen Kur</th>
                        <th style="padding:12px;text-align:center">Kaynak</th>
                        <th style="padding:12px;text-align:center">Son Güncelleme</th>
                        <th style="padding:12px;text-align:center">Aktif</th>
                        <th style="padding:12px;text-align:center">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rates as $r):
                    $isTry = $r['currency'] === 'TRY';
                    $raw = (float) $r['rate'];
                    $margin = (float) $r['margin_percent'];
                    $shown = $raw * (1 + $margin / 100);
                ?>
                    <tr style="border-top:1px solid var(--aho-color-border)">
                        <td style="padding:12px;font-weight:600"><?= e($r['currency']) ?></td>
                        <td style="padding:12px;font-size:18px"><?= e($r['symbol'] ?? '') ?></td>
                        <td style="padding:12px;text-align:right;font-variant-numeric:tabular-nums;color:var(--aho-color-ink-600)">
                            <?= $isTry ? '—' : number_format($raw, 4, ',', '.') ?>
                        </td>
                        <td style="padding:8px;text-align:center">
                            <?php if ($isTry): ?>
                                <span style="color:var(--aho-color-ink-400)">—</span>
                            <?php else: ?>
                                <input type="number" step="0.01" min="-10" max="50"
                                       name="margin[<?= (int)$r['id'] ?>]"
                                       value="<?= e(number_format($margin, 2, '.', '')) ?>"
                                       style="width:80px;padding:6px 8px;text-align:right;border:1px solid var(--aho-color-border);border-radius:6px;font-variant-numeric:tabular-nums">
                            <?php endif; ?>
                        </td>
                        <td style="padding:12px;text-align:right;font-weight:600;font-variant-numeric:tabular-nums;color:var(--aho-color-success, #059669)">
                            <?= $isTry ? '1,0000' : number_format($shown, 4, ',', '.') ?>
                        </td>
                        <td style="padding:12px;text-align:center">
                            <?php
                            $srcBadge = match ($r['source']) {
                                'tcmb'              => ['🇹🇷 TCMB',  '#059669'],
                                'exchangerate.host' => ['🌐 API',    '#0891b2'],
                                default             => ['✍️ Manuel', '#6b7280'],
                            };
                            ?>
                            <span style="font-size:12px;padding:2px 8px;border-radius:10px;background:#f3f4f6;color:<?= $srcBadge[1] ?>">
                                <?= e($srcBadge[0]) ?>
                            </span>
                        </td>
                        <td style="padding:12px;text-align:center;font-size:12px;color:var(--aho-color-ink-500)">
                            <?= $r['updated_at'] ? e(date('d.m H:i', strtotime((string)$r['updated_at']))) : '—' ?>
                        </td>
                        <td style="padding:12px;text-align:center">
                            <?php if ($isTry): ?>
                                <span style="color:var(--aho-color-ink-400)">Zorunlu</span>
                            <?php else: ?>
                                <label class="aho-switch">
                                    <input type="checkbox" name="active[<?= (int)$r['id'] ?>]" value="1" <?= ((int)($r['is_active'] ?? 1) === 1) ? 'checked' : '' ?>>
                                    <span style="display:inline-block;width:36px;height:20px;background:#e5e7eb;border-radius:10px;position:relative;cursor:pointer"></span>
                                </label>
                            <?php endif; ?>
                        </td>
                        <td style="padding:12px;text-align:center">
                            <?php if (!$isTry): ?>
                                <form method="post" action="/admin/kur-yonetimi/<?= (int)$r['id'] ?>/sil" style="display:inline"
                                      onsubmit="return confirm('<?= e($r['currency']) ?> silinsin mi?')">
                                    <?= csrf() ?>
                                    <button type="submit" style="background:none;border:0;color:#dc2626;cursor:pointer;font-size:16px">🗑️</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div style="padding:16px;text-align:right;border-top:1px solid var(--aho-color-border);background:var(--aho-color-ink-50)">
                <button type="submit" class="aho-btn aho-btn--primary">💾 Marjları Kaydet</button>
            </div>
        </div>
    </form>

    <div class="aho-card" style="margin-top:24px">
        <h3 style="margin-top:0">➕ Yeni Para Birimi Ekle</h3>
        <form method="post" action="/admin/kur-yonetimi/ekle" style="display:flex;gap:12px;flex-wrap:wrap;align-items:end">
            <?= csrf() ?>
            <div>
                <label style="display:block;font-size:12px;margin-bottom:4px;color:var(--aho-color-ink-600)">Kod (3 harf)</label>
                <input type="text" name="currency" placeholder="JPY" maxlength="3" required
                       style="width:100px;padding:8px;border:1px solid var(--aho-color-border);border-radius:6px;text-transform:uppercase">
            </div>
            <div>
                <label style="display:block;font-size:12px;margin-bottom:4px;color:var(--aho-color-ink-600)">Sembol</label>
                <input type="text" name="symbol" placeholder="¥" maxlength="8"
                       style="width:100px;padding:8px;border:1px solid var(--aho-color-border);border-radius:6px">
            </div>
            <div>
                <label style="display:block;font-size:12px;margin-bottom:4px;color:var(--aho-color-ink-600)">Marj (%)</label>
                <input type="number" step="0.01" name="margin" value="2.00"
                       style="width:100px;padding:8px;border:1px solid var(--aho-color-border);border-radius:6px;text-align:right">
            </div>
            <button type="submit" class="aho-btn aho-btn--ghost">Ekle</button>
        </form>
        <p style="margin-top:12px;color:var(--aho-color-ink-500);font-size:13px">
            Ekleme sonrası TCMB'den Şimdi Çek butonuna basın; TCMB destekliyorsa kur otomatik gelir.
        </p>
    </div>

    <div class="aho-card" style="margin-top:24px">
        <h3 style="margin-top:0">🤖 Otomatik Güncelleme</h3>
        <p style="color:var(--aho-color-ink-700);font-size:14px">
            Kurlar <code>currency:update</code> cron'u ile <strong>saatte bir</strong> otomatik güncellenir.
            <br>Kaynak: <strong>TCMB</strong> (birincil) → başarısızsa <strong>exchangerate.host</strong> (fallback).
            <br>Sizin girdiğiniz marj her güncellemede korunur.
        </p>
        <div style="font-size:13px;color:var(--aho-color-ink-500);margin-top:8px">
            Cron aktif edilmediyse: <code>* * * * * cd /path/to/ahost && php console cron:run</code>
        </div>
    </div>
</div>

<style>
    .aho-switch input:checked + span {
        background: var(--aho-color-primary-600, #2563eb) !important;
    }
    .aho-switch input:checked + span::after {
        transform: translateX(16px);
    }
    .aho-switch span::after {
        content: '';
        position: absolute;
        top: 2px; left: 2px;
        width: 16px; height: 16px;
        background: #fff;
        border-radius: 50%;
        transition: transform .2s;
    }
    .aho-switch input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
</style>
<?php $view->endSection(); ?>
