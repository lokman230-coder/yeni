<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-pages-hero"><div class="aho-container"><h1>Domain Transfer</h1><p style="color:var(--aho-color-ink-500);margin-top:var(--aho-space-2)">Domain'inizi Ahost Bilişim'a taşıyın, +1 yıl uzatın.</p></div></section>
<section class="aho-pages-body"><div class="aho-container">
    <div class="aho-card" style="max-width:600px;margin:0 auto">
        <h2 style="margin-bottom:var(--aho-space-4)">Transfer Et</h2>
        <form>
            <div class="aho-form-group">
                <label class="aho-form-label">Domain</label>
                <input type="text" class="aho-form-input" placeholder="ornekdomain.com" required>
            </div>
            <div class="aho-form-group">
                <label class="aho-form-label">EPP / Auth Kodu</label>
                <input type="text" class="aho-form-input" placeholder="XXXXXX" required>
            </div>
            <button class="aho-btn aho-btn--primary aho-btn--block aho-btn--lg" type="button">Transfer Başlat</button>
        </form>
        <p style="color:var(--aho-color-ink-500);font-size:var(--aho-text-sm);margin-top:var(--aho-space-4)">
            Gerçek transfer akışı Faz 4'te aktifleşecektir.
        </p>
    </div>
</div></section>
<?php $view->endSection(); ?>
