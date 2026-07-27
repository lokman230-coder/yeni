<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
$success = flash('success');
$error   = flash('error');
$fullName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>👤 <?= e($fullName ?: $customer['email']) ?></h1>
            <p><?= e($customer['email']) ?> · #<?= (int)$customer['id'] ?></p>
        </div>
        <div class="aho-admin-page__actions">
            <a href="/admin/musteriler" class="aho-btn aho-btn--ghost">← Liste</a>
            <a href="/admin/musteriler/<?= (int)$customer['id'] ?>/duzenle" class="aho-btn aho-btn--outline">✏️ Düzenle</a>
            <form method="post" action="/admin/musteriler/<?= (int)$customer['id'] ?>/adina-giris" style="display:inline"
                  onsubmit="return confirm('Bu müşterinin paneline geçmek istiyor musun? Aktivite loglanacak.')">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <button class="aho-btn aho-btn--warning">🔐 Adına Giriş Yap</button>
            </form>
        </div>
    </div>

    <?php if ($success): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 2fr;gap:16px">
        <div class="aho-card">
            <div class="aho-card__header"><h3>Bilgiler</h3></div>
            <div class="aho-card__body">
                <p><strong>Telefon:</strong> <?= e($customer['phone'] ?? '—') ?></p>
                <p><strong>Firma:</strong> <?= e($customer['company'] ?? '—') ?></p>
                <p><strong>Durum:</strong> <?= e($customer['status'] ?? 'pending') ?></p>
                <p><strong>E-posta doğrulama:</strong> <?= !empty($customer['email_verified_at']) ? '✓' : '—' ?></p>
                <p><strong>Kayıt:</strong> <?= e($customer['created_at']) ?></p>
                <p><strong>Son giriş:</strong> <?= e($customer['last_login_at'] ?? '—') ?></p>
                <p><strong>Bakiye:</strong> <strong style="color:<?= (float)($customer['balance'] ?? 0) < 0 ? '#dc2626' : '#059669' ?>;font-size:16px"><?= number_format((float)($customer['balance'] ?? 0), 2) ?> TRY</strong></p>
                <button type="button" onclick="ahoOpenCreditModal()" class="aho-btn aho-btn--sm aho-btn--primary" style="width:100%;margin-top:8px">
                    💰 Bakiye Ekle / Çıkar
                </button>
            </div>
        </div>

        <!-- Bakiye modal -->
        <div id="ahoCreditModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;padding:20px">
            <div style="background:#fff;border-radius:12px;max-width:500px;width:100%;padding:24px">
                <h3 style="margin:0 0 16px">💰 Bakiye Hareketi</h3>
                <form method="post" action="/admin/musteriler/<?= (int)$customer['id'] ?>/bakiye-ekle">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
                        <label>
                            <input type="radio" name="direction" value="add" checked> 💵 Yükle (+)
                        </label>
                        <label>
                            <input type="radio" name="direction" value="deduct"> ➖ Düş (-)
                        </label>
                    </div>
                    <label>Tutar (TRY) *</label>
                    <input type="number" step="0.01" name="amount" required placeholder="100.00" style="width:100%;padding:10px;margin-bottom:12px;border:1px solid #d1d5db;border-radius:6px">

                    <label>Kaynak</label>
                    <select name="source" style="width:100%;padding:10px;margin-bottom:12px;border:1px solid #d1d5db;border-radius:6px">
                        <option value="admin_manual">Admin manuel</option>
                        <option value="payment">Ödeme (havale/kart)</option>
                        <option value="promo">Kampanya/hediye</option>
                        <option value="refund">İade</option>
                    </select>

                    <label>Açıklama (opsiyonel)</label>
                    <textarea name="description" rows="2" placeholder="Örn: 15.10.2026 havale — HSBC" style="width:100%;padding:10px;margin-bottom:16px;border:1px solid #d1d5db;border-radius:6px"></textarea>

                    <div style="display:flex;gap:8px;justify-content:flex-end">
                        <button type="button" onclick="document.getElementById('ahoCreditModal').style.display='none'" class="aho-btn aho-btn--ghost">İptal</button>
                        <button type="submit" class="aho-btn aho-btn--primary">Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
        function ahoOpenCreditModal() { document.getElementById('ahoCreditModal').style.display = 'flex'; }
        </script>

        <?php if (!empty($customer['admin_notes'])): ?>
        <div class="aho-card" style="margin-top:16px;background:#fffbeb;border-left:4px solid #f59e0b">
            <div class="aho-card__header"><h3>📝 Admin Notları</h3></div>
            <div class="aho-card__body" style="white-space:pre-wrap;font-size:14px;color:#78350f">
                <?= e($customer['admin_notes']) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Bakiye hareketleri -->
        <?php
        $credits = \App\Services\Credit\CreditService::history((int)$customer['id'], 15);
        if ($credits): ?>
        <div class="aho-card" style="margin-top:16px">
            <div class="aho-card__header"><h3>💳 Bakiye Hareketleri (son 15)</h3></div>
            <table class="aho-table">
                <thead><tr><th>Tarih</th><th>Tutar</th><th>Bakiye</th><th>Kaynak</th><th>Açıklama</th></tr></thead>
                <tbody>
                    <?php foreach ($credits as $cr):
                        $isCredit = (float)$cr['amount'] >= 0;
                    ?>
                        <tr>
                            <td><?= e(date('d.m.Y H:i', strtotime((string)$cr['created_at']))) ?></td>
                            <td style="color:<?= $isCredit ? '#059669' : '#dc2626' ?>;font-weight:600">
                                <?= ($isCredit ? '+' : '') . number_format((float)$cr['amount'], 2) ?> TRY
                            </td>
                            <td><?= number_format((float)$cr['balance_after'], 2) ?></td>
                            <td><span class="aho-badge"><?= e($cr['source']) ?></span></td>
                            <td style="font-size:13px;color:#6b7280"><?= e($cr['description'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div>
            <div class="aho-card" style="margin-bottom:16px">
                <div class="aho-card__header"><h3>Son Siparişler (<?= count($orders) ?>)</h3></div>
                <table class="aho-table">
                    <thead><tr><th>#</th><th>Toplam</th><th>Durum</th><th>Tarih</th></tr></thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td>#<?= (int)$o['id'] ?></td>
                                <td><?= number_format((float)$o['total'], 2) ?> <?= e($o['currency'] ?? 'TRY') ?></td>
                                <td><span class="aho-badge"><?= e($o['status']) ?></span></td>
                                <td><?= e(substr((string)$o['created_at'], 0, 16)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$orders): ?><tr><td colspan="4" style="text-align:center;color:var(--aho-muted);padding:12px">Sipariş yok.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="aho-card" style="margin-bottom:16px">
                <div class="aho-card__header"><h3>Faturalar (<?= count($invoices) ?>)</h3></div>
                <table class="aho-table">
                    <thead><tr><th>#</th><th>Toplam</th><th>Durum</th><th>Vade</th></tr></thead>
                    <tbody>
                        <?php foreach ($invoices as $inv): ?>
                            <tr>
                                <td>#<?= (int)$inv['id'] ?></td>
                                <td><?= number_format((float)$inv['total'], 2) ?> <?= e($inv['currency'] ?? 'TRY') ?></td>
                                <td><span class="aho-badge"><?= e($inv['status']) ?></span></td>
                                <td><?= e(substr((string)($inv['due_date'] ?? ''), 0, 10)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$invoices): ?><tr><td colspan="4" style="text-align:center;color:var(--aho-muted);padding:12px">Fatura yok.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="aho-card">
                <div class="aho-card__header"><h3>Destek Talepleri (<?= count($tickets) ?>)</h3></div>
                <table class="aho-table">
                    <thead><tr><th>#</th><th>Konu</th><th>Durum</th><th>Tarih</th></tr></thead>
                    <tbody>
                        <?php foreach ($tickets as $t): ?>
                            <tr>
                                <td>#<?= (int)$t['id'] ?></td>
                                <td><?= e($t['subject']) ?></td>
                                <td><span class="aho-badge"><?= e($t['status']) ?></span></td>
                                <td><?= e(substr((string)$t['created_at'], 0, 16)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$tickets): ?><tr><td colspan="4" style="text-align:center;color:var(--aho-muted);padding:12px">Talep yok.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $view->endSection(); ?>
