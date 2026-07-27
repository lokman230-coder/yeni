<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1><?= e($title) ?></h1>
            <p>Bu modül Faz 3-5 arasında geliştirilecektir.</p>
        </div>
        <a href="/admin" class="aho-btn aho-btn--ghost aho-btn--sm">← Kontrol Paneli</a>
    </div>

    <div class="aho-card">
        <div class="aho-empty-state" style="padding:var(--aho-space-12) var(--aho-space-6)">
            <div class="aho-empty-state__icon" style="font-size:64px">🚧</div>
            <div class="aho-empty-state__title"><?= e($title) ?> — Yakında</div>
            <div class="aho-empty-state__text" style="max-width:520px;margin:0 auto">
                Bu modülün geliştirilmesi <strong><?= e($slug) ?></strong> route'unda planlanmıştır.
                Ahost Bilişim geliştirme yol haritasında ilgili faza ulaşıldığında bu sayfa tam işlevli hale gelecektir.
            </div>
            <div style="margin-top:var(--aho-space-6);display:flex;gap:var(--aho-space-2);justify-content:center">
                <a href="/admin" class="aho-btn aho-btn--outline aho-btn--sm">Kontrol Paneli</a>
                <a href="/admin/health-center" class="aho-btn aho-btn--primary aho-btn--sm">Sistem Durumu</a>
            </div>
        </div>
    </div>
</div>
<?php $view->endSection(); ?>
