<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-customer-panel" style="padding:32px 0;background:#f9fafb;min-height:calc(100vh - 200px)">
    <div class="aho-container">
        <div style="display:grid;grid-template-columns:220px 1fr;gap:24px" class="aho-customer-layout">
            <?= $view->include('customer::_sidebar') ?>
            <div>
                <div style="margin-bottom:24px">
                    <h1 style="margin:0 0 8px;font-size:24px">💳 Bakiyem</h1>
                    <p style="color:#6b7280;margin:0">Hesap bakiyeni gör, yükleme yap veya kullanım geçmişini incele.</p>
                </div>

                <?php if ($success = flash('success')): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
                <?php if ($error = flash('error')): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

                <!-- Bakiye Kart -->
                <div class="aho-card" style="padding:28px;margin-bottom:24px;background:linear-gradient(135deg,#0ea5e9,#0891b2);color:#fff">
                    <div style="font-size:13px;opacity:.85;text-transform:uppercase;letter-spacing:1px">Mevcut Bakiye</div>
                    <div style="font-size:42px;font-weight:800;margin-top:6px"><?= number_format((float)$balance, 2) ?> TRY</div>
                    <div style="font-size:13px;opacity:.85;margin-top:4px">
                        Bakiye ile fatura ödeyebilir, otomatik yenilemelerde kullanabilirsin.
                    </div>
                </div>

                <!-- Bakiye Yükleme -->
                <div class="aho-card" style="padding:24px;margin-bottom:24px">
                    <h3 style="margin:0 0 16px">➕ Bakiye Yükle</h3>
                    <form method="post" action="/panel/bakiye/yukle">
                        <?= csrf() ?>
                        <label style="display:block;font-weight:600;margin-bottom:8px">Yüklenecek Tutar (TRY)</label>
                        <input type="number" name="amount" min="10" max="50000" step="10" required placeholder="100" style="width:100%;padding:12px;font-size:16px;border:1px solid #d1d5db;border-radius:8px;margin-bottom:12px">

                        <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
                            <?php foreach ([50, 100, 250, 500, 1000] as $preset): ?>
                                <button type="button" onclick="document.querySelector('[name=amount]').value=<?= $preset ?>" class="aho-btn aho-btn--sm aho-btn--outline"><?= $preset ?> ₺</button>
                            <?php endforeach; ?>
                        </div>

                        <label style="display:block;font-weight:600;margin-bottom:8px">Ödeme Yöntemi</label>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                            <label style="border:2px solid #e5e7eb;padding:14px;border-radius:8px;cursor:pointer;display:flex;align-items:center;gap:10px">
                                <input type="radio" name="method" value="paytr" checked>
                                <span>💳 Kredi Kartı (PayTR)</span>
                            </label>
                            <label style="border:2px solid #e5e7eb;padding:14px;border-radius:8px;cursor:pointer;display:flex;align-items:center;gap:10px">
                                <input type="radio" name="method" value="bank_transfer">
                                <span>🏦 Havale / EFT</span>
                            </label>
                        </div>

                        <button type="submit" class="aho-btn aho-btn--primary" style="width:100%;margin-top:16px;padding:14px;font-size:16px">
                            💰 Bakiye Yükle
                        </button>
                    </form>
                </div>

                <!-- Bakiye Hareketleri -->
                <div class="aho-card" style="padding:0;overflow:hidden">
                    <div style="padding:16px 24px;border-bottom:1px solid #e5e7eb"><h3 style="margin:0">📋 Hareket Geçmişi</h3></div>
                    <table style="width:100%;font-size:14px">
                        <thead style="background:#f9fafb;text-align:left">
                            <tr><th style="padding:12px 24px">Tarih</th><th style="padding:12px">Tutar</th><th style="padding:12px">Bakiye</th><th style="padding:12px">Kaynak</th><th style="padding:12px 24px">Açıklama</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($credits as $c):
                                $isCredit = (float)$c['amount'] >= 0;
                                $sourceLabel = match ($c['source']) {
                                    'admin_manual' => '👤 Admin',
                                    'payment'      => '💳 Ödeme',
                                    'invoice_pay'  => '🧾 Fatura',
                                    'refund'       => '↩ İade',
                                    'promo'        => '🎁 Kampanya',
                                    default        => $c['source'],
                                };
                            ?>
                                <tr style="border-top:1px solid #f3f4f6">
                                    <td style="padding:14px 24px"><?= e(date('d.m.Y H:i', strtotime((string)$c['created_at']))) ?></td>
                                    <td style="padding:14px;font-weight:700;color:<?= $isCredit ? '#059669' : '#dc2626' ?>">
                                        <?= ($isCredit ? '+' : '') . number_format((float)$c['amount'], 2) ?> TL
                                    </td>
                                    <td style="padding:14px"><?= number_format((float)$c['balance_after'], 2) ?></td>
                                    <td style="padding:14px"><?= e($sourceLabel) ?></td>
                                    <td style="padding:14px 24px;color:#6b7280;font-size:13px"><?= e($c['description'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$credits): ?><tr><td colspan="5" style="text-align:center;padding:24px;color:#6b7280">Henüz hareket yok.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
