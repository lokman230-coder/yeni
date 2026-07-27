<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
$error = flash('error');
?>
<section class="aho-pages-hero">
    <div class="aho-container"><h1>Ödeme</h1></div>
</section>

<section class="aho-checkout">
    <div class="aho-container">
        <div class="aho-checkout-steps">
            <span class="aho-checkout-step is-done">1. Sepet</span>
            <span class="aho-checkout-step is-active">2. Ödeme</span>
            <span class="aho-checkout-step">3. Sipariş Tamamlandı</span>
        </div>

        <?php if ($error): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

        <div class="aho-cart-layout">
            <div>
                <!-- Müşteri bilgileri -->
                <div class="aho-card" style="margin-bottom:var(--aho-space-4)">
                    <h3 class="aho-card__title" style="margin-bottom:var(--aho-space-3)">Fatura Bilgileri</h3>
                    <div style="color:var(--aho-color-ink-700);font-size:var(--aho-text-sm);line-height:1.7">
                        <div><strong><?= e(trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''))) ?></strong></div>
                        <div><?= e($customer['email']) ?></div>
                        <?php if (!empty($customer['phone'])): ?><div><?= e($customer['phone']) ?></div><?php endif; ?>
                        <?php if (!empty($customer['company'])): ?><div><?= e($customer['company']) ?></div><?php endif; ?>
                        <?php if (!empty($customer['address'])): ?><div><?= e($customer['address']) ?></div><?php endif; ?>
                    </div>
                    <a href="/panel" class="aho-btn aho-btn--ghost aho-btn--sm" style="margin-top:var(--aho-space-3)">Bilgileri Düzenle</a>
                </div>

                <!-- Ödeme yöntemi -->
                <form method="post" action="/odeme/tamamla" id="ahoCheckoutForm">
                    <?= csrf() ?>
                    <div class="aho-card">
                        <h3 class="aho-card__title" style="margin-bottom:var(--aho-space-4)">Ödeme Yöntemi</h3>

                        <?php
                        // Gateway → ikon/açıklama eşleştirmesi
                        $gatewayMeta = [
                            'paytr'   => ['icon' => '💳', 'desc' => 'Visa, Mastercard, tek çekim / taksit'],
                            'iyzico'  => ['icon' => '💠', 'desc' => 'Tüm bankalar, 2-12 taksit, 3D Secure'],
                            'papara'  => ['icon' => '🟨', 'desc' => 'Papara cüzdanı ile hızlı ödeme'],
                            'shopier' => ['icon' => '🛒', 'desc' => 'Shopier güvenli ödeme'],
                        ];
                        $available = $gateways ?? [];
                        // Hiçbir gateway aktif değilse en azından PayTR gözüksün (test amaçlı)
                        if (empty($available)) {
                            $available = [['id' => 'paytr', 'label' => 'PayTR Kredi Kartı']];
                        }
                        $first = true;
                        ?>
                        <div class="aho-payment-methods">
                        <?php foreach ($available as $gw):
                            $meta = $gatewayMeta[$gw['id']] ?? ['icon' => '💳', 'desc' => ''];
                        ?>
                            <label class="aho-payment-method<?= $first ? ' is-selected' : '' ?>">
                                <input type="radio" name="payment_method" value="<?= e($gw['id']) ?>" <?= $first ? 'checked' : '' ?>>
                                <div>
                                    <div style="font-weight:600"><?= $meta['icon'] ?> <?= e($gw['label']) ?></div>
                                    <?php if ($meta['desc']): ?>
                                    <div style="font-size:var(--aho-text-xs);color:var(--aho-color-ink-500);margin-top:2px">
                                        <?= e($meta['desc']) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </label>
                        <?php $first = false; endforeach; ?>

                            <label class="aho-payment-method">
                                <input type="radio" name="payment_method" value="bank_transfer">
                                <div>
                                    <div style="font-weight:600">🏦 Banka Havalesi / EFT</div>
                                    <div style="font-size:var(--aho-text-xs);color:var(--aho-color-ink-500);margin-top:2px">
                                        Sipariş sonrası banka bilgileri e-posta ile gönderilir
                                    </div>
                                </div>
                            </label>
                            <label class="aho-payment-method">
                                <input type="radio" name="payment_method" value="balance">
                                <div>
                                    <div style="font-weight:600">💰 Bakiye ile Öde</div>
                                    <div style="font-size:var(--aho-text-xs);color:var(--aho-color-ink-500);margin-top:2px">
                                        Mevcut bakiye: <strong><?= e(number_format((float)($customer['balance'] ?? 0), 2, ',', '.')) ?> ₺</strong>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <div style="margin-top:var(--aho-space-6);display:flex;justify-content:space-between;align-items:center;gap:var(--aho-space-3);flex-wrap:wrap">
                            <a href="/sepet" class="aho-btn aho-btn--ghost">← Sepete Dön</a>
                            <button type="submit" class="aho-btn aho-btn--primary aho-btn--lg">
                                Siparişi Onayla — <?= e($totals['formatted']['total']) ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Sağ özet -->
            <div class="aho-cart-summary">
                <h3 style="margin-bottom:var(--aho-space-4);font-size:var(--aho-text-lg)">Sipariş Özeti</h3>
                <?php foreach ($totals['items'] as $it): ?>
                    <div style="padding-bottom:var(--aho-space-2);margin-bottom:var(--aho-space-2);border-bottom:1px solid var(--aho-color-border);font-size:var(--aho-text-sm)">
                        <div style="display:flex;justify-content:space-between;gap:var(--aho-space-2)">
                            <span><?= e($it['product_name']) ?> <span style="color:var(--aho-color-ink-500)">(<?= e($it['period_label']) ?>)</span></span>
                            <strong><?= e($it['line_total_formatted']) ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="aho-cart-summary__row"><span>Ara Toplam</span><strong><?= e($totals['formatted']['subtotal']) ?></strong></div>
                <?php if ($totals['discount'] > 0): ?>
                    <div class="aho-cart-summary__row"><span>İndirim</span><strong style="color:var(--aho-color-success)">-<?= e($totals['formatted']['discount']) ?></strong></div>
                <?php endif; ?>
                <?php if ($totals['tax'] > 0): ?>
                    <div class="aho-cart-summary__row"><span>KDV (%<?= (float)$totals['tax_rate'] ?>)</span><strong><?= e($totals['formatted']['tax']) ?></strong></div>
                <?php endif; ?>
                <div class="aho-cart-summary__row aho-cart-summary__row--total">
                    <span>Toplam</span><strong><?= e($totals['formatted']['total']) ?></strong>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.querySelectorAll('.aho-payment-method input[type="radio"]').forEach(r => {
    r.addEventListener('change', () => {
        document.querySelectorAll('.aho-payment-method').forEach(l => l.classList.remove('is-selected'));
        r.closest('.aho-payment-method').classList.add('is-selected');
    });
});
</script>
<?php $view->endSection(); ?>
