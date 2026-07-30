<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>🌐 <?= e($domain['domain_name']) ?></h1>
            <p>Müşteri: <?= e($domain['customer_email'] ?? '—') ?></p>
        </div>
        <a href="/admin/domain-center" class="aho-btn aho-btn--ghost">← Listeye Dön</a>
    </div>

    <?php if (!empty($success)): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

    <form method="post" action="/admin/domain-center/<?= (int)$domain['id'] ?>/kaydet">
        <?= csrf() ?>

        <div class="aho-card" style="padding:24px;margin-bottom:16px">
            <h3 style="margin-top:0">Genel</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Durum</label>
                    <select name="status" style="width:100%;padding:8px;border:1px solid var(--aho-color-border);border-radius:6px">
                        <?php foreach (['active'=>'Aktif','pending'=>'Bekleyen','pending_transfer'=>'Transfer','expired'=>'Süresi Geçmiş','cancelled'=>'İptal','suspended'=>'Askıda'] as $k=>$v): ?>
                            <option value="<?= e($k) ?>" <?= $domain['status']===$k?'selected':'' ?>><?= e($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Registrar</label>
                    <select name="registrar_id" style="width:100%;padding:8px;border:1px solid var(--aho-color-border);border-radius:6px">
                        <option value="">— Seçilmedi —</option>
                        <?php foreach ($registrars as $r): ?>
                            <option value="<?= (int)$r['id'] ?>" <?= (int)$domain['registrar_id']===(int)$r['id']?'selected':'' ?>><?= e($r['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Bitiş Tarihi</label>
                    <input type="date" name="expiry_date" value="<?= e($domain['expiry_date'] ?? '') ?>" style="width:100%;padding:8px;border:1px solid var(--aho-color-border);border-radius:6px;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Sonraki Yenileme</label>
                    <input type="date" name="next_due_date" value="<?= e($domain['next_due_date'] ?? '') ?>" style="width:100%;padding:8px;border:1px solid var(--aho-color-border);border-radius:6px;box-sizing:border-box">
                </div>
            </div>

            <div style="display:flex;gap:20px;margin-top:16px">
                <label style="display:flex;gap:6px;align-items:center;font-size:13px;cursor:pointer">
                    <input type="checkbox" name="auto_renew" value="1" <?= (int)$domain['auto_renew']===1?'checked':'' ?>>
                    Otomatik Yenileme
                </label>
                <label style="display:flex;gap:6px;align-items:center;font-size:13px;cursor:pointer">
                    <input type="checkbox" name="transfer_lock" value="1" <?= (int)$domain['transfer_lock']===1?'checked':'' ?>>
                    Transfer Kilidi
                </label>
                <label style="display:flex;gap:6px;align-items:center;font-size:13px;cursor:pointer">
                    <input type="checkbox" name="whois_privacy" value="1" <?= (int)$domain['whois_privacy']===1?'checked':'' ?>>
                    WHOIS Gizliliği
                </label>
            </div>
        </div>

        <div class="aho-card" style="padding:24px;margin-bottom:16px">
            <h3 style="margin-top:0">Teknik</h3>
            <div style="margin-bottom:12px">
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Nameservers (her satıra bir tane veya virgülle ayrılmış)</label>
                <textarea name="nameservers" rows="3" placeholder="ns1.ahost.web.tr&#10;ns2.ahost.web.tr" style="width:100%;padding:8px;border:1px solid var(--aho-color-border);border-radius:6px;font-family:monospace;font-size:13px;box-sizing:border-box"><?= e($domain['nameservers'] ?? '') ?></textarea>
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">EPP Code (transfer için)</label>
                <input type="text" name="epp_code" value="<?= e($domain['epp_code'] ?? '') ?>" style="width:100%;padding:8px;border:1px solid var(--aho-color-border);border-radius:6px;font-family:monospace;font-size:13px;box-sizing:border-box">
            </div>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap">
            <div style="display:flex;gap:8px">
                <form method="post" action="/admin/domain-center/<?= (int)$domain['id'] ?>/sil" style="display:inline"
                      onsubmit="return confirm('<?= e($domain['domain_name']) ?> silinsin mi? Bu işlem geri alınamaz.')">
                    <?= csrf() ?>
                    <button type="submit" style="padding:10px 16px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:8px;cursor:pointer;font-weight:600">🗑️ Sil</button>
                </form>
            </div>
            <div style="display:flex;gap:8px">
                <form method="post" action="/admin/domain-center/<?= (int)$domain['id'] ?>/whois" style="display:inline">
                    <?= csrf() ?>
                    <button type="submit" style="padding:10px 16px;background:#e0f2fe;color:#0891b2;border:1px solid #7dd3fc;border-radius:8px;cursor:pointer;font-weight:600">🔍 WHOIS'ten Yenile</button>
                </form>
                <button type="submit" class="aho-btn aho-btn--primary">💾 Değişiklikleri Kaydet</button>
            </div>
        </div>
    </form>
</div>
<?php $view->endSection(); ?>
