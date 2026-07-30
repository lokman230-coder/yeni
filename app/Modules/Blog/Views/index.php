<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-pages-hero">
    <div class="aho-container"><h1>📝 Blog</h1><p>Hosting, domain, web teknolojileri ve daha fazlası.</p></div>
</section>

<section style="padding:40px 0">
    <div class="aho-container">
        <?php if (empty($posts)): ?>
            <div style="padding:60px;text-align:center;color:var(--aho-color-ink-500)">
                <div style="font-size:48px">📝</div>
                <p>Henüz yayında blog yazısı yok.</p>
            </div>
        <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px">
            <?php foreach ($posts as $p): ?>
                <a href="/blog/<?= e($p['slug']) ?>" class="aho-card" style="padding:0;overflow:hidden;text-decoration:none;color:inherit;display:block;transition:transform .15s">
                    <?php if (!empty($p['featured_image'])): ?>
                        <div style="height:180px;background:url('<?= e($p['featured_image']) ?>') center/cover"></div>
                    <?php else: ?>
                        <div style="height:180px;background:linear-gradient(135deg,#0ea5e9,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:48px;color:#fff">📝</div>
                    <?php endif; ?>
                    <div style="padding:20px">
                        <?php if ($p['category']): ?>
                            <div style="font-size:11px;color:var(--aho-color-primary-600);font-weight:600;text-transform:uppercase;margin-bottom:6px"><?= e($p['category']) ?></div>
                        <?php endif; ?>
                        <h2 style="font-size:18px;margin:0 0 8px;line-height:1.3"><?= e($p['title']) ?></h2>
                        <p style="color:var(--aho-color-ink-600);font-size:14px;margin:0 0 12px;line-height:1.5"><?= e(mb_substr((string)($p['excerpt'] ?? ''), 0, 120)) ?><?= mb_strlen((string)($p['excerpt']??''))>120?'…':'' ?></p>
                        <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--aho-color-ink-500)">
                            <span><?= $p['published_at'] ? e(date('d.m.Y', strtotime((string)$p['published_at']))) : '' ?></span>
                            <span>👁 <?= (int)$p['views'] ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php $view->endSection(); ?>
