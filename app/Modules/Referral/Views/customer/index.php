<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');

$code = $stats['code'];
$pending = $stats['pending_amount'];
$approved = $stats['approved_amount'];
?>
<section class="aho-pages-hero" style="background:linear-gradient(135deg,#059669 0%,#0891b2 100%);color:#fff">
    <div class="aho-container" style="padding:40px 20px">
        <div style="font-size:44px">🎁</div>
        <h1 style="margin:8px 0;font-size:32px">Referans Programım</h1>
        <p style="opacity:.9;font-size:16px;margin:0">
            Arkadaşlarınızı davet edin, her satın alımdan
            <strong>%<?= number_format((float)$settings['commission_percent'], 0, ',', '') ?></strong>
            komisyon kazanın.
        </p>
    </div>
</section>

<section style="padding:40px 0">
    <div class="aho-container" style="max-width:960px">

        <!-- Metrik kartları -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:28px">
            <div class="aho-card" style="padding:20px;text-align:center">
                <div style="font-size:12px;color:var(--aho-color-ink-500);margin-bottom:4px">TOPLAM TIKLAMA</div>
                <div style="font-size:28px;font-weight:700"><?= (int) ($code['total_visits'] ?? 0) ?></div>
            </div>
            <div class="aho-card" style="padding:20px;text-align:center">
                <div style="font-size:12px;color:var(--aho-color-ink-500);margin-bottom:4px">KAYIT OLAN</div>
                <div style="font-size:28px;font-weight:700"><?= (int) ($code['total_signups'] ?? 0) ?></div>
            </div>
            <div class="aho-card" style="padding:20px;text-align:center">
                <div style="font-size:12px;color:var(--aho-color-ink-500);margin-bottom:4px">SATIN ALAN</div>
                <div style="font-size:28px;font-weight:700;color:#059669"><?= (int) ($code['total_conversions'] ?? 0) ?></div>
            </div>
            <div class="aho-card" style="padding:20px;text-align:center;background:linear-gradient(135deg,#059669 0%,#0891b2 100%);color:#fff">
                <div style="font-size:12px;opacity:.9;margin-bottom:4px">KAZANDIĞIN</div>
                <div style="font-size:24px;font-weight:700"><?= number_format((float)($code['total_earned'] ?? 0), 2, ',', '.') ?> ₺</div>
            </div>
        </div>

        <!-- Link paylaş -->
        <div class="aho-card" style="padding:28px;margin-bottom:24px">
            <h3 style="margin-top:0;font-size:18px">🔗 Referans Linkin</h3>
            <p style="color:var(--aho-color-ink-600);font-size:14px;margin-top:4px">
                Bu linki paylaş — bu link üzerinden gelen ziyaretçi kayıt olup satın alım yaparsa
                sen %<?= number_format((float)$settings['commission_percent'], 0, ',', '') ?> komisyon kazanırsın.
                Cookie süresi: <strong><?= (int) $settings['cookie_days'] ?> gün</strong>.
            </p>

            <div style="display:flex;gap:8px;margin-top:14px">
                <input type="text" id="refLink" value="<?= e($shareUrl) ?>" readonly
                       style="flex:1;padding:14px;border:2px solid var(--aho-color-primary-600);border-radius:8px;font-family:monospace;font-size:14px;background:#f9fafb">
                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('refLink').value).then(()=>{this.textContent='✓ Kopyalandı';setTimeout(()=>this.textContent='📋 Kopyala',2000)})"
                        class="aho-btn aho-btn--primary" style="min-width:140px">📋 Kopyala</button>
            </div>

            <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap">
                <a target="_blank" href="https://wa.me/?text=<?= urlencode('Ahost Bilişim\'de hosting almak için: ' . $shareUrl) ?>"
                   class="aho-btn aho-btn--ghost" style="background:#25d366;color:#fff;border-color:#25d366">💬 WhatsApp</a>
                <a target="_blank" href="https://twitter.com/intent/tweet?text=<?= urlencode('Ahost Bilişim öneriyorum: ' . $shareUrl) ?>"
                   class="aho-btn aho-btn--ghost" style="background:#000;color:#fff;border-color:#000">𝕏 Paylaş</a>
                <a target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareUrl) ?>"
                   class="aho-btn aho-btn--ghost" style="background:#1877f2;color:#fff;border-color:#1877f2">📘 Facebook</a>
                <a href="mailto:?subject=<?= urlencode('Ahost Bilişim öneriyorum') ?>&body=<?= urlencode("Merhaba,\n\nAhost Bilişim'de hosting/domain almak için şu linki kullanabilirsin:\n$shareUrl") ?>"
                   class="aho-btn aho-btn--ghost">✉️ E-posta</a>
            </div>

            <div style="margin-top:16px;padding:12px;background:#f0fdf4;border-left:3px solid #059669;font-size:13px;color:var(--aho-color-ink-700)">
                💡 <strong>Referans Kodun:</strong> <code style="font-size:16px;font-weight:700;color:#059669"><?= e($code['code'] ?? '') ?></code>
                — Bu kodu manuel olarak da paylaşabilirsin.
            </div>
        </div>

        <!-- Bakiye durumu -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px" class="aho-referral-balance">
            <div class="aho-card" style="padding:20px">
                <div style="font-size:13px;color:var(--aho-color-ink-500)">⏳ Onay Bekleyen</div>
                <div style="font-size:22px;font-weight:700;color:#d97706;margin-top:6px">
                    <?= number_format($pending, 2, ',', '.') ?> ₺
                </div>
                <div style="font-size:12px;color:var(--aho-color-ink-500);margin-top:4px">Admin onayından sonra bakiyene eklenir</div>
            </div>
            <div class="aho-card" style="padding:20px">
                <div style="font-size:13px;color:var(--aho-color-ink-500)">💰 Kullanılabilir Bakiye</div>
                <div style="font-size:22px;font-weight:700;color:#059669;margin-top:6px">
                    <?= number_format((float)($customer['balance'] ?? 0), 2, ',', '.') ?> ₺
                </div>
                <div style="font-size:12px;color:var(--aho-color-ink-500);margin-top:4px">Hizmet alımlarında ya da havale çekiminde kullan</div>
            </div>
        </div>

        <!-- Payout (Bakiye Çekimi) -->
        <?php
            $custBalance = (float) ($customer['balance'] ?? 0);
            $minPayout = (float) $settings['min_payout'];
            $canRequest = $custBalance >= $minPayout;
        ?>
        <div class="aho-card" style="padding:24px;margin-bottom:24px">
            <h3 style="margin-top:0">💸 Bakiyeyi Banka Havalesi ile Çek</h3>
            <p style="color:var(--aho-color-ink-600);font-size:14px;margin:8px 0 16px">
                Minimum çekim: <strong><?= number_format($minPayout, 2, ',', '.') ?> ₺</strong>
                · İşlem 1-3 iş günü sürer · TR IBAN zorunludur.
            </p>

            <?php if ($canRequest): ?>
                <form method="post" action="/panel/referanslarim/payout" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <?= csrf() ?>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Tutar (₺) *</label>
                        <input type="number" step="0.01" name="amount" min="<?= $minPayout ?>" max="<?= $custBalance ?>" required
                               value="<?= number_format($custBalance, 2, '.', '') ?>"
                               style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px;text-align:right;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">IBAN *</label>
                        <input type="text" name="iban" required maxlength="34" pattern="TR[0-9 ]{22,32}"
                               placeholder="TR00 0000 0000 0000 0000 0000 00"
                               style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px;font-family:monospace;box-sizing:border-box;text-transform:uppercase">
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Hesap Sahibi *</label>
                        <input type="text" name="account_holder" required maxlength="191"
                               placeholder="Ad Soyad"
                               style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Banka Adı *</label>
                        <input type="text" name="bank_name" required maxlength="100"
                               placeholder="Ör: Ziraat Bankası"
                               style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px;box-sizing:border-box">
                    </div>
                    <div style="grid-column:1/-1;text-align:right">
                        <button type="submit" class="aho-btn aho-btn--primary">Çekim İsteği Oluştur</button>
                    </div>
                </form>
            <?php else: ?>
                <div style="padding:12px;background:#f9fafb;border-radius:8px;color:var(--aho-color-ink-600);font-size:14px">
                    ⏳ Çekim yapabilmek için bakiyenizin <strong><?= number_format($minPayout, 2, ',', '.') ?> ₺</strong> olması gerekiyor.
                    (Mevcut: <?= number_format($custBalance, 2, ',', '.') ?> ₺)
                </div>
            <?php endif; ?>

            <?php if (!empty($payouts)): ?>
                <h4 style="margin:20px 0 8px;font-size:14px;color:var(--aho-color-ink-700)">Geçmiş Çekimler</h4>
                <div style="border:1px solid var(--aho-color-border);border-radius:8px;overflow:hidden">
                    <table style="width:100%;border-collapse:collapse;font-size:13px">
                        <thead style="background:var(--aho-color-ink-50)">
                            <tr>
                                <th style="padding:10px;text-align:left">Tarih</th>
                                <th style="padding:10px;text-align:right">Tutar</th>
                                <th style="padding:10px">IBAN</th>
                                <th style="padding:10px;text-align:center">Durum</th>
                                <th style="padding:10px;text-align:right">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($payouts as $p):
                            $badge = match ($p['status']) {
                                'pending'   => ['⏳ Bekliyor',  '#d97706', '#fef3c7'],
                                'approved'  => ['✓ Onaylı',    '#0891b2', '#e0f2fe'],
                                'paid'      => ['💰 Ödendi',   '#059669', '#d1fae5'],
                                'rejected'  => ['❌ Reddedildi','#dc2626', '#fee2e2'],
                                'cancelled' => ['❌ İptal',     '#6b7280', '#f3f4f6'],
                                default     => [$p['status'],   '#6b7280', '#f3f4f6'],
                            };
                        ?>
                            <tr style="border-top:1px solid var(--aho-color-border)">
                                <td style="padding:10px"><?= e(date('d.m.Y H:i', strtotime((string)$p['created_at']))) ?></td>
                                <td style="padding:10px;text-align:right;font-weight:600"><?= number_format((float)$p['amount'], 2, ',', '.') ?> ₺</td>
                                <td style="padding:10px;font-family:monospace;font-size:12px"><?= e(substr($p['iban'], 0, 8) . '…' . substr($p['iban'], -4)) ?></td>
                                <td style="padding:10px;text-align:center">
                                    <span style="padding:3px 8px;font-size:11px;border-radius:10px;color:<?= $badge[1] ?>;background:<?= $badge[2] ?>"><?= e($badge[0]) ?></span>
                                </td>
                                <td style="padding:10px;text-align:right">
                                    <?php if ($p['status'] === 'pending'): ?>
                                        <form method="post" action="/panel/referanslarim/payout/<?= (int)$p['id'] ?>/iptal" style="display:inline" onsubmit="return confirm('Çekim isteğini iptal etmek istediğinize emin misiniz?')">
                                            <?= csrf() ?>
                                            <button type="submit" style="background:none;border:0;color:#dc2626;cursor:pointer;font-size:12px;text-decoration:underline">İptal</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Komisyon geçmişi -->
        <div class="aho-card" style="padding:0;overflow:auto">
            <div style="padding:20px 24px;border-bottom:1px solid var(--aho-color-border)">
                <h3 style="margin:0;font-size:16px">📊 Komisyon Geçmişi</h3>
            </div>
            <?php if (empty($stats['recent_commissions'])): ?>
                <div style="padding:40px;text-align:center;color:var(--aho-color-ink-500)">
                    Henüz komisyon kaydın yok.<br>
                    <span style="font-size:13px">Linkini paylaşmaya başla, ilk kazancın yolda!</span>
                </div>
            <?php else: ?>
                <table style="width:100%;border-collapse:collapse;font-size:14px">
                    <thead style="background:var(--aho-color-ink-50);text-align:left">
                        <tr>
                            <th style="padding:12px 16px">Tarih</th>
                            <th style="padding:12px 16px">Referans</th>
                            <th style="padding:12px 16px;text-align:right">Sipariş</th>
                            <th style="padding:12px 16px;text-align:right">Komisyon</th>
                            <th style="padding:12px 16px;text-align:center">Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($stats['recent_commissions'] as $c): ?>
                        <tr style="border-top:1px solid var(--aho-color-border)">
                            <td style="padding:12px 16px;color:var(--aho-color-ink-600)"><?= e(date('d.m.Y H:i', strtotime((string)$c['created_at']))) ?></td>
                            <td style="padding:12px 16px;font-size:13px"><?= e($c['referred_email'] ?? '—') ?></td>
                            <td style="padding:12px 16px;text-align:right;color:var(--aho-color-ink-600)"><?= number_format((float)$c['order_total'], 2, ',', '.') ?> <?= e($c['currency']) ?></td>
                            <td style="padding:12px 16px;text-align:right;font-weight:600"><?= number_format((float)$c['commission_amount'], 2, ',', '.') ?> <?= e($c['currency']) ?></td>
                            <td style="padding:12px 16px;text-align:center">
                                <?php
                                $badge = match ($c['status']) {
                                    'pending'  => ['⏳ Bekliyor', '#d97706', '#fef3c7'],
                                    'approved' => ['✅ Onaylı',   '#059669', '#d1fae5'],
                                    'paid'     => ['💰 Ödendi',   '#059669', '#d1fae5'],
                                    'rejected' => ['❌ Red',      '#dc2626', '#fee2e2'],
                                    default    => [$c['status'],  '#6b7280', '#f3f4f6'],
                                };
                                ?>
                                <span style="padding:3px 10px;font-size:12px;border-radius:12px;color:<?= $badge[1] ?>;background:<?= $badge[2] ?>">
                                    <?= e($badge[0]) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div style="margin-top:24px;padding:16px;background:#f9fafb;border-radius:8px;font-size:13px;color:var(--aho-color-ink-600);line-height:1.6">
            📌 <strong>Nasıl çalışır?</strong><br>
            1. Referans linkini arkadaşlarınla paylaş →
            2. Onlar bu linkle siteye gelip kayıt olsun →
            3. İlk siparişlerini yapsınlar →
            4. Otomatik olarak %<?= (int)$settings['commission_percent'] ?> komisyon kazan →
            5. Admin onayından sonra bakiyene eklenir →
            6. Bu bakiyeyi sonraki hizmet alımlarında kullan.
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
