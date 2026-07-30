<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>🌐 Yeni Domain Ekle</h1>
            <p>Manuel domain kaydı — mevcut bir domaini sisteme dahil eder (registrar API dışı).</p>
        </div>
        <a href="/admin/domain-center" class="aho-btn aho-btn--ghost">← Vazgeç</a>
    </div>

    <?php if (!empty($error)): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

    <form method="post" action="/admin/domain-center/kaydet" class="aho-card" style="padding:24px;max-width:640px">
        <?= csrf() ?>

        <div style="margin-bottom:14px">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Domain Adı *</label>
            <input type="text" name="domain_name" required placeholder="ornek.com"
                   style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px;font-family:monospace;box-sizing:border-box">
        </div>

        <div style="margin-bottom:14px">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Müşteri ID</label>
            <input type="number" name="customer_id" min="1" placeholder="1"
                   style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px;box-sizing:border-box">
            <div style="font-size:11px;color:var(--aho-color-ink-500);margin-top:2px">Boş bırakırsanız 1 (varsayılan admin) olarak kaydedilir.</div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Kayıt Tarihi</label>
                <input type="date" name="registration_date" value="<?= e(date('Y-m-d')) ?>" style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px;box-sizing:border-box">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Bitiş Tarihi</label>
                <input type="date" name="expiry_date" value="<?= e(date('Y-m-d', strtotime('+1 year'))) ?>" style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px;box-sizing:border-box">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Registrar</label>
                <select name="registrar_id" style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px">
                    <option value="">— Seçilmedi —</option>
                    <?php foreach ($registrars as $r): ?>
                        <option value="<?= (int)$r['id'] ?>"><?= e($r['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Durum</label>
                <select name="status" style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px">
                    <option value="active">Aktif</option>
                    <option value="pending">Bekleyen</option>
                </select>
            </div>
        </div>

        <div style="margin-bottom:20px">
            <label style="display:flex;gap:6px;align-items:center;font-size:13px;cursor:pointer">
                <input type="checkbox" name="auto_renew" value="1" checked>
                Otomatik yenile
            </label>
        </div>

        <div style="display:flex;gap:12px;justify-content:flex-end">
            <a href="/admin/domain-center" class="aho-btn aho-btn--ghost">Vazgeç</a>
            <button type="submit" class="aho-btn aho-btn--primary">Domain Ekle</button>
        </div>
    </form>
</div>
<?php $view->endSection(); ?>
