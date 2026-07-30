<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
$isEdit = $customer !== null;
$action = $isEdit ? "/admin/musteriler/{$customer['id']}/guncelle" : "/admin/musteriler/kaydet";
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1><?= $isEdit ? '✏️ ' . e($customer['email']) : '+ Yeni Müşteri' ?></h1>
            <p><?= $isEdit ? 'Müşteri bilgilerini düzenle.' : 'Manuel müşteri kaydı oluştur.' ?></p>
        </div>
        <div class="aho-admin-page__actions">
            <a href="/admin/musteriler" class="aho-btn aho-btn--ghost">← Liste</a>
        </div>
    </div>

    <form method="post" action="<?= e($action) ?>" class="aho-form">
        <?= csrf() ?>

        <div class="aho-card">
            <div class="aho-card__header"><h3>📇 Kimlik</h3></div>
            <div class="aho-card__body" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div>
                    <label>E-posta *</label>
                    <input type="email" name="email" required value="<?= e($customer['email'] ?? '') ?>">
                </div>
                <div>
                    <label>Telefon</label>
                    <input type="tel" name="phone" value="<?= e($customer['phone'] ?? '') ?>">
                </div>
                <div>
                    <label>Ad</label>
                    <input type="text" name="first_name" value="<?= e($customer['first_name'] ?? '') ?>">
                </div>
                <div>
                    <label>Soyad</label>
                    <input type="text" name="last_name" value="<?= e($customer['last_name'] ?? '') ?>">
                </div>
                <div>
                    <label>Firma</label>
                    <input type="text" name="company" value="<?= e($customer['company'] ?? '') ?>">
                </div>
                <div>
                    <label>Vergi No / TCKN</label>
                    <input type="text" name="tax_id" value="<?= e($customer['tax_id'] ?? '') ?>">
                </div>
                <div>
                    <label>Vergi Dairesi</label>
                    <input type="text" name="tax_office" value="<?= e($customer['tax_office'] ?? '') ?>">
                </div>
                <div>
                    <label>Müşteri Tipi</label>
                    <select name="is_individual">
                        <option value="1" <?= ($customer['is_individual'] ?? 1) == 1 ? 'selected' : '' ?>>Bireysel</option>
                        <option value="0" <?= ($customer['is_individual'] ?? 1) == 0 ? 'selected' : '' ?>>Kurumsal</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="aho-card" style="margin-top:16px">
            <div class="aho-card__header"><h3>🏠 Adres</h3></div>
            <div class="aho-card__body" style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:14px">
                <div style="grid-column:1/-1">
                    <label>Adres</label>
                    <textarea name="address" rows="2"><?= e($customer['address'] ?? '') ?></textarea>
                </div>
                <div>
                    <label>Şehir</label>
                    <input type="text" name="city" value="<?= e($customer['city'] ?? '') ?>">
                </div>
                <div>
                    <label>Posta Kodu</label>
                    <input type="text" name="postcode" value="<?= e($customer['postcode'] ?? '') ?>">
                </div>
                <div>
                    <label>Ülke</label>
                    <input type="text" name="country" maxlength="2" value="<?= e($customer['country'] ?? 'TR') ?>" placeholder="TR">
                </div>
            </div>
        </div>

        <div class="aho-card" style="margin-top:16px">
            <div class="aho-card__header"><h3>⚙️ Ayarlar</h3></div>
            <div class="aho-card__body" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px">
                <div>
                    <label>Durum</label>
                    <select name="status">
                        <?php foreach (['active'=>'Aktif','pending'=>'Beklemede','suspended'=>'Askıda','closed'=>'Kapalı'] as $k=>$v): ?>
                            <option value="<?= $k ?>" <?= ($customer['status'] ?? 'active') === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Dil</label>
                    <select name="preferred_language">
                        <option value="tr" <?= ($customer['preferred_language'] ?? 'tr') === 'tr' ? 'selected' : '' ?>>Türkçe</option>
                        <option value="en" <?= ($customer['preferred_language'] ?? 'tr') === 'en' ? 'selected' : '' ?>>English</option>
                        <option value="de" <?= ($customer['preferred_language'] ?? 'tr') === 'de' ? 'selected' : '' ?>>Deutsch</option>
                    </select>
                </div>
                <div>
                    <label>Para Birimi</label>
                    <select name="preferred_currency">
                        <?php foreach (['TRY','USD','EUR','GBP'] as $c): ?>
                            <option value="<?= $c ?>" <?= ($customer['preferred_currency'] ?? 'TRY') === $c ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label><?= $isEdit ? 'Yeni Şifre (boşsa değişmez)' : 'Şifre *' ?></label>
                    <input type="password" name="password" <?= $isEdit ? '' : 'required' ?>>
                </div>
                <div style="grid-column:2/4;display:flex;align-items:center;gap:16px;padding-top:20px">
                    <label><input type="checkbox" name="email_verified" value="1" <?= !empty($customer['email_verified_at']) ? 'checked' : '' ?>> E-posta doğrulanmış</label>
                </div>
            </div>
        </div>

        <div class="aho-card" style="margin-top:16px">
            <div class="aho-card__header"><h3>📝 Admin Notları <small style="color:#6b7280;font-weight:400">(müşteri görmez)</small></h3></div>
            <div class="aho-card__body">
                <textarea name="admin_notes" rows="4" placeholder="Sadece admin ekibinin göreceği notlar — ödeme geçmişi, özel durumlar, iletişim tercihi..."><?= e($customer['admin_notes'] ?? '') ?></textarea>
            </div>
        </div>

        <div style="margin-top:16px;display:flex;gap:8px;justify-content:space-between">
            <?php if ($isEdit): ?>
                <div style="display:flex;gap:6px">
                    <form method="post" action="/admin/musteriler/<?= (int)$customer['id'] ?>/askiya-al" style="display:inline">
                        <?= csrf() ?>
                        <button type="submit" class="aho-btn aho-btn--warning">
                            <?= $customer['status'] === 'suspended' ? '▶ Aktif Et' : '⏸ Askıya Al' ?>
                        </button>
                    </form>
                    <form method="post" action="/admin/musteriler/<?= (int)$customer['id'] ?>/sil" style="display:inline" onsubmit="return confirm('Kapatılsın mı?')">
                        <?= csrf() ?>
                        <button type="submit" class="aho-btn aho-btn--danger">🗑 Kapat</button>
                    </form>
                </div>
            <?php endif; ?>
            <button type="submit" class="aho-btn aho-btn--primary" style="margin-left:auto">💾 Kaydet</button>
        </div>
    </form>
</div>
<?php $view->endSection(); ?>
