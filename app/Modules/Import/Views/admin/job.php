<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
$pct = (int)$job['total'] > 0 ? min(100, (int)round(((int)$job['imported']+(int)$job['skipped']) / (int)$job['total'] * 100)) : 0;
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>🔌 Import Job #<?= (int)$job['id'] ?></h1>
            <p><strong><?= e($job['source']) ?></strong> · <?= e($job['type']) ?> · Oluşturma: <?= e($job['created_at']) ?></p>
        </div>
        <a href="/admin/veri-aktarimi" class="aho-btn aho-btn--ghost">← Listeye Dön</a>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:20px">
        <div class="aho-card" style="padding:16px"><div style="font-size:11px;color:var(--aho-color-ink-500)">TOPLAM</div><div style="font-size:24px;font-weight:700"><?= (int)$job['total'] ?></div></div>
        <div class="aho-card" style="padding:16px"><div style="font-size:11px;color:var(--aho-color-ink-500)">İTHAL</div><div style="font-size:24px;font-weight:700;color:#059669"><?= (int)$job['imported'] ?></div></div>
        <div class="aho-card" style="padding:16px"><div style="font-size:11px;color:var(--aho-color-ink-500)">ATLANDI (duplicate)</div><div style="font-size:24px;font-weight:700;color:#d97706"><?= (int)$job['skipped'] ?></div></div>
        <div class="aho-card" style="padding:16px"><div style="font-size:11px;color:var(--aho-color-ink-500)">HATA</div><div style="font-size:24px;font-weight:700;color:#dc2626"><?= (int)$job['errors'] ?></div></div>
    </div>

    <div class="aho-card" style="padding:20px;margin-bottom:16px">
        <div style="display:flex;justify-content:space-between;margin-bottom:8px">
            <strong>İlerleme: <?= $pct ?>%</strong>
            <span style="color:var(--aho-color-ink-500);font-size:13px">Durum: <?= e($job['status']) ?></span>
        </div>
        <div style="background:#e5e7eb;border-radius:8px;height:12px;overflow:hidden">
            <div style="width:<?= $pct ?>%;height:100%;background:<?= $job['status']==='completed'?'#059669':'#0ea5e9' ?>;transition:width .5s"></div>
        </div>
        <?php if ($job['status'] !== 'completed'): ?>
            <form method="post" action="/admin/veri-aktarimi/is/<?= (int)$job['id'] ?>/calistir" style="margin-top:14px">
                <?= csrf() ?>
                <button type="submit" class="aho-btn aho-btn--primary">▶ 50 kayıt daha işle</button>
                <span style="font-size:12px;color:var(--aho-color-ink-500);margin-left:8px">Her tıklamada 50 kayıt eklenir. Büyük tabanlarda birkaç kez tıklayın.</span>
            </form>
        <?php endif; ?>
    </div>

    <?php if (!empty($job['error_log'])): ?>
    <div class="aho-card" style="padding:20px">
        <h3 style="margin-top:0;font-size:14px;color:#dc2626">❌ Hata Log</h3>
        <pre style="background:#111827;color:#fca5a5;padding:16px;border-radius:8px;font-size:12px;max-height:400px;overflow:auto;margin:0"><?= e($job['error_log']) ?></pre>
    </div>
    <?php endif; ?>
</div>
<?php $view->endSection(); ?>
