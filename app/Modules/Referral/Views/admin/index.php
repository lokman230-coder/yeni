<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>🎁 Referans / Affiliate Programı</h1>
            <p>Program ayarları, top affiliate'lar, bekleyen komisyon onayları.</p>
        </div>
    </div>

    <?php if (!empty($success)): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

    <!-- Genel metrikler -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:24px">
        <div class="aho-card" style="padding:16px">
            <div style="font-size:11px;color:var(--aho-color-ink-500)">AFFILIATE</div>
            <div style="font-size:24px;font-weight:700"><?= (int)$metrics['total_codes'] ?></div>
        </div>
        <div class="aho-card" style="padding:16px">
            <div style="font-size:11px;color:var(--aho-color-ink-500)">TIKLAMA</div>
            <div style="font-size:24px;font-weight:700"><?= (int)$metrics['total_visits'] ?></div>
        </div>
        <div class="aho-card" style="padding:16px">
            <div style="font-size:11px;color:var(--aho-color-ink-500)">KAYIT</div>
            <div style="font-size:24px;font-weight:700"><?= (int)$metrics['total_signups'] ?></div>
        </div>
        <div class="aho-card" style="padding:16px">
            <div style="font-size:11px;color:var(--aho-color-ink-500)">SATIN ALAN</div>
            <div style="font-size:24px;font-weight:700;color:#059669"><?= (int)$metrics['total_conversions'] ?></div>
        </div>
        <div class="aho-card" style="padding:16px">
            <div style="font-size:11px;color:var(--aho-color-ink-500)">BEKLEYEN ₺</div>
            <div style="font-size:20px;font-weight:700;color:#d97706"><?= number_format($metrics['pending_amount'], 2, ',', '.') ?> ₺</div>
        </div>
        <div class="aho-card" style="padding:16px">
            <div style="font-size:11px;color:var(--aho-color-ink-500)">ONAYLI ₺</div>
            <div style="font-size:20px;font-weight:700;color:#059669"><?= number_format($metrics['approved_amount'], 2, ',', '.') ?> ₺</div>
        </div>
    </div>

    <!-- Program Ayarları -->
    <div class="aho-card" style="margin-bottom:24px;padding:24px">
        <h3 style="margin-top:0">⚙️ Program Ayarları</h3>
        <form method="post" action="/admin/referral/ayarlar" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;align-items:end">
            <?= csrf() ?>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Komisyon (%)</label>
                <input type="number" step="0.1" min="0" max="50" name="commission_percent"
                       value="<?= e(number_format((float)$settings['commission_percent'], 2, '.', '')) ?>"
                       style="width:100%;padding:8px;border:1px solid var(--aho-color-border);border-radius:6px;box-sizing:border-box">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Cookie Süresi (gün)</label>
                <input type="number" min="1" max="365" name="cookie_days"
                       value="<?= (int)$settings['cookie_days'] ?>"
                       style="width:100%;padding:8px;border:1px solid var(--aho-color-border);border-radius:6px;box-sizing:border-box">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Min. Ödeme (₺)</label>
                <input type="number" step="0.01" min="0" name="min_payout"
                       value="<?= e(number_format((float)$settings['min_payout'], 2, '.', '')) ?>"
                       style="width:100%;padding:8px;border:1px solid var(--aho-color-border);border-radius:6px;box-sizing:border-box">
            </div>
            <div style="display:flex;gap:16px;align-items:center">
                <label style="display:flex;gap:6px;align-items:center;font-size:13px;cursor:pointer">
                    <input type="checkbox" name="first_order_only" value="1" <?= (int)$settings['first_order_only'] === 1 ? 'checked' : '' ?>>
                    Sadece ilk sipariş
                </label>
                <label style="display:flex;gap:6px;align-items:center;font-size:13px;cursor:pointer">
                    <input type="checkbox" name="is_active" value="1" <?= (int)$settings['is_active'] === 1 ? 'checked' : '' ?>>
                    Program aktif
                </label>
            </div>
            <div>
                <button type="submit" class="aho-btn aho-btn--primary" style="width:100%">💾 Kaydet</button>
            </div>
        </form>
    </div>

    <!-- Top 10 -->
    <div class="aho-card" style="margin-bottom:24px;padding:0;overflow:auto">
        <div style="padding:16px 24px;border-bottom:1px solid var(--aho-color-border)">
            <h3 style="margin:0">🏆 Top 10 Affiliate</h3>
        </div>
        <?php if (empty($top)): ?>
            <div style="padding:32px;text-align:center;color:var(--aho-color-ink-500)">Henüz affiliate yok.</div>
        <?php else: ?>
        <table style="width:100%;border-collapse:collapse;font-size:14px">
            <thead style="background:var(--aho-color-ink-50);text-align:left">
                <tr>
                    <th style="padding:10px 16px">#</th>
                    <th style="padding:10px 16px">Müşteri</th>
                    <th style="padding:10px 16px">Kod</th>
                    <th style="padding:10px 16px;text-align:right">Tıklama</th>
                    <th style="padding:10px 16px;text-align:right">Kayıt</th>
                    <th style="padding:10px 16px;text-align:right">Satın Alan</th>
                    <th style="padding:10px 16px;text-align:right">Kazanç</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($top as $i => $t): ?>
                <tr style="border-top:1px solid var(--aho-color-border)">
                    <td style="padding:10px 16px;color:var(--aho-color-ink-500)"><?= $i + 1 ?></td>
                    <td style="padding:10px 16px"><?= e($t['email'] ?? '—') ?></td>
                    <td style="padding:10px 16px"><code style="font-weight:600;color:#059669"><?= e($t['code']) ?></code></td>
                    <td style="padding:10px 16px;text-align:right"><?= (int)$t['total_visits'] ?></td>
                    <td style="padding:10px 16px;text-align:right"><?= (int)$t['total_signups'] ?></td>
                    <td style="padding:10px 16px;text-align:right;color:#059669;font-weight:600"><?= (int)$t['total_conversions'] ?></td>
                    <td style="padding:10px 16px;text-align:right;font-weight:700"><?= number_format((float)$t['total_earned'], 2, ',', '.') ?> ₺</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Bekleyen Payout İstekleri -->
    <div class="aho-card" style="padding:0;overflow:auto;margin-bottom:24px">
        <div style="padding:16px 24px;border-bottom:1px solid var(--aho-color-border);background:#fef3c7">
            <h3 style="margin:0">💸 Bekleyen Payout İstekleri (<?= count($payouts) ?>)</h3>
        </div>
        <?php if (empty($payouts)): ?>
            <div style="padding:20px;text-align:center;color:var(--aho-color-ink-500);font-size:13px">Bekleyen payout isteği yok.</div>
        <?php else: ?>
        <table style="width:100%;border-collapse:collapse;font-size:13px">
            <thead style="background:var(--aho-color-ink-50);text-align:left">
                <tr>
                    <th style="padding:10px 16px">Tarih</th>
                    <th style="padding:10px 16px">Müşteri</th>
                    <th style="padding:10px 16px;text-align:right">Tutar</th>
                    <th style="padding:10px 16px">IBAN</th>
                    <th style="padding:10px 16px">Alıcı / Banka</th>
                    <th style="padding:10px 16px;text-align:center">Durum</th>
                    <th style="padding:10px 16px;text-align:right">İşlem</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($payouts as $p): ?>
                <tr style="border-top:1px solid var(--aho-color-border)">
                    <td style="padding:10px 16px;font-size:12px"><?= e(date('d.m.Y H:i', strtotime((string)$p['created_at']))) ?></td>
                    <td style="padding:10px 16px">
                        <div><?= e(trim((string)$p['customer_name']) ?: '—') ?></div>
                        <div style="font-size:11px;color:var(--aho-color-ink-500)"><?= e($p['customer_email'] ?? '') ?></div>
                    </td>
                    <td style="padding:10px 16px;text-align:right;font-weight:700;color:#0891b2"><?= number_format((float)$p['amount'], 2, ',', '.') ?> ₺</td>
                    <td style="padding:10px 16px;font-family:monospace;font-size:12px"><?= e($p['iban']) ?></td>
                    <td style="padding:10px 16px;font-size:12px">
                        <div><?= e($p['account_holder']) ?></div>
                        <div style="color:var(--aho-color-ink-500)"><?= e($p['bank_name']) ?></div>
                    </td>
                    <td style="padding:10px 16px;text-align:center">
                        <span style="padding:3px 8px;font-size:11px;border-radius:10px;background:<?= $p['status']==='approved' ? '#e0f2fe':'#fef3c7' ?>;color:<?= $p['status']==='approved' ? '#0891b2':'#92400e' ?>">
                            <?= $p['status'] === 'approved' ? '✓ Onaylı' : '⏳ Bekliyor' ?>
                        </span>
                    </td>
                    <td style="padding:10px 16px;text-align:right;white-space:nowrap">
                        <?php if ($p['status'] === 'pending'): ?>
                            <form method="post" action="/admin/referral/payout/<?= (int)$p['id'] ?>/onayla" style="display:inline">
                                <?= csrf() ?>
                                <button type="submit" style="padding:5px 10px;background:#0891b2;color:#fff;border:0;border-radius:6px;cursor:pointer;font-size:11px">✓ Onayla</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" action="/admin/referral/payout/<?= (int)$p['id'] ?>/odendi" style="display:inline" onsubmit="return confirm('Havale yapıldı olarak işaretlensin mi?')">
                            <?= csrf() ?>
                            <button type="submit" style="padding:5px 10px;background:#059669;color:#fff;border:0;border-radius:6px;cursor:pointer;font-size:11px">💰 Ödendi</button>
                        </form>
                        <form method="post" action="/admin/referral/payout/<?= (int)$p['id'] ?>/reddet" style="display:inline">
                            <?= csrf() ?>
                            <input type="hidden" name="note" value="Reddedildi">
                            <button type="submit" onclick="return confirm('Payout reddedilsin mi? Bakiye müşteriye iade edilir.')" style="padding:5px 10px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:6px;cursor:pointer;font-size:11px">✗ Red</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Bekleyen komisyonlar -->
    <div class="aho-card" style="padding:0;overflow:auto">
        <div style="padding:16px 24px;border-bottom:1px solid var(--aho-color-border);display:flex;justify-content:space-between;align-items:center">
            <h3 style="margin:0">⏳ Onay Bekleyen Komisyonlar (<?= count($pending) ?>)</h3>
        </div>
        <?php if (empty($pending)): ?>
            <div style="padding:32px;text-align:center;color:var(--aho-color-ink-500)">Bekleyen komisyon yok. 🎉</div>
        <?php else: ?>
        <table style="width:100%;border-collapse:collapse;font-size:14px">
            <thead style="background:var(--aho-color-ink-50);text-align:left">
                <tr>
                    <th style="padding:10px 16px">Tarih</th>
                    <th style="padding:10px 16px">Referrer</th>
                    <th style="padding:10px 16px">Yönlendirilen</th>
                    <th style="padding:10px 16px;text-align:right">Sipariş</th>
                    <th style="padding:10px 16px;text-align:right">Komisyon</th>
                    <th style="padding:10px 16px;text-align:center">İşlem</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pending as $c): ?>
                <tr style="border-top:1px solid var(--aho-color-border)">
                    <td style="padding:10px 16px;font-size:12px;color:var(--aho-color-ink-500)"><?= e(date('d.m.Y H:i', strtotime((string)$c['created_at']))) ?></td>
                    <td style="padding:10px 16px"><?= e($c['referrer_email'] ?? '—') ?></td>
                    <td style="padding:10px 16px;font-size:13px"><?= e($c['referred_email'] ?? '—') ?></td>
                    <td style="padding:10px 16px;text-align:right"><?= number_format((float)$c['order_total'], 2, ',', '.') ?> <?= e($c['currency']) ?></td>
                    <td style="padding:10px 16px;text-align:right;font-weight:700;color:#d97706">
                        <?= number_format((float)$c['commission_amount'], 2, ',', '.') ?> <?= e($c['currency']) ?>
                        <div style="font-size:11px;color:var(--aho-color-ink-500);font-weight:400">%<?= number_format((float)$c['commission_percent'], 1, ',', '.') ?></div>
                    </td>
                    <td style="padding:10px 16px;text-align:center">
                        <form method="post" action="/admin/referral/komisyon/<?= (int)$c['id'] ?>/onayla" style="display:inline">
                            <?= csrf() ?>
                            <button type="submit" style="padding:6px 12px;background:#059669;color:#fff;border:0;border-radius:6px;cursor:pointer;font-size:12px">✓ Onayla</button>
                        </form>
                        <form method="post" action="/admin/referral/komisyon/<?= (int)$c['id'] ?>/reddet" style="display:inline">
                            <?= csrf() ?>
                            <button type="submit" style="padding:6px 12px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:6px;cursor:pointer;font-size:12px">✗ Red</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php $view->endSection(); ?>
