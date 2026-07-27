<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
$balanceEnough = $balance >= (float)$invoice['balance'];
?>
<section style="padding:40px 0;background:#f9fafb;min-height:calc(100vh - 200px)">
    <div class="aho-container" style="max-width:640px">
        <div class="aho-card" style="padding:32px">
            <div style="text-align:center;margin-bottom:24px">
                <div style="font-size:14px;color:#6b7280;text-transform:uppercase;letter-spacing:1px">Fatura Ödeme</div>
                <h1 style="margin:6px 0;font-size:24px">#<?= e($invoice['invoice_number']) ?></h1>
                <div style="font-size:32px;font-weight:800;color:#0ea5e9">
                    <?= number_format((float)$invoice['balance'], 2) ?> <?= e($invoice['currency']) ?>
                </div>
                <?php if ($invoice['due_date']): ?>
                    <div style="font-size:13px;color:#6b7280;margin-top:4px">Vade: <?= e(date('d.m.Y', strtotime((string)$invoice['due_date']))) ?></div>
                <?php endif; ?>
            </div>

            <form method="post" action="/odeme/<?= (int)$invoice['id'] ?>/tamamla">
                <?= csrf() ?>
                <h3 style="margin:0 0 12px;font-size:16px">Ödeme Yöntemi Seç</h3>

                <div style="display:flex;flex-direction:column;gap:10px">
                    <!-- BAKIYE -->
                    <label style="border:2px solid <?= $balanceEnough ? '#0ea5e9' : '#e5e7eb' ?>;padding:14px 16px;border-radius:10px;cursor:<?= $balanceEnough ? 'pointer' : 'not-allowed' ?>;display:flex;gap:12px;align-items:center;opacity:<?= $balanceEnough ? 1 : 0.5 ?>">
                        <input type="radio" name="method" value="balance" <?= $balanceEnough ? 'checked' : 'disabled' ?>>
                        <div style="flex:1">
                            <div style="font-weight:600">💰 Bakiye ile Öde</div>
                            <div style="font-size:12px;color:#6b7280;margin-top:2px">
                                Mevcut bakiye: <strong><?= number_format($balance, 2) ?> TRY</strong>
                                <?php if (!$balanceEnough): ?>
                                    <span style="color:#dc2626">— Yetersiz. <a href="/panel/bakiye">Bakiye yükle</a></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </label>

                    <!-- KREDİ KARTI -->
                    <?php foreach ($gateways as $gw): ?>
                        <label style="border:2px solid #e5e7eb;padding:14px 16px;border-radius:10px;cursor:pointer;display:flex;gap:12px;align-items:center">
                            <input type="radio" name="method" value="<?= e($gw['id']) ?>">
                            <div style="flex:1">
                                <div style="font-weight:600">💳 <?= e($gw['label'] ?? ucfirst($gw['id'])) ?></div>
                                <div style="font-size:12px;color:#6b7280;margin-top:2px">Kredi kartı / banka kartı ile güvenli ödeme</div>
                            </div>
                        </label>
                    <?php endforeach; ?>

                    <!-- HAVALE -->
                    <label style="border:2px solid #e5e7eb;padding:14px 16px;border-radius:10px;cursor:pointer;display:flex;gap:12px;align-items:center">
                        <input type="radio" name="method" value="bank_transfer">
                        <div style="flex:1">
                            <div style="font-weight:600">🏦 Banka Havalesi / EFT</div>
                            <div style="font-size:12px;color:#6b7280;margin-top:2px">IBAN bilgileri gösterilir, havale sonrası admin onaylar (1-4 saat)</div>
                        </div>
                    </label>
                </div>

                <div style="margin-top:20px;display:flex;gap:8px;justify-content:space-between">
                    <a href="/panel/faturalarim" class="aho-btn aho-btn--ghost">← İptal</a>
                    <button type="submit" class="aho-btn aho-btn--primary aho-btn--lg">
                        Ödemeyi Tamamla — <?= number_format((float)$invoice['balance'], 2) ?> <?= e($invoice['currency']) ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
