<?php $view->extend('layouts.admin'); $view->section('content'); ?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header"><div><h1>Popup Builder</h1><p>Active campaigns and conversion events.</p></div></div>
    <div class="aho-card" style="padding:0;overflow:auto">
        <table class="aho-table">
            <thead><tr><th>Name</th><th>Trigger</th><th>Status</th><th>Views</th><th>Conversions</th></tr></thead>
            <tbody>
            <?php foreach ($popups as $p): ?><tr><td><?= e($p['name']) ?></td><td><?= e($p['trigger_type']) ?></td><td><?= e($p['status']) ?></td><td><?= (int) $p['view_count'] ?></td><td><?= (int) $p['conversion_count'] ?></td></tr><?php endforeach; ?>
            <?php if (!$popups): ?><tr><td colspan="5" style="text-align:center;padding:24px">No popups yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $view->endSection(); ?>
