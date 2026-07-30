<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
$customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) ?: ($customer['email'] ?? '');
$statusLabels = ['draft' => 'Taslak', 'sent' => 'Gönderildi', 'accepted' => 'Kabul Edildi', 'declined' => 'Reddedildi', 'expired' => 'Süresi Doldu'];
$status = (string)($quote['status'] ?? 'draft');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>📄 Teklif — <?= e($quote['quote_number']) ?></h1>
            <p><?= e($quote['subject']) ?> · <span class="aho-badge"><?= e($statusLabels[$status] ?? $status) ?></span></p>
        </div>
        <div class="aho-admin-page__actions">
            <a href="/admin/musteriler/<?= (int)$customer['id'] ?>#teklif" class="aho-btn aho-btn--ghost">← Müşteri Profili</a>
            <?php if ($status === 'draft'): ?>
                <a href="/admin/teklifler/<?= (int)$quote['id'] ?>/duzenle" class="aho-btn aho-btn--outline">Düzenle</a>
                <form method="post" action="/admin/teklifler/<?= (int)$quote['id'] ?>/gonder" style="display:inline">
                    <?= csrf() ?><button class="aho-btn aho-btn--primary">✉ Gönder</button>
                </form>
            <?php endif; ?>
            <?php if (in_array($status, ['draft', 'sent'], true)): ?>
                <form method="post" action="/admin/teklifler/<?= (int)$quote['id'] ?>/kabul" style="display:inline" onsubmit="return confirm('Teklif kabul edilsin ve faturaya çevrilsin mi?')">
                    <?= csrf() ?><button class="aho-btn aho-btn--success">✓ Kabul Et → Faturala</button>
                </form>
                <form method="post" action="/admin/teklifler/<?= (int)$quote['id'] ?>/reddet" style="display:inline">
                    <?= csrf() ?><button class="aho-btn aho-btn--outline">✗ Reddedildi İşaretle</button>
                </form>
            <?php endif; ?>
            <?php if ($status === 'draft'): ?>
                <form method="post" action="/admin/teklifler/<?= (int)$quote['id'] ?>/sil" style="display:inline" onsubmit="return confirm('Teklif silinsin mi?')">
                    <?= csrf() ?><button class="aho-btn aho-btn--danger">🗑 Sil</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($success = flash('success')): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error = flash('error')): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px">
        <div class="aho-card">
            <div class="aho-card__header"><h3>🧾 Kalemler</h3></div>
            <table class="aho-table">
                <thead><tr><th>Açıklama</th><th>Adet</th><th>Birim</th><th>KDV%</th><th>Toplam</th></tr></thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?= e($it['description']) ?></td>
                        <td><?= (int)$it['quantity'] ?></td>
                        <td><?= number_format((float)$it['unit_price'], 2) ?></td>
                        <td><?= number_format((float)$it['tax_rate'], 2) ?></td>
                        <td><strong><?= number_format((float)$it['line_total'], 2) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (!empty($quote['notes'])): ?>
                <div style="padding:14px"><strong>Not:</strong> <?= nl2br(e($quote['notes'])) ?></div>
            <?php endif; ?>
        </div>
        <div>
            <div class="aho-card">
                <div class="aho-card__header"><h3>💰 Özet</h3></div>
                <div class="aho-detail-list">
                    <p><span>Müşteri</span><strong><?= e($customerName) ?></strong></p>
                    <p><span>Ara Toplam</span><strong><?= number_format((float)$quote['subtotal'], 2) ?> <?= e($quote['currency']) ?></strong></p>
                    <p><span>KDV</span><strong><?= number_format((float)$quote['tax_total'], 2) ?> <?= e($quote['currency']) ?></strong></p>
                    <p><span>Genel Toplam</span><strong><?= number_format((float)$quote['total'], 2) ?> <?= e($quote['currency']) ?></strong></p>
                    <p><span>Geçerlilik</span><strong><?= e($quote['valid_until'] ?: '—') ?></strong></p>
                    <?php if (!empty($quote['converted_invoice_id'])): ?>
                        <p><span>Fatura</span><strong><a href="/admin/faturalar/<?= (int)$quote['converted_invoice_id'] ?>">#<?= (int)$quote['converted_invoice_id'] ?></a></strong></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $view->endSection(); ?>
