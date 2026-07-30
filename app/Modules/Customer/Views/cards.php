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
                    <h1 style="margin:0 0 6px;font-size:24px">💳 Saklanan Kartlarım</h1>
                    <p style="color:#6b7280;margin:0">Kayıtlı kartlarını yönet, otomatik yenilemeyi aç/kapat.</p>
                </div>

                <?php if ($success = flash('success')): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
                <?php if ($error = flash('error')): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

                <div class="aho-card" style="padding:18px;margin-bottom:16px;background:#f0f9ff;border-left:4px solid #0ea5e9">
                    <strong>🔒 PCI-DSS Uyumlu Güvenlik:</strong> Kart numaranız <strong>hiçbir zaman</strong> bizim sunucumuzda saklanmaz.
                    Yalnızca ödeme sağlayıcının verdiği kart token'ı (referans) saklanır. Bu token ile CVV'siz otomatik tahsilat yapabilirsiniz.
                </div>

                <?php if (!$cards): ?>
                    <div class="aho-card" style="padding:60px;text-align:center">
                        <div style="font-size:56px">💳</div>
                        <h3 style="margin:12px 0 8px">Kayıtlı kart yok</h3>
                        <p style="color:#6b7280;margin:0 0 20px">İlk kart eklemek için bir fatura ödemesi sırasında "Bu kartı sakla" seçeneğini işaretleyin.</p>
                    </div>
                <?php else: ?>
                    <div style="display:grid;gap:12px">
                        <?php foreach ($cards as $card): ?>
                            <div class="aho-card" style="padding:20px;<?= (int)$card['is_default'] ? 'border-left:4px solid #0ea5e9;' : '' ?>">
                                <div style="display:flex;justify-content:space-between;align-items:flex-start">
                                    <div style="display:flex;gap:14px;align-items:center">
                                        <div style="font-size:36px"><?= match(strtolower($card['card_brand'] ?? '')) {'visa'=>'💳','mastercard'=>'💳','troy'=>'🇹🇷',default=>'💳'} ?></div>
                                        <div>
                                            <div style="font-weight:700;font-size:16px">
                                                <?= e($card['card_brand'] ?? '—') ?> **** <?= e($card['card_last4']) ?>
                                                <?php if ((int)$card['is_default']): ?>
                                                    <span style="background:#0ea5e9;color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;margin-left:6px">VARSAYILAN</span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="font-size:13px;color:#6b7280;margin-top:2px">
                                                <?= e($card['card_holder'] ?? '') ?> · <?= (int)($card['exp_month'] ?? 0) ?>/<?= (int)($card['exp_year'] ?? 0) ?>
                                                · Gateway: <?= e($card['gateway']) ?>
                                            </div>
                                            <?php if ($card['nickname']): ?><div style="font-size:12px;color:#0891b2;margin-top:2px">📝 <?= e($card['nickname']) ?></div><?php endif; ?>
                                        </div>
                                    </div>
                                    <div style="display:flex;gap:6px;flex-direction:column">
                                        <form method="post" action="/panel/kartlar/<?= (int)$card['id'] ?>/otomatik-tahsilat">
                                            <?= csrf() ?>
                                            <label style="display:flex;gap:6px;align-items:center;font-size:13px;cursor:pointer">
                                                <input type="checkbox" name="enabled" value="1" <?= (int)$card['auto_billing_enabled'] ? 'checked' : '' ?> onchange="this.form.submit()">
                                                <span>Otomatik tahsilat</span>
                                            </label>
                                        </form>
                                        <?php if (!(int)$card['is_default']): ?>
                                            <form method="post" action="/panel/kartlar/<?= (int)$card['id'] ?>/varsayilan">
                                                <?= csrf() ?>
                                                <button class="aho-btn aho-btn--sm aho-btn--outline">✓ Varsayılan Yap</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" action="/panel/kartlar/<?= (int)$card['id'] ?>/sil" onsubmit="return confirm('Kart silinsin mi?')">
                                            <?= csrf() ?>
                                            <button class="aho-btn aho-btn--sm aho-btn--danger">🗑 Sil</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
