<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
$techs = $project['technologies'] ? json_decode((string)$project['technologies'], true) ?: [] : [];
$gallery = $project['gallery'] ? json_decode((string)$project['gallery'], true) ?: [] : [];
?>
<section style="background:linear-gradient(135deg,#0ea5e9,#8b5cf6);color:#fff;padding:50px 0">
    <div class="aho-container">
        <a href="/referanslar" style="color:#fff;opacity:.85;text-decoration:none;font-size:13px">← Tüm Referanslar</a>
        <h1 style="font-size:36px;margin:12px 0 8px"><?= e($project['title']) ?></h1>
        <?php if ($project['client_name']): ?>
            <p style="opacity:.9;margin:0;font-size:15px">👤 <?= e($project['client_name']) ?></p>
        <?php endif; ?>
    </div>
</section>

<section style="padding:40px 0;background:#f9fafb;min-height:60vh">
    <div class="aho-container" style="display:grid;grid-template-columns:2fr 1fr;gap:24px">
        <div>
            <?php if ($project['thumbnail']): ?>
                <img src="<?= e($project['thumbnail']) ?>" style="width:100%;border-radius:12px;margin-bottom:24px">
            <?php endif; ?>

            <div class="aho-card" style="padding:28px">
                <h3 style="margin:0 0 12px">📖 Proje Hakkında</h3>
                <p style="line-height:1.7;color:#374151"><?= nl2br(e($project['description'] ?? '')) ?></p>

                <?php if ($project['challenge']): ?>
                    <h3 style="margin:24px 0 12px">🎯 Zorluk</h3>
                    <p style="line-height:1.7;color:#374151"><?= nl2br(e($project['challenge'])) ?></p>
                <?php endif; ?>

                <?php if ($project['solution']): ?>
                    <h3 style="margin:24px 0 12px">💡 Çözüm</h3>
                    <p style="line-height:1.7;color:#374151"><?= nl2br(e($project['solution'])) ?></p>
                <?php endif; ?>

                <?php if ($project['customer_quote']): ?>
                    <div style="margin-top:24px;padding:20px;background:#f0f9ff;border-left:4px solid #0ea5e9;font-style:italic;color:#0c4a6e">
                        <div style="font-size:32px;line-height:1;margin-bottom:8px">"</div>
                        <?= e($project['customer_quote']) ?>
                        <?php if ($project['client_name']): ?>
                            <div style="margin-top:8px;font-size:13px;color:#0369a1;font-style:normal">— <?= e($project['client_name']) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($related): ?>
                <h3 style="margin:32px 0 12px">🔗 Benzer Projeler</h3>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
                    <?php foreach ($related as $r): ?>
                        <a href="/referanslar/<?= e($r['slug']) ?>" style="text-decoration:none;color:inherit">
                            <div class="aho-card" style="padding:16px;text-align:center">
                                <div style="font-size:32px;margin-bottom:8px">🎨</div>
                                <div style="font-weight:600;font-size:14px"><?= e($r['title']) ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <aside>
            <div class="aho-card" style="padding:20px;position:sticky;top:20px">
                <h4 style="margin:0 0 16px;font-size:14px;color:#6b7280;text-transform:uppercase;letter-spacing:1px">Proje Detayı</h4>
                <div style="display:grid;gap:12px;font-size:14px">
                    <div><strong>Kategori:</strong> <?= e($project['category']) ?></div>
                    <?php if ($project['sector']): ?><div><strong>Sektör:</strong> <?= e($project['sector']) ?></div><?php endif; ?>
                    <?php if ($project['duration_days']): ?><div><strong>Süre:</strong> <?= (int)$project['duration_days'] ?> gün</div><?php endif; ?>
                    <?php if ($project['published_at']): ?><div><strong>Tarih:</strong> <?= e(date('m/Y', strtotime((string)$project['published_at']))) ?></div><?php endif; ?>
                </div>

                <?php if ($techs): ?>
                    <h4 style="margin:20px 0 8px;font-size:14px;color:#6b7280;text-transform:uppercase;letter-spacing:1px">Teknolojiler</h4>
                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                        <?php foreach ($techs as $t): ?>
                            <span style="background:#e0e7ff;color:#4338ca;padding:4px 10px;border-radius:8px;font-size:12px;font-weight:600"><?= e($t) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($project['preview_url']): ?>
                    <a href="<?= e($project['preview_url']) ?>" target="_blank" class="aho-btn aho-btn--primary" style="margin-top:20px;width:100%;text-align:center;display:block">
                        🔗 Canlı Siteyi Gör
                    </a>
                <?php endif; ?>

                <a href="/iletisim" class="aho-btn aho-btn--outline" style="margin-top:12px;width:100%;text-align:center;display:block">
                    💬 Benzer Proje İçin İletişime Geç
                </a>
            </div>
        </aside>
    </div>
</section>
<?php $view->endSection(); ?>
