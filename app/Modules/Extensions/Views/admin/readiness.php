<?php $view->extend('layouts.admin'); $view->section('content'); ?>
<?php
$summary = $report['summary'] ?? ['ok' => 0, 'warning' => 0, 'missing' => 0];
$items = $report['items'] ?? [];
$badge = static function (string $status): string {
    $colors = [
        'ok' => 'background:#dcfce7;color:#166534',
        'warning' => 'background:#fef3c7;color:#92400e',
        'missing' => 'background:#fee2e2;color:#991b1b',
    ];
    return '<span style="display:inline-flex;align-items:center;padding:4px 9px;border-radius:999px;font-size:12px;font-weight:700;' . ($colors[$status] ?? '') . '">' . e(strtoupper($status)) . '</span>';
};
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>Production Readiness</h1>
            <p>Canliya almadan once entegrasyon, migrasyon ve kritik ayar kontrolu.</p>
        </div>
        <a href="/admin/api/production-readiness" class="aho-btn aho-btn--ghost">JSON</a>
    </div>

    <div class="aho-grid aho-grid--3" style="margin-bottom:16px">
        <section class="aho-card"><h2>OK</h2><div style="font-size:30px;font-weight:800;color:#166534"><?= (int)($summary['ok'] ?? 0) ?></div></section>
        <section class="aho-card"><h2>Warning</h2><div style="font-size:30px;font-weight:800;color:#92400e"><?= (int)($summary['warning'] ?? 0) ?></div></section>
        <section class="aho-card"><h2>Missing</h2><div style="font-size:30px;font-weight:800;color:#991b1b"><?= (int)($summary['missing'] ?? 0) ?></div></section>
    </div>

    <section class="aho-card">
        <table class="aho-table">
            <thead>
                <tr>
                    <th>Area</th>
                    <th>Group</th>
                    <th>Status</th>
                    <th>Message</th>
                    <th>Required action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td style="font-weight:700"><?= e($item['name'] ?? '') ?></td>
                    <td><?= e($item['group'] ?? '') ?></td>
                    <td><?= $badge((string)($item['status'] ?? 'missing')) ?></td>
                    <td><?= e($item['message'] ?? '') ?></td>
                    <td>
                        <?php $actions = (array)($item['actions'] ?? []); ?>
                        <?= $actions ? e(implode(', ', $actions)) : '-' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$items): ?>
                <tr><td colspan="5" style="text-align:center;padding:18px">No readiness data.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        <p style="margin-top:12px;color:var(--aho-color-ink-500);font-size:13px">Generated at: <?= e($report['generated_at'] ?? '') ?></p>
    </section>
</div>
<?php $view->endSection(); ?>
