<?php $view->extend('layouts.admin'); $view->section('content'); ?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header"><div><h1>SEO Analyzer</h1><p>Run technical SEO checks for any URL.</p></div></div>
    <form class="aho-card" method="post" action="/admin/seo-analyzer/analyze" style="display:flex;gap:10px;align-items:end">
        <?= csrf() ?>
        <div style="flex:1"><label class="aho-form-label">URL</label><input class="aho-form-input" name="url" placeholder="https://example.com" required></div>
        <button class="aho-btn aho-btn--primary">Analyze</button>
    </form>
    <div class="aho-card" style="padding:0;overflow:auto">
        <table class="aho-table">
            <thead><tr><th>URL</th><th>Score</th><th>Findings</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($audits as $a): $findings = json_decode((string)($a['findings_json'] ?? '[]'), true) ?: []; ?>
                <tr><td><?= e($a['url']) ?></td><td><?= (int) $a['score'] ?></td><td><?= e(implode(', ', array_map(fn($f) => $f['message'] ?? '', $findings))) ?></td><td><?= e($a['created_at'] ?? '') ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$audits): ?><tr><td colspan="4" style="text-align:center;padding:24px">No audits yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $view->endSection(); ?>
