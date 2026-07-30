<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-pages-hero">
    <div class="aho-container">
        <h1><?= e($product['name']) ?></h1>
        <?php if (!empty($product['short_description'])): ?>
            <p style="color:var(--aho-color-ink-500);margin-top:var(--aho-space-2);font-size:var(--aho-text-lg)">
                <?= e($product['short_description']) ?>
            </p>
        <?php endif; ?>
    </div>
</section>

<section class="aho-pages-body">
    <div class="aho-container" style="max-width:900px">
        <div class="aho-card">
            <?php if (!empty($product['description'])): ?>
                <div class="aho-prose" style="margin-bottom:var(--aho-space-6)">
                    <?= $view->raw($product['description']) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($prices)): ?>
                <div class="aho-alert aho-alert--info">Bu ürün için fiyat henüz tanımlanmamış.</div>
            <?php else: ?>
                <h3 style="margin-bottom:var(--aho-space-3)">Periyot Seçin</h3>
                <form method="post" action="/sepet/ekle">
                    <?= csrf() ?>
                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                    <div style="display:grid;gap:var(--aho-space-2);margin-bottom:var(--aho-space-4)">
                        <?php foreach ($prices as $i => $pr): ?>
                            <label class="aho-payment-method <?= $i === 0 ? 'is-selected' : '' ?>">
                                <input type="radio" name="period" value="<?= e($pr['period']) ?>" <?= $i === 0 ? 'checked' : '' ?>>
                                <div style="flex:1">
                                    <div style="font-weight:600"><?= e($pr['period_label']) ?></div>
                                </div>
                                <div style="font-weight:700;color:var(--aho-color-primary-700)"><?= e($pr['formatted']) ?></div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <?php $productOptions = $productOptions ?? []; ?>
                    <?php if ($productOptions): ?>
                        <h3 style="margin-bottom:var(--aho-space-3)">🎛 Paket Seçenekleri</h3>
                        <div class="aho-product-options" style="display:grid;gap:var(--aho-space-3);margin-bottom:var(--aho-space-4)">
                            <?php foreach ($productOptions as $opt):
                                $defaultId = null;
                                foreach ($opt['values'] as $v) { if ((int)$v['is_default'] === 1) { $defaultId = (int)$v['id']; break; } }
                                if ($defaultId === null && $opt['values']) { $defaultId = (int)$opt['values'][0]['id']; }
                            ?>
                                <div class="aho-form-group" style="background:#f9fafb;padding:12px;border-radius:8px;border:1px solid #e5e7eb">
                                    <label class="aho-form-label" style="font-weight:600">
                                        <?= e($opt['name']) ?>
                                        <?php if ((int)$opt['is_required'] === 1): ?><span style="color:#dc2626">*</span><?php endif; ?>
                                    </label>
                                    <?php if (!empty($opt['description'])): ?>
                                        <p style="color:#6b7280;font-size:13px;margin:4px 0 8px"><?= e($opt['description']) ?></p>
                                    <?php endif; ?>
                                    <?php if ($opt['input_type'] === 'radio'): ?>
                                        <?php foreach ($opt['values'] as $v):
                                            $delta = (float)$v['price_delta'];
                                            $deltaLabel = $delta > 0 ? '(+' . number_format($delta, 2) . ' ' . $v['currency'] . ')' : ($delta < 0 ? '(' . number_format($delta, 2) . ' ' . $v['currency'] . ')' : '');
                                        ?>
                                            <label style="display:flex;gap:8px;align-items:center;padding:6px 0;cursor:pointer">
                                                <input type="radio" name="options[<?= (int)$opt['id'] ?>]" value="<?= (int)$v['id'] ?>" <?= (int)$v['id'] === $defaultId ? 'checked' : '' ?>>
                                                <span><?= e($v['label']) ?></span>
                                                <?php if ($deltaLabel): ?><span style="color:#059669;font-weight:600;margin-left:auto"><?= $deltaLabel ?></span><?php endif; ?>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php elseif ($opt['input_type'] === 'checkbox'): ?>
                                        <?php foreach ($opt['values'] as $v):
                                            $delta = (float)$v['price_delta'];
                                            $deltaLabel = $delta > 0 ? '(+' . number_format($delta, 2) . ' ' . $v['currency'] . ')' : '';
                                        ?>
                                            <label style="display:flex;gap:8px;align-items:center;padding:6px 0;cursor:pointer">
                                                <input type="checkbox" name="options[<?= (int)$opt['id'] ?>][]" value="<?= (int)$v['id'] ?>" <?= !empty($v['is_default']) ? 'checked' : '' ?>>
                                                <span><?= e($v['label']) ?></span>
                                                <?php if ($deltaLabel): ?><span style="color:#059669;font-weight:600;margin-left:auto"><?= $deltaLabel ?></span><?php endif; ?>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <select name="options[<?= (int)$opt['id'] ?>]" class="aho-form-input" <?= (int)$opt['is_required'] === 1 ? 'required' : '' ?>>
                                            <?php if ((int)$opt['is_required'] !== 1): ?>
                                                <option value="">— Seçiniz —</option>
                                            <?php endif; ?>
                                            <?php foreach ($opt['values'] as $v):
                                                $delta = (float)$v['price_delta'];
                                                $deltaLabel = $delta > 0 ? ' (+' . number_format($delta, 2) . ' ' . $v['currency'] . ')' : ($delta < 0 ? ' (' . number_format($delta, 2) . ' ' . $v['currency'] . ')' : '');
                                            ?>
                                                <option value="<?= (int)$v['id'] ?>" <?= (int)$v['id'] === $defaultId ? 'selected' : '' ?>>
                                                    <?= e($v['label']) . $deltaLabel ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="aho-btn aho-btn--primary aho-btn--lg aho-btn--block">
                        🛒 Sepete Ekle
                    </button>
                </form>
                <script>
                document.querySelectorAll('input[name="period"]').forEach(r => r.addEventListener('change', () => {
                    document.querySelectorAll('.aho-payment-method').forEach(l => l.classList.remove('is-selected'));
                    r.closest('.aho-payment-method').classList.add('is-selected');
                }));
                </script>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
