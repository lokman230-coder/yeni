<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
// View::renderFile içinde extract($data) yapıyor; parameter değişken çakışmasını önlemek için 'plan' key kullanıyoruz.
$d = $plan_data ?? $data;
?>
<section class="aho-pages-hero">
    <div class="aho-container">
        <h1><?= e($d['title']) ?></h1>
        <p style="color:var(--aho-color-ink-500);margin-top:var(--aho-space-2);font-size:var(--aho-text-lg)"><?= e($d['subtitle']) ?></p>
    </div>
</section>

<section class="aho-pages-body">
    <div class="aho-container">
        <div class="aho-plan-grid">
            <?php foreach ($d['plans'] as $plan): ?>
                <div class="aho-plan-card <?= !empty($plan['popular']) ? 'is-popular' : '' ?>">
                    <?php if (!empty($plan['popular'])): ?>
                        <div class="aho-plan-card__badge">EN POPÜLER</div>
                    <?php endif; ?>
                    <div class="aho-plan-card__name"><?= e($plan['name']) ?></div>
                    <div class="aho-plan-card__price">
                        <span class="aho-plan-card__amount"><?= number_format((float)$plan['price'], 0, ',', '.') ?></span>
                        <span class="aho-plan-card__unit">₺ / ay</span>
                    </div>
                    <ul class="aho-plan-card__features">
                        <?php foreach ($plan['features'] as $f): ?>
                            <li>✓ <?= e($f) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="/sepet" class="aho-btn <?= !empty($plan['popular']) ? 'aho-btn--accent' : 'aho-btn--outline' ?> aho-btn--lg aho-btn--block">Hemen Al</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
