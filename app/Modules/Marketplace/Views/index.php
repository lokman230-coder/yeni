<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-pages-hero" style="padding-block:var(--aho-space-12) var(--aho-space-6)">
    <div class="aho-container">
        <h1>Marketplace</h1>
        <p style="color:var(--aho-color-ink-500);margin-top:var(--aho-space-2)">
            Domain, tasarım, script, hizmet — güvenli komisyon sistemiyle.
        </p>
    </div>
</section>

<section class="aho-pages-body">
    <div class="aho-container">
        <form method="get" style="max-width:600px;margin-bottom:var(--aho-space-6);display:flex;gap:var(--aho-space-2)">
            <input type="text" name="q" value="<?= e($filters['q']) ?>" class="aho-form-input" placeholder="🔍 İlan ara..." style="flex:1">
            <button class="aho-btn aho-btn--primary">Ara</button>
        </form>

        <div class="aho-mp-cats">
            <a href="/marketplace" class="aho-mp-cat <?= empty($filters['category_id']) ? 'is-active' : '' ?>">Tümü</a>
            <?php foreach ($categories as $c): ?>
                <a href="/marketplace?category_id=<?= (int)$c['id'] ?>"
                   class="aho-mp-cat <?= ($filters['category_id'] ?? 0) === (int)$c['id'] ? 'is-active' : '' ?>">
                    <?= e($c['icon'] ?? '📦') ?> <?= e($c['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($listings)): ?>
            <div class="aho-card">
                <div class="aho-empty-state" style="padding:var(--aho-space-12)">
                    <div class="aho-empty-state__icon" style="font-size:56px">🛍️</div>
                    <div class="aho-empty-state__title">Bu kategoride ilan yok</div>
                    <div class="aho-empty-state__text">Farklı bir kategori seçin veya sonra tekrar bakın.</div>
                </div>
            </div>
        <?php else: ?>
            <div class="aho-mp-grid">
                <?php foreach ($listings as $l): ?>
                    <a href="/marketplace/<?= e($l['slug']) ?>" class="aho-mp-card">
                        <div class="aho-mp-card__thumb">🛍️</div>
                        <div class="aho-mp-card__body">
                            <div class="aho-mp-card__category"><?= e($l['category_name'] ?? 'Genel') ?></div>
                            <div class="aho-mp-card__title"><?= e($l['title']) ?></div>
                            <div class="aho-mp-card__seller">Satıcı: <?= e(trim(($l['seller_first'] ?? '') . ' ' . ($l['seller_last'] ?? ''))) ?: 'Anonim' ?></div>
                            <div class="aho-mp-card__footer">
                                <span class="aho-mp-card__price"><?= e(\App\Support\Money::displayIn((float)$l['price'], (string)$l['currency'])) ?></span>
                                <span class="aho-btn aho-btn--outline aho-btn--sm">İncele</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php $view->endSection(); ?>
