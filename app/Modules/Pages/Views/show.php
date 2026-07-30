<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-pages-hero">
    <div class="aho-container">
        <h1><?= e($page['title']) ?></h1>
    </div>
</section>

<section class="aho-pages-body">
    <div class="aho-container">
        <article class="aho-prose">
            <?= $view->raw($page['content']) ?>
        </article>
    </div>
</section>
<?php $view->endSection(); ?>
