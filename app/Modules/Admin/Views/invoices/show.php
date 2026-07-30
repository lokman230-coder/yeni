<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div><h1>🧾 Fatura #<?= e($invoice['invoice_number']) ?></h1><p>Toplam: <?= number_format((float)$invoice['total'], 2) ?> <?= e($invoice['currency']) ?></p></div>
        <div class="aho-admin-page__actions">
            <a href="/admin/faturalar" class="aho-btn aho-btn--ghost">← Liste</a>
            <a href="/admin/faturalar/<?= (int)$invoice['id'] ?>/pdf" target="_blank" class="aho-btn aho-btn--outline">📄 PDF İndir</a>
            <?php if (!in_array($invoice['status'], ['cancelled','refunded'], true)): ?>
            <form method="post" action="/admin/faturalar/<?= (int)$invoice['id'] ?>/iptal" style="display:inline" onsubmit="return confirm('İptal edilsin mi?')">
                <?= csrf() ?>
                <button class="aho-btn aho-btn--danger">✗ İptal</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($success = flash('success')): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error = flash('error')): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px">
        <div>
            <div class="aho-card">
                <div class="aho-card__header"><h3>📦 Sipariş Kalemleri</h3></div>
                <table class="aho-table">
                    <thead><tr><th>Ürün</th><th>Adet</th><th>Birim</th><th>Toplam</th></tr></thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                            <tr>
                                <td><?= e($it['product_name']) ?> <small>(<?= e($it['period']) ?>)</small><?php if ($it['domain_name']): ?> - <?= e($it['domain_name']) ?><?php endif; ?></td>
                                <td><?= (int)$it['quantity'] ?></td>
                                <td><?= number_format((float)$it['unit_price'], 2) ?></td>
                                <td><strong><?= number_format((float)$it['line_total'], 2) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$items): ?><tr><td colspan="4" style="text-align:center;color:#6b7280;padding:12px">Kalem yok</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="aho-card" style="margin-top:16px">
                <div class="aho-card__header"><h3>💳 Ödemeler</h3></div>
                <table class="aho-table">
                    <thead><tr><th>Yöntem</th><th>Tutar</th><th>Durum</th><th>TX ID</th><th>Tarih</th></tr></thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td><?= e($p['method']) ?></td>
                                <td><?= number_format((float)$p['amount'], 2) ?></td>
                                <td><span class="aho-badge"><?= e($p['status']) ?></span></td>
                                <td><code style="font-size:11px"><?= e($p['gateway_transaction_id'] ?: '—') ?></code></td>
                                <td><?= e(date('d.m.Y H:i', strtotime((string)$p['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$payments): ?><tr><td colspan="5" style="text-align:center;color:#6b7280;padding:12px">Ödeme yok</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!in_array($invoice['status'], ['paid','cancelled','refunded'], true)): ?>
            <div class="aho-card" style="margin-top:16px;background:#f0f9ff;border-left:4px solid #0ea5e9">
                <div class="aho-card__header"><h3>➕ Manuel Ödeme Kaydet (Havale onayı vb.)</h3></div>
                <div class="aho-card__body">
                    <form method="post" action="/admin/faturalar/<?= (int)$invoice['id'] ?>/odeme-kaydet">
                        <?= csrf() ?>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:12px">
                            <div>
                                <label>Tutar (<?= e($invoice['currency']) ?>) *</label>
                                <input type="number" step="0.01" name="amount" value="<?= (float)$invoice['balance'] ?>" required>
                            </div>
                            <div>
                                <label>Yöntem</label>
                                <select name="method">
                                    <option value="bank_transfer">Banka Havalesi / EFT</option>
                                    <option value="cash">Nakit</option>
                                    <option value="check">Çek</option>
                                    <option value="paytr">PayTR (manuel)</option>
                                    <option value="iyzico">iyzico (manuel)</option>
                                    <option value="other">Diğer</option>
                                </select>
                            </div>
                            <div>
                                <label>İşlem No (opsiyonel)</label>
                                <input type="text" name="gateway_transaction_id" placeholder="Havale referans no...">
                            </div>
                        </div>
                        <label>Notlar</label>
                        <textarea name="notes" rows="2" placeholder="Örn: 15.10.2026 tarihli havale, Garanti Bankası"></textarea>
                        <button type="submit" class="aho-btn aho-btn--primary" style="margin-top:8px">💰 Ödeme Kaydet</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div>
            <div class="aho-card">
                <div class="aho-card__header"><h3>👤 Müşteri</h3></div>
                <div class="aho-card__body">
                    <p><strong><?= e(trim(($invoice['first_name'] ?? '') . ' ' . ($invoice['last_name'] ?? '')) ?: '—') ?></strong></p>
                    <p><?= e($invoice['customer_email']) ?></p>
                    <?php if ($invoice['company']): ?><p>🏢 <?= e($invoice['company']) ?></p><?php endif; ?>
                    <?php if ($invoice['tax_id']): ?><p>Vergi: <?= e($invoice['tax_id']) ?></p><?php endif; ?>
                    <a href="/admin/musteriler/<?= (int)$invoice['customer_id'] ?>" class="aho-btn aho-btn--sm aho-btn--outline" style="margin-top:8px">Profil →</a>
                </div>
            </div>

            <div class="aho-card" style="margin-top:16px">
                <div class="aho-card__header"><h3>💰 Toplamlar</h3></div>
                <div class="aho-card__body" style="font-size:14px">
                    <div style="display:flex;justify-content:space-between"><span>Ara toplam:</span><strong><?= number_format((float)$invoice['subtotal'], 2) ?></strong></div>
                    <?php if ((float)$invoice['discount_total'] > 0): ?>
                        <div style="display:flex;justify-content:space-between;color:#059669"><span>İndirim:</span><strong>-<?= number_format((float)$invoice['discount_total'], 2) ?></strong></div>
                    <?php endif; ?>
                    <div style="display:flex;justify-content:space-between"><span>Vergi:</span><strong><?= number_format((float)$invoice['tax_total'], 2) ?></strong></div>
                    <hr>
                    <div style="display:flex;justify-content:space-between;font-size:16px"><span>Toplam:</span><strong><?= number_format((float)$invoice['total'], 2) ?></strong></div>
                    <div style="display:flex;justify-content:space-between;color:#059669"><span>Ödenen:</span><strong><?= number_format((float)$invoice['paid_total'], 2) ?></strong></div>
                    <div style="display:flex;justify-content:space-between;font-size:16px;color:<?= (float)$invoice['balance'] > 0 ? '#dc2626' : '#059669' ?>"><span>Kalan:</span><strong><?= number_format((float)$invoice['balance'], 2) ?></strong></div>
                </div>
            </div>

            <div class="aho-card" style="margin-top:16px">
                <div class="aho-card__header"><h3>📅 Tarihler</h3></div>
                <div class="aho-card__body" style="font-size:13px">
                    <p>Kesim: <?= e(date('d.m.Y', strtotime((string)$invoice['issue_date']))) ?></p>
                    <p>Vade: <?= e(date('d.m.Y', strtotime((string)$invoice['due_date']))) ?></p>
                    <?php if ($invoice['paid_at']): ?>
                        <p style="color:#059669">Ödendi: <?= e(date('d.m.Y H:i', strtotime((string)$invoice['paid_at']))) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $view->endSection(); ?>
