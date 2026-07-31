<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
$periodLabels = ['onetime'=>'Tek Seferlik','monthly'=>'Aylık','quarterly'=>'3 Aylık','semiannually'=>'6 Aylık','annually'=>'Yıllık','biennially'=>'2 Yıllık','triennially'=>'3 Yıllık'];
$panelPort = match ($account['server_panel'] ?? null) { 'cpanel' => 2083, 'da' => 2222, 'plesk' => 8443, default => null };
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>🖥️ <?= e($account['domain'] ?: ('Hosting #' . $account['id'])) ?></h1>
            <p><?= e($account['customer_email'] ?? '') ?> · Hesap #<?= (int)$account['id'] ?>
                <?php if (!empty($account['order_id'])): ?> · <a href="/admin/siparisler/<?= (int)$account['order_id'] ?>">Sipariş #<?= (int)$account['order_id'] ?>'ı Görüntüle</a><?php endif; ?>
            </p>
        </div>
        <div class="aho-admin-page__actions">
            <a href="/admin/musteriler/<?= (int)$account['customer_id'] ?>#hosting" class="aho-btn aho-btn--ghost">← Müşteri Profili</a>
        </div>
    </div>

    <?php if ($success = flash('success')): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error = flash('error')): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

    <?php if (!empty($account['disk_usage_mb']) || !empty($account['bandwidth_usage_mb'])): ?>
        <div class="aho-card" style="display:flex;gap:24px;align-items:center;flex-wrap:wrap">
            <span>💾 Disk: <strong><?= number_format((float)($account['disk_usage_mb'] ?? 0), 0) ?> MB</strong></span>
            <span>📶 Trafik: <strong><?= number_format((float)($account['bandwidth_usage_mb'] ?? 0), 0) ?> MB</strong></span>
            <?php if (!empty($account['usage_updated_at'])): ?><small style="color:var(--aho-color-ink-500)">Son güncelleme: <?= e(substr((string)$account['usage_updated_at'], 0, 16)) ?></small><?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Modül Komutları -->
    <div class="aho-card">
        <h3>Modül Komutları</h3>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <?php if (($account['status'] ?? '') === 'active'): ?>
                <form method="post" action="/admin/musteriler/<?= (int)$account['customer_id'] ?>/hosting/<?= (int)$account['id'] ?>/askiya-al" onsubmit="return confirm('Askıya alınsın mı?')">
                    <?= csrf() ?><button class="aho-btn aho-btn--outline">⏸ Askıya Al</button>
                </form>
            <?php elseif (($account['status'] ?? '') === 'suspended'): ?>
                <form method="post" action="/admin/musteriler/<?= (int)$account['customer_id'] ?>/hosting/<?= (int)$account['id'] ?>/aktif-et" onsubmit="return confirm('Askıdan çıkarılsın mı?')">
                    <?= csrf() ?><button class="aho-btn aho-btn--success">▶ Askıdan Çıkar</button>
                </form>
            <?php endif; ?>
            <form method="post" action="/admin/musteriler/<?= (int)$account['customer_id'] ?>/hosting/<?= (int)$account['id'] ?>/sifre-sifirla" onsubmit="return confirm('Yeni rastgele şifre üretilip sunucuda değiştirilecek. Devam?')">
                <?= csrf() ?><button class="aho-btn aho-btn--outline">🔑 Şifreyi Değiştir</button>
            </form>
            <?php if ($account['server_hostname'] ?? null and $panelPort): ?>
                <a href="https://<?= e($account['server_hostname']) ?>:<?= $panelPort ?>" target="_blank" class="aho-btn aho-btn--primary">🔗 cPanel'e Giriş Yap</a>
            <?php endif; ?>
        </div>
        <p style="font-size:12px;color:var(--aho-color-ink-500);margin-top:10px">💡 Paket değiştirmek için aşağıdan "Ürün/Hizmet" seçip <strong>Değişiklikleri Kaydet</strong>'e bas — panelde de otomatik değiştirilmeye çalışılır.</p>
    </div>

    <form method="post" action="/admin/hosting-hesaplari/<?= (int)$account['id'] ?>/guncelle" class="aho-form">
        <?= csrf() ?>
        <div class="aho-card">
            <h3>Hesap Bilgileri</h3>
            <div class="aho-admin-form-row aho-admin-form-row--2">
                <div>
                    <label>Ürün/Hizmet (Paket Değiştir)</label>
                    <select name="product_id" class="aho-form-select">
                        <option value="">— seçilmedi —</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= (int)($account['product_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?> (<?= e($p['type']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Sunucu</label>
                    <select name="server_id" class="aho-form-select">
                        <option value="">— seçilmedi —</option>
                        <?php foreach ($servers as $s): ?>
                            <option value="<?= (int)$s['id'] ?>" <?= (int)($account['server_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?> (<?= e($s['hostname']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label>Alan Adı</label><input type="text" name="domain" class="aho-form-input" value="<?= e($account['domain'] ?? '') ?>"></div>
                <div><label>Kullanıcı Adı</label><input type="text" name="username" class="aho-form-input" value="<?= e($account['username'] ?? '') ?>"></div>
                <div>
                    <label>Durum</label>
                    <select name="status" class="aho-form-select">
                        <?php foreach (['pending'=>'Kuruluyor','active'=>'Aktif','suspended'=>'Askıda','terminated'=>'Kapatıldı'] as $k=>$v): ?>
                            <option value="<?= $k ?>" <?= ($account['status'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label>Sonraki Ödeme Tarihi</label><input type="date" name="next_due_date" class="aho-form-input" value="<?= e($account['next_due_date'] ?? '') ?>"></div>
                <div style="grid-column:1/-1"><label>Notlar</label><textarea name="notes" rows="3" class="aho-form-textarea"><?= e($account['notes'] ?? '') ?></textarea></div>
            </div>
            <button type="submit" class="aho-btn aho-btn--primary" style="margin-top:14px">💾 Değişiklikleri Kaydet</button>
        </div>
    </form>

    <?php if (!empty($account['order_id']) || $account['unit_price'] !== null): ?>
    <div class="aho-card">
        <h3>Faturalandırma Bilgisi <small style="font-weight:400;color:var(--aho-color-ink-500)">(siparişten, salt okunur)</small></h3>
        <div class="aho-detail-list">
            <p><span>İlk Ödeme (Kurulum)</span><strong><?= number_format((float)($account['setup_fee'] ?? 0), 2) ?> TRY</strong></p>
            <p><span>Yinelenen Tutar</span><strong><?= number_format((float)($account['unit_price'] ?? 0), 2) ?> TRY</strong></p>
            <p><span>Fatura Dönemi</span><strong><?= e($periodLabels[$account['period'] ?? ''] ?? '—') ?></strong></p>
        </div>
        <p style="font-size:12px;color:var(--aho-color-ink-500)">Tutar/dönem değiştirmek için ilgili siparişi düzenle.</p>
    </div>
    <?php endif; ?>
</div>
<?php $view->endSection(); ?>
