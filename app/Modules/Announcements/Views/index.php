<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-pages-hero"><div class="aho-container"><h1>Duyurular</h1></div></section>
<section class="aho-pages-body"><div class="aho-container">
    <div style="max-width:800px;margin:0 auto;display:flex;flex-direction:column;gap:var(--aho-space-4)">
        <?php if (empty($items)): ?><div class="aho-card"><h3>Henüz duyuru yok</h3><p style="color:var(--aho-color-ink-600)">Yeni duyurular burada yayınlanacak.</p></div><?php endif; ?>
        <?php foreach ($items as $item): ?>
        <article class="aho-card"><div style="color:var(--aho-color-ink-500);font-size:var(--aho-text-xs);margin-bottom:var(--aho-space-1)"><?= !empty($item['published_at']) ? e(date('d.m.Y', strtotime((string)$item['published_at']))) : '' ?></div><h3 style="margin-bottom:var(--aho-space-2)"><?= e($item['title']) ?></h3><div style="color:var(--aho-color-ink-600)"><?= nl2br(e($item['content'])) ?></div></article>
        <?php endforeach; ?>
    </div>
</div></section>
<?php $view->endSection(); ?>
