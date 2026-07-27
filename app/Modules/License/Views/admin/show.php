<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>🔑 <?= e($license['product_name']) ?></h1>
            <p style="font-family:monospace;font-size:16px"><?= e($license['license_key']) ?>
                <button type="button" onclick="navigator.clipboard.writeText('<?= e($license['license_key']) ?>');this.textContent='✓'" class="aho-btn aho-btn--sm">📋 Kopyala</button>
            </p>
        </div>
        <div class="aho-admin-page__actions">
            <a href="/admin/lisanslar" class="aho-btn aho-btn--ghost">← Liste</a>
            <?php if ($license['status'] !== 'revoked'): ?>
                <form method="post" action="/admin/lisanslar/<?= (int)$license['id'] ?>/iptal" style="display:inline" onsubmit="return confirm('İptal edilsin mi? Geri alınamaz.')">
                    <?= csrf() ?>
                    <button class="aho-btn aho-btn--danger">✗ İptal Et</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($success = flash('success')): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 2fr;gap:16px">
        <div class="aho-card">
            <div class="aho-card__header"><h3>📋 Bilgiler</h3></div>
            <div class="aho-card__body" style="font-size:13px">
                <p><strong>Müşteri:</strong> <?= e($license['customer_email'] ?? '—') ?></p>
                <p><strong>Tip:</strong> <?= e($license['license_type']) ?></p>
                <p><strong>Max:</strong> <?= $license['license_type'] === 'unlimited' ? '∞' : (int)$license['max_domains'] ?></p>
                <p><strong>Kaynak:</strong> <?= e($license['source']) ?></p>
                <p><strong>Durum:</strong> <?= e($license['status']) ?></p>
                <p><strong>Üretim:</strong> <?= e($license['issued_at']) ?></p>
                <p><strong>Bitiş:</strong> <?= $license['expires_at'] ? e(date('d.m.Y', strtotime((string)$license['expires_at']))) : 'Süresiz' ?></p>
                <p><strong>Son kontrol:</strong> <?= $license['last_verified_at'] ? e($license['last_verified_at']) : '—' ?></p>
                <p><strong>Kontrol sayısı:</strong> <?= (int)$license['verification_count'] ?></p>
                <?php if ($license['purchase_code']): ?>
                    <p><strong>Envato:</strong> <code style="font-size:11px"><?= e($license['purchase_code']) ?></code></p>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <div class="aho-card">
                <div class="aho-card__header"><h3>🌍 Aktif Domainler/Paketler</h3></div>
                <table class="aho-table">
                    <thead><tr><th>Identifier</th><th>Tip</th><th>IP</th><th>Aktive</th><th>Son Görülme</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($activations as $a): ?>
                            <tr>
                                <td><code><?= e($a['identifier']) ?></code></td>
                                <td><?= e($a['identifier_type']) ?></td>
                                <td><?= e($a['ip']) ?></td>
                                <td><?= e($a['activated_at']) ?></td>
                                <td><?= e($a['last_seen_at']) ?></td>
                                <td>
                                    <?php if ((int)$a['is_active']): ?>
                                        <form method="post" action="/admin/lisanslar/<?= (int)$license['id'] ?>/aktivasyon-kapat" style="display:inline">
                                            <?= csrf() ?>
                                            <input type="hidden" name="activation_id" value="<?= (int)$a['id'] ?>">
                                            <button class="aho-btn aho-btn--sm aho-btn--outline">Kapat</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color:#6b7280">Kapalı</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$activations): ?><tr><td colspan="6" style="text-align:center;color:#6b7280;padding:12px">Henüz aktivasyon yok.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="aho-card" style="margin-top:16px">
                <div class="aho-card__header"><h3>📜 Son Doğrulama Logları</h3></div>
                <table class="aho-table">
                    <thead><tr><th>Tarih</th><th>Domain</th><th>IP</th><th>Sonuç</th></tr></thead>
                    <tbody>
                        <?php foreach ($verifications as $v): ?>
                            <tr>
                                <td style="font-size:12px"><?= e($v['created_at']) ?></td>
                                <td><code><?= e($v['identifier'] ?? '—') ?></code></td>
                                <td style="font-size:12px"><?= e($v['ip']) ?></td>
                                <td><span class="aho-badge"><?= e($v['result']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $view->endSection(); ?>
