<?php $view->extend('layouts.admin'); $view->section('content'); ?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header"><div><h1>Integrations</h1><p>Webhook/Zapier/Make event log.</p></div></div>
    <div class="aho-grid aho-grid--2">
        <section class="aho-card">
            <h2>Webhooks</h2>
            <table class="aho-table"><thead><tr><th>Name</th><th>Event</th><th>Status</th></tr></thead><tbody>
            <?php foreach ($webhooks as $w): ?><tr><td><?= e($w['name']) ?></td><td><?= e($w['event_name']) ?></td><td><?= !empty($w['is_active']) ? 'Active' : 'Paused' ?></td></tr><?php endforeach; ?>
            <?php if (!$webhooks): ?><tr><td colspan="3" style="text-align:center;padding:18px">No webhooks yet.</td></tr><?php endif; ?>
            </tbody></table>
        </section>
        <section class="aho-card">
            <h2>Recent Events</h2>
            <table class="aho-table"><thead><tr><th>Event</th><th>Status</th><th>Date</th></tr></thead><tbody>
            <?php foreach ($events as $e): ?><tr><td><?= e($e['event_name']) ?></td><td><?= e($e['delivery_status']) ?></td><td><?= e($e['created_at'] ?? '') ?></td></tr><?php endforeach; ?>
            <?php if (!$events): ?><tr><td colspan="3" style="text-align:center;padding:18px">No events yet.</td></tr><?php endif; ?>
            </tbody></table>
        </section>
    </div>
</div>
<?php $view->endSection(); ?>
