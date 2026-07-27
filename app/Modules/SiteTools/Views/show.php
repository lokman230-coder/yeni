<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-pages-hero" style="padding-block:var(--aho-space-8)">
    <div class="aho-container">
        <div style="text-align:left"><a href="/site-araclari" style="color:var(--aho-color-ink-500);font-size:var(--aho-text-sm)">← Tüm Araçlar</a></div>
        <h1 style="margin-top:var(--aho-space-2)"><?= $tool->icon() ?> <?= e($tool->label()) ?></h1>
        <p style="color:var(--aho-color-ink-500);margin-top:var(--aho-space-2)"><?= e($tool->description()) ?></p>
    </div>
</section>

<section class="aho-pages-body">
    <div class="aho-container" style="max-width:900px">
        <form method="get" class="aho-home-search" style="margin-bottom:var(--aho-space-6);box-shadow:var(--aho-shadow-md)">
            <input type="text" name="q" class="aho-home-search__input"
                   value="<?= e($input) ?>"
                   placeholder="<?= e($tool->inputPlaceholder()) ?>" required autofocus>
            <button type="submit" class="aho-btn aho-btn--primary aho-btn--lg">Analiz Et</button>
        </form>

        <?php if ($result): ?>
            <?php if (!($result['success'] ?? true)): ?>
                <div class="aho-alert aho-alert--danger">
                    Hata: <?= e($result['error'] ?? 'Bilinmeyen') ?>
                </div>
            <?php else: ?>
                <?php
                $render = $result['render'] ?? 'default';
                $data = $result['data'] ?? [];
                include __DIR__ . '/_result.php';
                ?>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Diğer araçlar -->
        <div style="margin-top:var(--aho-space-12)">
            <h3 style="margin-bottom:var(--aho-space-4);font-size:var(--aho-text-lg);color:var(--aho-color-ink-700)">Diğer Araçlar</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:var(--aho-space-2)">
                <?php foreach ($tools as $t): if ($t->slug() === $tool->slug()) continue; ?>
                    <a href="/site-araclari/<?= e($t->slug()) ?>" class="aho-tool" style="text-align:left;padding:var(--aho-space-3)">
                        <?= $t->icon() ?> <?= e($t->label()) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
