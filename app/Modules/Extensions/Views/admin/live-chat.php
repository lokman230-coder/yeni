<?php $view->extend('layouts.admin'); $view->section('content'); ?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header"><div><h1>Live Chat</h1><p>Visitor conversations and support replies.</p></div></div>
    <div class="aho-card" style="padding:0;overflow:auto">
        <table class="aho-table">
            <thead><tr><th>#</th><th>Visitor</th><th>Status</th><th>Last message</th><th>Reply</th></tr></thead>
            <tbody>
            <?php foreach ($conversations as $c): ?>
                <tr>
                    <td>#<?= (int) $c['id'] ?></td>
                    <td><?= e($c['visitor_name'] ?: '-') ?><br><small><?= e($c['visitor_email'] ?: $c['visitor_ip']) ?></small></td>
                    <td><?= e($c['status']) ?></td>
                    <td><?= e($c['last_message'] ?? '') ?><br><small><?= e($c['last_message_at'] ?? $c['created_at'] ?? '') ?></small></td>
                    <td>
                        <form method="post" action="/admin/live-chat/<?= (int) $c['id'] ?>/reply" style="display:flex;gap:8px">
                            <?= csrf() ?>
                            <input class="aho-form-input" name="message" placeholder="Reply..." required>
                            <button class="aho-btn aho-btn--primary">Send</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$conversations): ?><tr><td colspan="5" style="text-align:center;padding:24px">No conversations yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $view->endSection(); ?>
