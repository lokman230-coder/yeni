<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
$projects = \App\Core\Database\Connection::select(
    "SELECT * FROM portfolio_projects WHERE is_published = 1 ORDER BY is_featured DESC, sort_order ASC, id DESC"
);
$categories = [];
foreach ($projects as $p) {
    $categories[$p['category']] = true;
}
$categoryLabels = [
    'web'         => '🌐 Web Sitesi',
    'mobile'      => '📱 Mobil Uygulama',
    'ecommerce'   => '🛒 E-Ticaret',
    'corporate'   => '🏢 Kurumsal',
    'landing'     => '🎯 Landing Page',
    'saas'        => '☁ SaaS',
    'marketplace' => '🏪 Marketplace',
    'portfolio'   => '🎨 Portfolio',
    'custom'      => '⚙ Özel Yazılım',
];
?>
<section class="aho-pages-hero" style="background:linear-gradient(135deg,#0ea5e9 0%,#8b5cf6 100%);color:#fff;padding:60px 0">
    <div class="aho-container" style="text-align:center">
        <h1 style="font-size:36px;margin:0 0 12px">🎨 Referanslarımız</h1>
        <p style="opacity:.9;font-size:16px;max-width:600px;margin:0 auto">
            <?= count($projects) ?>+ başarılı proje. Web, mobil, e-ticaret, SaaS ve daha fazlası.
        </p>
    </div>
</section>

<section class="aho-pages-body" style="padding:40px 0">
    <div class="aho-container">
        <?php if (count($categories) > 1): ?>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px;justify-content:center">
            <button onclick="filterProjects('all', this)" class="aho-btn aho-btn--sm aho-btn--primary" data-filter="all">Tümü (<?= count($projects) ?>)</button>
            <?php foreach ($categories as $cat => $_):
                $count = count(array_filter($projects, fn($p) => $p['category'] === $cat));
            ?>
                <button onclick="filterProjects('<?= e($cat) ?>', this)" class="aho-btn aho-btn--sm aho-btn--outline" data-filter="<?= e($cat) ?>">
                    <?= e($categoryLabels[$cat] ?? $cat) ?> (<?= $count ?>)
                </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px" id="portfolio-grid">
            <?php foreach ($projects as $p):
                $techs = $p['technologies'] ? json_decode((string)$p['technologies'], true) : [];
            ?>
                <div class="aho-card portfolio-item" data-category="<?= e($p['category']) ?>" style="overflow:hidden;transition:transform .2s,box-shadow .2s">
                    <?php if ($p['thumbnail']): ?>
                        <img src="<?= e($p['thumbnail']) ?>" style="width:100%;height:180px;object-fit:cover;display:block">
                    <?php else: ?>
                        <div style="height:180px;background:linear-gradient(135deg,#0ea5e9,#8b5cf6);display:flex;align-items:center;justify-content:center;color:#fff;font-size:48px">
                            <?= $categoryLabels[$p['category']][0] ?? '🎨' ?>
                        </div>
                    <?php endif; ?>
                    <div style="padding:20px">
                        <div style="display:flex;justify-content:space-between;align-items:start;gap:8px;margin-bottom:8px">
                            <h3 style="margin:0;font-size:17px;line-height:1.3"><?= e($p['title']) ?></h3>
                            <?php if ((int)$p['is_featured']): ?>
                                <span style="background:#f59e0b;color:#fff;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700;white-space:nowrap">⭐ ÖNE ÇIKAN</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($p['client_name']): ?>
                            <div style="font-size:13px;color:#6b7280;margin-bottom:8px">👤 <?= e($p['client_name']) ?></div>
                        <?php endif; ?>
                        <?php if ($p['description']): ?>
                            <p style="font-size:14px;color:#4b5563;line-height:1.5;margin:8px 0"><?= e(mb_substr($p['description'], 0, 140)) ?><?= mb_strlen($p['description']) > 140 ? '…' : '' ?></p>
                        <?php endif; ?>
                        <?php if ($techs): ?>
                            <div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:12px">
                                <?php foreach (array_slice($techs, 0, 5) as $t): ?>
                                    <span style="background:#e0e7ff;color:#4338ca;padding:2px 8px;border-radius:8px;font-size:11px;font-weight:600"><?= e($t) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($p['customer_quote']): ?>
                            <div style="margin-top:12px;padding:10px;background:#f9fafb;border-left:3px solid #0ea5e9;font-size:13px;font-style:italic;color:#4b5563">
                                "<?= e(mb_substr($p['customer_quote'], 0, 120)) ?>"
                            </div>
                        <?php endif; ?>
                        <?php if ($p['preview_url']): ?>
                            <a href="<?= e($p['preview_url']) ?>" target="_blank" rel="noopener" class="aho-btn aho-btn--sm aho-btn--outline" style="margin-top:12px;width:100%;text-align:center">
                                🔗 Canlı Siteyi Gör
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$projects): ?>
                <div style="grid-column:1/-1;text-align:center;padding:60px;color:#6b7280">
                    Henüz referans proje eklenmemiş.
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
.portfolio-item:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.08); }
</style>

<script>
function filterProjects(category, btn) {
    document.querySelectorAll('[data-filter]').forEach(b => {
        b.classList.remove('aho-btn--primary');
        b.classList.add('aho-btn--outline');
    });
    btn.classList.remove('aho-btn--outline');
    btn.classList.add('aho-btn--primary');
    document.querySelectorAll('.portfolio-item').forEach(item => {
        item.style.display = (category === 'all' || item.getAttribute('data-category') === category) ? 'block' : 'none';
    });
}
</script>
<?php $view->endSection(); ?>
