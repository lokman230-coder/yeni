<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
$success = flash('success');
?>
<section class="aho-customer-page">
    <div class="aho-container">
        <div class="aho-customer-header">
            <div>
                <h1>Destek Taleplerim</h1>
                <p class="aho-customer-header__welcome">Talepleriniz burada listelenir.</p>
            </div>
            <a href="/panel/destek/yeni" class="aho-btn aho-btn--primary">➕ Yeni Talep</a>
        </div>

        <?php if ($success): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>

        <?php if (empty($tickets)): ?>
            <div class="aho-card">
                <div class="aho-empty-state" style="padding:var(--aho-space-12)">
                    <div class="aho-empty-state__icon" style="font-size:56px">🎫</div>
                    <div class="aho-empty-state__title">Hiç talebiniz yok</div>
                    <div class="aho-empty-state__text">"Yeni Talep" butonu ile ilk talebinizi oluşturun.</div>
                </div>
            </div>
        <?php else: ?>
            <div class="aho-tkt-list">
                <?php foreach ($tickets as $t): ?>
                    <a href="/panel/destek/<?= (int)$t['id'] ?>" class="aho-tkt-item">
                        <div>
                            <span class="aho-tkt-item__number"><?= e($t['ticket_number']) ?></span>
                            <span class="aho-tkt-item__subject"><?= e($t['subject']) ?></span>
                            <div class="aho-tkt-item__meta">
                                <?= e($t['dept_name'] ?? 'Genel') ?>
                                · <?= e($t['created_at']) ?>
                            </div>
                        </div>
                        <div>
                            <span class="aho-tkt-badge aho-tkt-badge--<?= e($t['priority']) ?>"><?= e($t['priority']) ?></span>
                            <span class="aho-tkt-badge aho-tkt-badge--<?= e($t['status']) ?>"><?= e($t['status']) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php $view->endSection(); ?>
