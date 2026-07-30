<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
$success = flash('success');
$info    = flash('info');
$error   = flash('error');
?>
<section class="aho-pages-hero">
    <div class="aho-container"><h1>Sepetiniz</h1></div>
</section>

<section class="aho-cart-page">
    <div class="aho-container">
        <?php if ($success): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
        <?php if ($info):    ?><div class="aho-alert aho-alert--info"><?= e($info) ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

        <?php if (empty($totals['items'])): ?>
            <div class="aho-card">
                <div class="aho-empty-state" style="padding:var(--aho-space-12)">
                    <div class="aho-empty-state__icon" style="font-size:64px">🛒</div>
                    <div class="aho-empty-state__title" style="font-size:var(--aho-text-xl)">Sepetiniz Boş</div>
                    <div class="aho-empty-state__text" style="max-width:520px;margin:var(--aho-space-2) auto var(--aho-space-6)">
                        Sepetinizde ürün bulunmuyor. Hosting, domain veya site builder paketlerimizi inceleyebilirsiniz.
                    </div>
                    <div style="display:flex;gap:var(--aho-space-2);justify-content:center;flex-wrap:wrap">
                        <a href="/hosting" class="aho-btn aho-btn--primary">Hosting'e Göz At</a>
                        <a href="/domain" class="aho-btn aho-btn--outline">Domain Sorgula</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="aho-cart-layout">
                <!-- Ürünler -->
                <div>
                    <?php foreach ($totals['items'] as $item): ?>
                        <div class="aho-cart-item">
                            <div style="flex:1">
                                <div class="aho-cart-item__name"><?= e($item['product_name']) ?></div>
                                <div class="aho-cart-item__meta">
                                    Periyot: <strong><?= e($item['period_label']) ?></strong> ·
                                    Adet: <?= (int) $item['quantity'] ?>
                                    <?php if (!empty($item['domain_name'])): ?>
                                        · Domain: <strong><?= e($item['domain_name']) ?></strong>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($item['addons_parsed'])): ?>
                                    <div class="aho-cart-item__meta">
                                        Ek paketler: <?= (int) count($item['addons_parsed']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($item['options_parsed'])): ?>
                                    <div class="aho-cart-item__meta" style="margin-top:4px">
                                        <?php foreach ($item['options_parsed'] as $optRow):
                                            $d = (float)$optRow['price_delta_snapshot'];
                                        ?>
                                            <div style="font-size:13px;color:#4b5563">
                                                🎛 <?= e($optRow['label_snapshot']) ?>:
                                                <strong><?= e($optRow['value_snapshot']) ?></strong>
                                                <?php if ($d > 0): ?>
                                                    <span style="color:#059669">(+<?= number_format($d, 2) ?>)</span>
                                                <?php elseif ($d < 0): ?>
                                                    <span style="color:#dc2626">(<?= number_format($d, 2) ?>)</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div style="text-align:right">
                                <div class="aho-cart-item__price"><?= e($item['line_total_formatted']) ?></div>
                                <form method="post" action="/sepet/<?= (int)$item['id'] ?>/sil" style="margin-top:var(--aho-space-2)">
                                    <?= csrf() ?>
                                    <button class="aho-btn aho-btn--ghost aho-btn--sm" type="submit">🗑 Sil</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div style="margin-top:var(--aho-space-4);text-align:right">
                        <form method="post" action="/sepet/temizle" style="display:inline"
                              onsubmit="return confirm('Sepeti temizlemek istediğinize emin misiniz?');">
                            <?= csrf() ?>
                            <button class="aho-btn aho-btn--ghost aho-btn--sm">Sepeti Temizle</button>
                        </form>
                    </div>
                </div>

                <!-- Sağ özet -->
                <div class="aho-cart-summary">
                    <h3 style="margin-bottom:var(--aho-space-4);font-size:var(--aho-text-lg)">Sipariş Özeti</h3>

                    <div class="aho-cart-summary__row">
                        <span>Ara Toplam</span>
                        <strong><?= e($totals['formatted']['subtotal']) ?></strong>
                    </div>

                    <?php if ($totals['discount'] > 0): ?>
                        <div class="aho-cart-summary__row">
                            <span>İndirim <?php if (!empty($totals['coupon'])): ?>(<?= e($totals['coupon']['code']) ?>)<?php endif; ?></span>
                            <strong style="color:var(--aho-color-success)">-<?= e($totals['formatted']['discount']) ?></strong>
                        </div>
                    <?php endif; ?>

                    <?php if ($totals['tax'] > 0): ?>
                        <div class="aho-cart-summary__row">
                            <span>KDV (%<?= (float) $totals['tax_rate'] ?>)</span>
                            <strong><?= e($totals['formatted']['tax']) ?></strong>
                        </div>
                    <?php endif; ?>

                    <div class="aho-cart-summary__row aho-cart-summary__row--total">
                        <span>Toplam</span>
                        <strong><?= e($totals['formatted']['total']) ?></strong>
                    </div>

                    <!-- Kupon -->
                    <form method="post" action="/sepet/kupon-uygula" style="margin-top:var(--aho-space-4)">
                        <?= csrf() ?>
                        <div class="aho-form-group">
                            <label class="aho-form-label" style="font-size:var(--aho-text-xs)">Kupon Kodu</label>
                            <div style="display:flex;gap:var(--aho-space-2)">
                                <input type="text" name="coupon_code" class="aho-form-input"
                                       value="<?= e($totals['coupon_code'] ?? '') ?>"
                                       placeholder="Örn: WELCOME10" style="flex:1">
                                <button class="aho-btn aho-btn--outline aho-btn--sm">Uygula</button>
                            </div>
                        </div>
                    </form>

                    <a href="/odeme" class="aho-btn aho-btn--primary aho-btn--lg aho-btn--block" style="margin-top:var(--aho-space-4)">
                        Ödemeye Geç →
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php $view->endSection(); ?>
