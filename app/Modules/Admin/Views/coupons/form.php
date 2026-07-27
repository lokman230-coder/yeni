<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
$isEdit = $coupon !== null;
$c = $coupon ?? [];
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>🎟️ <?= $isEdit ? e($c['code']) : 'Yeni Kupon' ?></h1>
            <?php if ($isEdit): ?>
                <p><?= (int)$c['usage_count'] ?> kez kullanıldı<?= $c['usage_limit'] ? ' / ' . (int)$c['usage_limit'] . ' limit' : '' ?></p>
            <?php endif; ?>
        </div>
        <a href="/admin/kuponlar" class="aho-btn aho-btn--ghost">← Listeye Dön</a>
    </div>

    <?php if (!empty($error)): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>
    <?php if (!empty($success)): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>

    <form method="post" action="<?= $isEdit ? '/admin/kuponlar/'.(int)$c['id'].'/kaydet' : '/admin/kuponlar/kaydet' ?>">
        <?= csrf() ?>

        <div class="aho-card" style="padding:24px;margin-bottom:16px">
            <h3 style="margin-top:0">Temel</h3>
            <div style="display:grid;grid-template-columns:1fr 2fr;gap:16px">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Kod *</label>
                    <input type="text" name="code" required maxlength="64" value="<?= e($c['code'] ?? '') ?>" placeholder="WELCOME10"
                           style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px;font-family:monospace;text-transform:uppercase;font-weight:700;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">İsim</label>
                    <input type="text" name="name" maxlength="191" value="<?= e($c['name'] ?? '') ?>" placeholder="Hoş Geldin İndirimi"
                           style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px;box-sizing:border-box">
                </div>
            </div>
        </div>

        <div class="aho-card" style="padding:24px;margin-bottom:16px">
            <h3 style="margin-top:0">Değer</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Tip</label>
                    <select name="type" style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px">
                        <option value="percent" <?= ($c['type'] ?? 'percent')==='percent'?'selected':'' ?>>Yüzde (%)</option>
                        <option value="fixed"   <?= ($c['type'] ?? '')==='fixed'?'selected':'' ?>>Sabit Tutar</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Değer *</label>
                    <input type="number" step="0.01" min="0" max="100000" name="value" required value="<?= e($c['value'] ?? '10') ?>"
                           style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px;text-align:right;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Para Birimi (fixed için)</label>
                    <select name="currency" style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px">
                        <?php foreach (['TRY','USD','EUR','GBP'] as $cur): ?>
                            <option value="<?= $cur ?>" <?= ($c['currency'] ?? 'TRY')===$cur?'selected':'' ?>><?= $cur ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="aho-card" style="padding:24px;margin-bottom:16px">
            <h3 style="margin-top:0">Kısıtlar</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Başlangıç Tarihi</label>
                    <input type="datetime-local" name="starts_at" value="<?= e(!empty($c['starts_at']) ? date('Y-m-d\TH:i', strtotime((string)$c['starts_at'])) : '') ?>" style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Bitiş Tarihi</label>
                    <input type="datetime-local" name="ends_at" value="<?= e(!empty($c['ends_at']) ? date('Y-m-d\TH:i', strtotime((string)$c['ends_at'])) : '') ?>" style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Maksimum Kullanım (toplam)</label>
                    <input type="number" name="usage_limit" min="1" value="<?= e($c['usage_limit'] ?? '') ?>" placeholder="Sınırsız için boş"
                           style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Müşteri Başına Max Kullanım</label>
                    <input type="number" name="usage_limit_per_customer" min="1" value="<?= e($c['usage_limit_per_customer'] ?? '') ?>" placeholder="Sınırsız"
                           style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Minimum Sepet Tutarı (TRY)</label>
                    <input type="number" step="0.01" name="min_order_total" value="<?= e($c['min_order_total'] ?? '') ?>" placeholder="Şart yok"
                           style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px;box-sizing:border-box">
                </div>
                <div style="display:flex;align-items:center;padding-top:24px;gap:16px;flex-wrap:wrap">
                    <label style="display:flex;gap:6px;align-items:center;font-size:14px;font-weight:600;cursor:pointer">
                        <input type="checkbox" name="is_active" value="1" <?= (int)($c['is_active'] ?? 1)===1?'checked':'' ?>>
                        Aktif
                    </label>
                    <label style="display:flex;gap:6px;align-items:center;font-size:14px;font-weight:600;cursor:pointer;color:#0891b2">
                        <input type="checkbox" name="auto_apply" value="1" <?= (int)($c['auto_apply'] ?? 0)===1?'checked':'' ?>>
                        🎯 Otomatik Uygula
                    </label>
                </div>
                <div style="grid-column:1/-1;font-size:12px;color:var(--aho-color-ink-500);margin-top:-8px">
                    💡 <strong>Otomatik Uygula:</strong> Bu kupon, "Min Sepet Tutarı" karşılandığında kullanıcı kod girmese bile <strong>otomatik uygulanır</strong>. Birden fazla uygun kupon varsa <em>öncelik</em> yüksek olan seçilir.
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Öncelik (0-100, yüksek=önce)</label>
                    <input type="number" name="priority" min="0" max="100" value="<?= e($c['priority'] ?? 0) ?>" style="width:120px;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px;box-sizing:border-box">
                </div>
            </div>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center">
            <div>
                <?php if ($isEdit): ?>
                    <form method="post" action="/admin/kuponlar/<?= (int)$c['id'] ?>/sil" style="display:inline" onsubmit="return confirm('<?= e($c['code']) ?> silinsin mi?')">
                        <?= csrf() ?>
                        <button type="submit" style="padding:10px 16px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:8px;cursor:pointer;font-weight:600">🗑️ Sil</button>
                    </form>
                <?php endif; ?>
            </div>
            <button type="submit" class="aho-btn aho-btn--primary">💾 <?= $isEdit ? 'Kaydet' : 'Oluştur' ?></button>
        </div>
    </form>
</div>
<?php $view->endSection(); ?>
