<?php $view->extend('layouts.admin'); $view->section('content'); ?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header"><div><h1>Form Builder</h1><p>Forms and recent submissions.</p></div></div>
    <div class="aho-grid aho-grid--2">
        <section class="aho-card">
            <h2>Forms</h2>
            <table class="aho-table"><thead><tr><th>Name</th><th>Status</th><th>Submissions</th></tr></thead><tbody>
            <?php foreach ($forms as $f): ?><tr><td><?= e($f['name']) ?><br><small><?= e($f['slug']) ?></small></td><td><?= e($f['status']) ?></td><td><?= (int) $f['submission_count'] ?></td></tr><?php endforeach; ?>
            <?php if (!$forms): ?><tr><td colspan="3" style="text-align:center;padding:18px">No forms yet.</td></tr><?php endif; ?>
            </tbody></table>
        </section>
        <section class="aho-card">
            <h2>Recent Submissions</h2>
            <table class="aho-table"><thead><tr><th>Form</th><th>Email</th><th>Date</th></tr></thead><tbody>
            <?php foreach ($submissions as $s): ?><tr><td><?= e($s['form_name'] ?? '-') ?></td><td><?= e($s['submitter_email'] ?? '-') ?></td><td><?= e($s['created_at'] ?? '') ?></td></tr><?php endforeach; ?>
            <?php if (!$submissions): ?><tr><td colspan="3" style="text-align:center;padding:18px">No submissions yet.</td></tr><?php endif; ?>
            </tbody></table>
        </section>
    </div>
</div>
<?php $view->endSection(); ?>
