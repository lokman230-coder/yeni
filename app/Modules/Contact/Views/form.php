<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
$success = flash('success');
$error = flash('error');
?>
<section class="aho-pages-hero">
    <div class="aho-container">
        <h1>İletişim</h1>
        <p style="color:var(--aho-color-ink-500);margin-top:var(--aho-space-2)">Sorularınız için formu doldurun ya da doğrudan bize ulaşın.</p>
    </div>
</section>

<section class="aho-pages-body">
    <div class="aho-container">
        <div class="aho-contact-grid">
            <div class="aho-card">
                <h2 style="margin-bottom:var(--aho-space-4)">📞 İletişim Bilgileri</h2>
                <ul style="display:flex;flex-direction:column;gap:var(--aho-space-3);color:var(--aho-color-ink-700)">
                    <li><strong>Telefon:</strong> 0850 000 00 00</li>
                    <li><strong>E-posta:</strong> destek@ahost.web.tr</li>
                    <li><strong>Adres:</strong> İstanbul, Türkiye</li>
                    <li><strong>Destek:</strong> 7/24</li>
                </ul>
            </div>

            <div class="aho-card">
                <h2 style="margin-bottom:var(--aho-space-4)">✉️ Bize Yazın</h2>

                <?php if ($success): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
                <?php if ($error): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

                <form method="post" action="/iletisim">
                    <?= csrf() ?>
                    <div class="aho-form-group">
                        <label class="aho-form-label aho-form-label--required" for="name">Ad Soyad</label>
                        <input type="text" id="name" name="name" class="aho-form-input" value="<?= e(old('name')) ?>" required>
                    </div>
                    <div class="aho-form-group">
                        <label class="aho-form-label aho-form-label--required" for="email">E-posta</label>
                        <input type="email" id="email" name="email" class="aho-form-input" value="<?= e(old('email')) ?>" required>
                    </div>
                    <div class="aho-form-group">
                        <label class="aho-form-label" for="phone">Telefon</label>
                        <input type="tel" id="phone" name="phone" class="aho-form-input" value="<?= e(old('phone')) ?>">
                    </div>
                    <div class="aho-form-group">
                        <label class="aho-form-label" for="subject">Konu</label>
                        <input type="text" id="subject" name="subject" class="aho-form-input" value="<?= e(old('subject')) ?>">
                    </div>
                    <div class="aho-form-group">
                        <label class="aho-form-label aho-form-label--required" for="message">Mesaj</label>
                        <textarea id="message" name="message" class="aho-form-textarea" rows="5" required><?= e(old('message')) ?></textarea>
                    </div>
                    <button type="submit" class="aho-btn aho-btn--primary aho-btn--lg aho-btn--block">Mesajı Gönder</button>
                </form>
            </div>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
