<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-pages-hero"><div class="aho-container"><h1>Duyurular</h1></div></section>
<section class="aho-pages-body"><div class="aho-container">
    <div style="max-width:800px;margin:0 auto;display:flex;flex-direction:column;gap:var(--aho-space-4)">
        <?php for ($i = 1; $i <= 3; $i++): ?>
        <div class="aho-card">
            <div style="color:var(--aho-color-ink-500);font-size:var(--aho-text-xs);margin-bottom:var(--aho-space-1)"><?= date('d.m.Y', strtotime("-{$i} day")) ?></div>
            <h3 style="margin-bottom:var(--aho-space-2)">Örnek Duyuru <?= $i ?></h3>
            <p style="color:var(--aho-color-ink-600)">Duyurular admin panelinden eklenip yönetilir. Bu bir demo listedir.</p>
        </div>
        <?php endfor; ?>
    </div>
</div></section>
<?php $view->endSection(); ?>
