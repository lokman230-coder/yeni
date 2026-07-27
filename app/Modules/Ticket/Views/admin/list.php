<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>Destek Merkezi</h1>
            <p>Müşteri destek taleplerini yönetin.</p>
        </div>
    </div>

    <div style="margin-bottom:var(--aho-space-4)">
        <a href="/admin/destek-merkezi" class="aho-btn <?= $status === '' ? 'aho-btn--primary' : 'aho-btn--outline' ?> aho-btn--sm">Tümü</a>
        <a href="/admin/destek-merkezi?status=open" class="aho-btn <?= $status === 'open' ? 'aho-btn--primary' : 'aho-btn--outline' ?> aho-btn--sm">Açık</a>
        <a href="/admin/destek-merkezi?status=customer_reply" class="aho-btn <?= $status === 'customer_reply' ? 'aho-btn--primary' : 'aho-btn--outline' ?> aho-btn--sm">Müşteri Yanıtladı</a>
        <a href="/admin/destek-merkezi?status=closed" class="aho-btn <?= $status === 'closed' ? 'aho-btn--primary' : 'aho-btn--outline' ?> aho-btn--sm">Kapalı</a>
    </div>

    <?php if (empty($tickets)): ?>
        <div class="aho-card">
            <div class="aho-empty-state" style="padding:var(--aho-space-8)">
                <div class="aho-empty-state__icon" style="font-size:48px">🎫</div>
                <div class="aho-empty-state__title">Bu filtrede talep yok</div>
            </div>
        </div>
    <?php else: ?>
        <div class="aho-card" style="padding:0;overflow:hidden">
            <table class="aho-admin-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Konu</th>
                        <th>Müşteri</th>
                        <th>Öncelik</th>
                        <th>Durum</th>
                        <th>Son yanıt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $t): ?>
                        <tr>
                            <td><a href="/admin/destek-merkezi/<?= (int)$t['id'] ?>"><?= e($t['ticket_number']) ?></a></td>
                            <td><a href="/admin/destek-merkezi/<?= (int)$t['id'] ?>"><strong><?= e($t['subject']) ?></strong></a></td>
                            <td><?= e(trim(($t['first_name'] ?? '') . ' ' . ($t['last_name'] ?? '')) ?: $t['customer_email']) ?></td>
                            <td><span class="aho-tkt-badge aho-tkt-badge--<?= e($t['priority']) ?>"><?= e($t['priority']) ?></span></td>
                            <td><span class="aho-tkt-badge aho-tkt-badge--<?= e($t['status']) ?>"><?= e($t['status']) ?></span></td>
                            <td style="font-size:var(--aho-text-xs);color:var(--aho-color-ink-500)"><?= e($t['last_reply_at'] ?? $t['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php $view->endSection(); ?>
