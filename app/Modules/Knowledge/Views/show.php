<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-pages-hero">
    <div class="aho-container">
        <a href="/bilgi-bankasi<?= $category ? '/kategori/' . e($article['category']) : '' ?>" style="color:var(--aho-color-ink-500);text-decoration:none;font-size:14px">← Bilgi Bankasi</a>
        <h1 style="margin-top:var(--aho-space-3)"><?= e($article['title']) ?></h1>
        <p style="color:var(--aho-color-ink-500);margin-top:var(--aho-space-2)"><?= e($article['summary']) ?></p>
    </div>
</section>
<section class="aho-pages-body">
    <div class="aho-container" style="max-width:820px">
        <article class="aho-card" style="padding:28px">
            <?php foreach ($article['body'] as $i => $line): ?>
                <p style="font-size:16px;line-height:1.7;color:var(--aho-color-ink-700)"><strong><?= $i + 1 ?>.</strong> <?= e($line) ?></p>
            <?php endforeach; ?>
        </article>
        <div style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap">
            <a href="/destek" class="aho-btn aho-btn--outline">Destek Al</a>
            <a href="/bilgi-bankasi" class="aho-btn aho-btn--primary">Tum Bilgiler</a>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
