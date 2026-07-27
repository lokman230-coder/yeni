<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
$error = flash('error');
?>
<section class="aho-customer-page">
    <div class="aho-container" style="max-width:720px">
        <div class="aho-customer-header">
            <div>
                <h1>Yeni Destek Talebi</h1>
                <p class="aho-customer-header__welcome">Sorununuzu detaylı yazın, en kısa sürede dönüş yapılacak.</p>
            </div>
            <a href="/panel/destek" class="aho-btn aho-btn--ghost aho-btn--sm">← Geri</a>
        </div>

        <?php if ($error): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

        <form method="post" action="/panel/destek/yeni" class="aho-card">
            <?= csrf() ?>
            <div class="aho-form-group">
                <label class="aho-form-label aho-form-label--required">Konu</label>
                <input type="text" name="subject" class="aho-form-input" required maxlength="255" placeholder="Kısa özet">
            </div>

            <div class="aho-admin-form-row aho-admin-form-row--2">
                <div class="aho-form-group">
                    <label class="aho-form-label">Departman</label>
                    <select name="department_id" class="aho-form-select">
                        <option value="">Genel</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="aho-form-group">
                    <label class="aho-form-label">Öncelik</label>
                    <select name="priority" class="aho-form-select">
                        <option value="low">Düşük</option>
                        <option value="medium" selected>Orta</option>
                        <option value="high">Yüksek</option>
                        <option value="urgent">Acil</option>
                    </select>
                </div>
            </div>

            <div class="aho-form-group">
                <label class="aho-form-label aho-form-label--required">Mesaj</label>
                <textarea name="message" class="aho-form-textarea" rows="8" required placeholder="Sorununuzu detaylı yazın..."></textarea>
            </div>

            <button type="submit" class="aho-btn aho-btn--primary aho-btn--lg">Talebi Gönder</button>
        </form>
    </div>
</section>
<?php $view->endSection(); ?>
