<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>QA Scan Center</h1>
            <p>Tüm route'ları listeler, gruplar ve method dağılımını gösterir.</p>
        </div>
    </div>

    <div class="aho-stat-grid" style="margin-bottom:var(--aho-space-6)">
        <div class="aho-card aho-stat-card">
            <div class="aho-stat-card__label">Toplam Route</div>
            <div class="aho-stat-card__value"><?= (int) $summary['total_routes'] ?></div>
        </div>
        <div class="aho-card aho-stat-card">
            <div class="aho-stat-card__label">GET</div>
            <div class="aho-stat-card__value"><?= (int) ($summary['by_method']['GET'] ?? 0) ?></div>
        </div>
        <div class="aho-card aho-stat-card">
            <div class="aho-stat-card__label">POST</div>
            <div class="aho-stat-card__value"><?= (int) ($summary['by_method']['POST'] ?? 0) ?></div>
        </div>
        <div class="aho-card aho-stat-card">
            <div class="aho-stat-card__label">Modül Grubu</div>
            <div class="aho-stat-card__value"><?= (int) count($summary['by_group']) ?></div>
        </div>
    </div>

    <div class="aho-admin-grid">
        <div class="aho-card">
            <h3 class="aho-card__title" style="margin-bottom:var(--aho-space-3)">Grup Dağılımı</h3>
            <table class="aho-admin-table">
                <thead><tr><th>Grup</th><th>Route Sayısı</th></tr></thead>
                <tbody>
                    <?php foreach ($summary['by_group'] as $g => $c): ?>
                        <tr><td><?= e($g) ?></td><td><?= (int) $c ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="aho-card">
            <h3 class="aho-card__title" style="margin-bottom:var(--aho-space-3)">Route Listesi</h3>
            <div style="max-height:500px;overflow-y:auto">
                <table class="aho-admin-table">
                    <thead><tr><th>Method</th><th>Path</th><th>Name</th></tr></thead>
                    <tbody>
                        <?php foreach ($routes as $r): ?>
                            <tr>
                                <td><span class="aho-admin-badge aho-admin-badge--info"><?= e($r['method']) ?></span></td>
                                <td style="font-family:monospace;font-size:var(--aho-text-xs)"><?= e($r['path']) ?></td>
                                <td style="font-size:var(--aho-text-xs);color:var(--aho-color-ink-500)"><?= e($r['name'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $view->endSection(); ?>
