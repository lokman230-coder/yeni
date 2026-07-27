<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
$success = flash('success');
?>
<section class="aho-customer-page">
    <div class="aho-container" style="max-width:840px">
        <div class="aho-customer-header">
            <div>
                <div style="font-family:var(--aho-font-mono);font-size:var(--aho-text-xs);color:var(--aho-color-ink-500)"><?= e($ticket['ticket_number']) ?></div>
                <h1><?= e($ticket['subject']) ?></h1>
                <div style="margin-top:var(--aho-space-2)">
                    <span class="aho-tkt-badge aho-tkt-badge--<?= e($ticket['priority']) ?>"><?= e($ticket['priority']) ?></span>
                    <span class="aho-tkt-badge aho-tkt-badge--<?= e($ticket['status']) ?>"><?= e($ticket['status']) ?></span>
                </div>
            </div>
            <a href="/panel/destek" class="aho-btn aho-btn--ghost aho-btn--sm">← Talepler</a>
        </div>

        <?php if ($success): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>

        <div class="aho-tkt-thread">
            <?php foreach ($replies as $r): ?>
                <div class="aho-tkt-reply aho-tkt-reply--<?= e($r['author_type']) ?>">
                    <div class="aho-tkt-reply__head">
                        <span class="aho-tkt-reply__author">
                            <?= $r['author_type'] === 'admin' ? '👤 Destek Ekibi' : '👤 Siz' ?>
                        </span>
                        <span><?= e($r['created_at']) ?></span>
                    </div>
                    <div class="aho-tkt-reply__body"><?= nl2br(e($r['message'])) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($attachments)): ?>
            <div class="aho-card" style="padding:16px;margin:16px 0">
                <h4 style="margin:0 0 10px;font-size:14px">📎 Ekli Dosyalar</h4>
                <div style="display:flex;flex-wrap:wrap;gap:8px">
                    <?php foreach ($attachments as $a): ?>
                        <a href="/panel/destek/<?= (int)$ticket['id'] ?>/ek/<?= (int)$a['id'] ?>"
                           style="display:flex;gap:6px;align-items:center;padding:6px 10px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;text-decoration:none;color:inherit;font-size:12px">
                            <?= str_starts_with($a['mime'], 'image/') ? '🖼️' : '📄' ?>
                            <?= e($a['original_name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($ticket['status'] !== 'closed'): ?>
            <form method="post" action="/panel/destek/<?= (int)$ticket['id'] ?>/yanit" class="aho-tkt-reply-form" enctype="multipart/form-data">
                <?= csrf() ?>
                <div class="aho-form-group">
                    <label class="aho-form-label">Yanıtınız</label>
                    <textarea name="message" class="aho-form-textarea" rows="6"></textarea>
                </div>
                <div class="aho-form-group">
                    <label class="aho-form-label">📎 Dosya Eki (opsiyonel — max 10MB)</label>
                    <input type="file" name="attachment" accept="image/*,application/pdf,.txt,.log,.zip,.gz" style="padding:8px;border:1px dashed var(--aho-color-border);border-radius:8px;width:100%">
                    <div style="font-size:12px;color:var(--aho-color-ink-500);margin-top:4px">İzin verilen: resim (png/jpg/gif/webp), PDF, TXT, LOG, ZIP</div>
                </div>
                <button type="submit" class="aho-btn aho-btn--primary">Yanıt Gönder</button>
            </form>
        <?php else: ?>
            <div class="aho-alert aho-alert--info" style="margin-top:var(--aho-space-4)">Bu talep kapatılmıştır.</div>
        <?php endif; ?>
    </div>
</section>
<?php $view->endSection(); ?>
