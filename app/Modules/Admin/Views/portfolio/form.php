<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
$isEdit = $project !== null;
$action = $isEdit ? "/admin/portfolio/{$project['id']}/guncelle" : "/admin/portfolio/kaydet";
$techs = (!empty($project['technologies'])) ? implode(', ', json_decode((string)$project['technologies'], true) ?: []) : '';
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div><h1><?= $isEdit ? '✏️ ' . e($project['title']) : '+ Yeni Portfolio Projesi' ?></h1></div>
        <div class="aho-admin-page__actions"><a href="/admin/portfolio" class="aho-btn aho-btn--ghost">← Liste</a></div>
    </div>

    <form method="post" action="<?= e($action) ?>" class="aho-form">
        <?= csrf() ?>
        <div class="aho-card">
            <div class="aho-card__body" style="display:grid;grid-template-columns:2fr 1fr;gap:14px">
                <div>
                    <label>Proje Başlığı *</label>
                    <input type="text" name="title" required value="<?= e($project['title'] ?? '') ?>">
                </div>
                <div>
                    <label>Müşteri Adı</label>
                    <input type="text" name="client_name" value="<?= e($project['client_name'] ?? '') ?>">
                </div>
                <div>
                    <label>Kategori</label>
                    <select name="category">
                        <?php foreach (['web'=>'Web Sitesi','mobile'=>'Mobil Uygulama','ecommerce'=>'E-Ticaret','corporate'=>'Kurumsal','landing'=>'Landing Page','saas'=>'SaaS','marketplace'=>'Marketplace','portfolio'=>'Portfolio','custom'=>'Özel'] as $k => $v): ?>
                            <option value="<?= $k ?>" <?= ($project['category'] ?? 'web') === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Sektör</label>
                    <input type="text" name="sector" value="<?= e($project['sector'] ?? '') ?>" placeholder="Örn: restoran, sağlık, e-ticaret">
                </div>
                <div style="grid-column:1/-1">
                    <label>Açıklama</label>
                    <textarea name="description" rows="3"><?= e($project['description'] ?? '') ?></textarea>
                </div>
                <div>
                    <label>Zorluk / Sorun</label>
                    <textarea name="challenge" rows="3"><?= e($project['challenge'] ?? '') ?></textarea>
                </div>
                <div>
                    <label>Çözüm</label>
                    <textarea name="solution" rows="3"><?= e($project['solution'] ?? '') ?></textarea>
                </div>
                <div>
                    <label>Canlı Site URL</label>
                    <input type="url" name="preview_url" value="<?= e($project['preview_url'] ?? '') ?>" placeholder="https://...">
                </div>
                <div>
                    <label>Kapak Görseli URL</label>
                    <input type="text" name="thumbnail" value="<?= e($project['thumbnail'] ?? '') ?>">
                </div>
                <div style="grid-column:1/-1">
                    <label>Teknolojiler (virgülle ayır)</label>
                    <input type="text" name="technologies" value="<?= e($techs) ?>" placeholder="Laravel, Vue.js, MySQL">
                </div>
                <div style="grid-column:1/-1">
                    <label>Müşteri Yorumu</label>
                    <textarea name="customer_quote" rows="2"><?= e($project['customer_quote'] ?? '') ?></textarea>
                </div>
                <div>
                    <label>Süre (gün)</label>
                    <input type="number" name="duration_days" value="<?= (int)($project['duration_days'] ?? 0) ?>">
                </div>
                <div>
                    <label>Sıralama</label>
                    <input type="number" name="sort_order" value="<?= (int)($project['sort_order'] ?? 0) ?>">
                </div>
                <div style="grid-column:1/-1;display:flex;gap:20px">
                    <label><input type="checkbox" name="is_featured" value="1" <?= !empty($project['is_featured']) ? 'checked' : '' ?>> Öne çıkar</label>
                    <label><input type="checkbox" name="is_published" value="1" <?= !$isEdit || !empty($project['is_published']) ? 'checked' : '' ?>> Yayında</label>
                </div>
            </div>
        </div>
        <div style="margin-top:16px;text-align:right">
            <button type="submit" class="aho-btn aho-btn--primary">💾 Kaydet</button>
        </div>
    </form>
</div>
<?php $view->endSection(); ?>
