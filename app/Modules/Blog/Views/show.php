<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<article style="padding:40px 0">
    <div class="aho-container" style="max-width:760px">
        <div style="margin-bottom:24px">
            <?php if ($post['category']): ?>
                <div style="font-size:12px;color:var(--aho-color-primary-600);font-weight:600;text-transform:uppercase"><?= e($post['category']) ?></div>
            <?php endif; ?>
            <h1 style="margin:8px 0 12px;font-size:36px;line-height:1.2"><?= e($post['title']) ?></h1>
            <div style="color:var(--aho-color-ink-500);font-size:13px;display:flex;gap:16px">
                <?php if ($post['published_at']): ?>
                    <span>📅 <?= e(date('d.m.Y', strtotime((string)$post['published_at']))) ?></span>
                <?php endif; ?>
                <span>👁 <?= (int)$post['views'] ?> okunma</span>
            </div>
        </div>

        <?php if (!empty($post['featured_image'])): ?>
            <img src="<?= e($post['featured_image']) ?>" alt="<?= e($post['title']) ?>" style="width:100%;border-radius:12px;margin-bottom:24px">
        <?php endif; ?>

        <div class="aho-blog-body" style="font-size:16px;line-height:1.7;color:var(--aho-color-ink-800)">
            <?= $post['body_html'] // HTML allowed — admin trusted ?>
        </div>

        <div style="margin-top:40px;padding-top:24px;border-top:1px solid var(--aho-color-border);text-align:center">
            <a href="/blog" class="aho-btn aho-btn--ghost">← Tüm Yazılar</a>
        </div>
    </div>
</article>
<style>
.aho-blog-body h2 { margin: 32px 0 12px; font-size: 26px; }
.aho-blog-body h3 { margin: 24px 0 10px; font-size: 20px; }
.aho-blog-body p { margin: 0 0 16px; }
.aho-blog-body ul, .aho-blog-body ol { margin: 0 0 16px; padding-left: 24px; }
.aho-blog-body li { margin: 6px 0; }
.aho-blog-body a { color: var(--aho-color-primary-600); text-decoration: underline; }
.aho-blog-body strong { color: var(--aho-color-ink-900); }
.aho-blog-body blockquote { border-left: 4px solid var(--aho-color-primary-600); padding: 12px 20px; margin: 20px 0; background: #f9fafb; border-radius: 0 8px 8px 0; }
</style>
<?php $view->endSection(); ?>
