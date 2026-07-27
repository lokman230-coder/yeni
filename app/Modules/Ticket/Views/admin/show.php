<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
$success = flash('success');

$priorityLabel = ['low'=>'Düşük','medium'=>'Orta','high'=>'Yüksek','urgent'=>'Acil'];
$priorityColor = ['low'=>'#6b7280','medium'=>'#0891b2','high'=>'#d97706','urgent'=>'#dc2626'];
$statusLabel = ['open'=>'Açık','answered'=>'Cevaplandı','customer_reply'=>'Müşteri Cevapladı','on_hold'=>'Beklemede','closed'=>'Kapalı'];
$statusColor = ['open'=>'#059669','answered'=>'#0891b2','customer_reply'=>'#d97706','on_hold'=>'#6b7280','closed'=>'#6b7280'];
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <div style="font-family:monospace;font-size:11px;color:var(--aho-color-ink-500)"><?= e($ticket['ticket_number']) ?></div>
            <h1 style="margin:4px 0"><?= e($ticket['subject']) ?></h1>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:8px;font-size:13px;color:var(--aho-color-ink-600)">
                👤 <?= e(trim(($ticket['first_name'] ?? '') . ' ' . ($ticket['last_name'] ?? '')) ?: ($ticket['customer_email'] ?? '—')) ?>
                <?php if (!empty($ticket['customer_email'])): ?>· <?= e($ticket['customer_email']) ?><?php endif; ?>
                · <?= e(date('d.m.Y H:i', strtotime((string)$ticket['created_at']))) ?>
            </div>
        </div>
        <a href="/admin/destek-merkezi" class="aho-btn aho-btn--ghost">← Talepler</a>
    </div>

    <?php if ($success): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 280px;gap:20px;align-items:flex-start">
        <!-- Thread -->
        <div>
            <div class="aho-card" style="padding:0">
                <?php foreach ($replies as $r):
                    $isAdmin = $r['author_type'] === 'admin';
                    $isSystem = $r['author_type'] === 'system';
                    $isInternal = (int)($r['is_internal'] ?? 0) === 1;
                ?>
                    <div style="padding:20px;border-bottom:1px solid var(--aho-color-border);
                                <?= $isInternal ? 'background:#fef3c7' : ($isAdmin ? 'background:#f0f9ff' : 'background:#fff') ?>">
                        <div style="display:flex;justify-content:space-between;gap:12px;margin-bottom:10px;font-size:13px">
                            <div style="display:flex;gap:8px;align-items:center;font-weight:600">
                                <?php if ($isSystem): ?>
                                    ⚙️ <span style="color:#6b7280">Sistem</span>
                                <?php elseif ($isAdmin): ?>
                                    🎧 <span style="color:#0891b2">Destek Ekibi</span>
                                <?php else: ?>
                                    👤 <span>Müşteri</span>
                                <?php endif; ?>
                                <?php if ($isInternal): ?>
                                    <span style="padding:2px 8px;font-size:10px;background:#fbbf24;color:#78350f;border-radius:8px;font-weight:700">İÇ NOT — MÜŞTERİ GÖRMEZ</span>
                                <?php endif; ?>
                            </div>
                            <div style="color:var(--aho-color-ink-500);font-size:12px"><?= e(date('d.m.Y H:i', strtotime((string)$r['created_at']))) ?></div>
                        </div>
                        <div style="white-space:pre-wrap;line-height:1.6;color:var(--aho-color-ink-800)"><?= nl2br(e($r['message'])) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($attachments)): ?>
                <div class="aho-card" style="padding:16px;margin-top:16px">
                    <h4 style="margin:0 0 12px;font-size:14px">📎 Ekli Dosyalar (<?= count($attachments) ?>)</h4>
                    <div style="display:flex;flex-wrap:wrap;gap:8px">
                        <?php foreach ($attachments as $a): ?>
                            <a href="/admin/destek-merkezi/<?= (int)$ticket['id'] ?>/ek/<?= (int)$a['id'] ?>"
                               style="display:flex;gap:8px;align-items:center;padding:8px 12px;background:#f9fafb;border:1px solid var(--aho-color-border);border-radius:8px;text-decoration:none;color:inherit;font-size:12px">
                                <span style="font-size:16px"><?= str_starts_with($a['mime'], 'image/') ? '🖼️' : '📄' ?></span>
                                <span><?= e($a['original_name']) ?></span>
                                <span style="color:var(--aho-color-ink-500)">(<?= number_format($a['size_bytes']/1024, 0) ?> KB)</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($ticket['status'] !== 'closed'): ?>
                <form method="post" action="/admin/destek-merkezi/<?= (int)$ticket['id'] ?>/yanit" class="aho-card" style="padding:20px;margin-top:16px" enctype="multipart/form-data">
                    <?= csrf() ?>
                    <div style="display:flex;gap:12px;align-items:center;margin-bottom:10px;font-size:13px">
                        <label style="display:flex;gap:6px;align-items:center;cursor:pointer">
                            <input type="radio" name="is_internal" value="0" checked>
                            💬 Müşteriye Yanıt
                        </label>
                        <label style="display:flex;gap:6px;align-items:center;cursor:pointer">
                            <input type="radio" name="is_internal" value="1">
                            📝 İç Not (sadece admin)
                        </label>
                    </div>
                    <textarea id="ticketReplyBox" name="message" rows="6" placeholder="Yanıtınızı yazın..."
                              style="width:100%;padding:12px;border:1px solid var(--aho-color-border);border-radius:8px;font-family:inherit;font-size:14px;resize:vertical;box-sizing:border-box"></textarea>
                    <button type="button" onclick="aiSuggestReply(<?= (int)$ticket['id'] ?>)" style="margin-top:6px;padding:6px 12px;background:linear-gradient(135deg,#8b5cf6,#ec4899);color:#fff;border:0;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600">🤖 AI Cevap Önerisi</button>

                    <script>
                    async function aiSuggestReply(tid) {
                        const btn = event.target;
                        btn.disabled = true; const orig = btn.textContent; btn.textContent = '⏳ Düşünüyor...';
                        try {
                            const r = await fetch('/admin/api/ai/ticket-reply', {
                                method: 'POST',
                                headers: {'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':'<?= csrf_token() ?>','Accept':'application/json'},
                                body: '_csrf=<?= csrf_token() ?>&ticket_id=' + tid
                            });
                            const d = await r.json();
                            if (!d.ok) { alert('Hata: ' + (d.error || '')); return; }
                            document.getElementById('ticketReplyBox').value = d.suggestion || '';
                            btn.textContent = '✓ (' + d.provider + ') — düzenleyip gönder';
                            setTimeout(() => { btn.textContent = orig; btn.disabled = false; }, 3000);
                        } catch(e) { alert('İstek hatası: ' + e.message); btn.disabled = false; btn.textContent = orig; }
                    }
                    </script>
                    <div style="margin-top:10px">
                        <label style="font-size:12px;color:var(--aho-color-ink-600);font-weight:600">📎 Dosya Eki (max 10MB)</label>
                        <input type="file" name="attachment" accept="image/*,application/pdf,.txt,.log,.zip,.gz" style="display:block;margin-top:4px;padding:6px;border:1px dashed var(--aho-color-border);border-radius:6px;width:100%">
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px">
                        <button type="submit" class="aho-btn aho-btn--primary">📤 Gönder</button>
                        <button type="submit" name="close" value="1" style="padding:10px 16px;background:#f0fdf4;color:#065f46;border:1px solid #a7f3d0;border-radius:8px;font-weight:600;cursor:pointer">✓ Yanıtla + Kapat</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="aho-alert" style="padding:16px;background:#f3f4f6;color:#6b7280;margin-top:16px;text-align:center">
                    Bu talep kapatıldı. Yeni bir yanıt için müşteri talebi yeniden açabilir.
                </div>
            <?php endif; ?>
        </div>

        <!-- Yan panel -->
        <div>
            <div class="aho-card" style="padding:16px;margin-bottom:12px">
                <h4 style="margin:0 0 12px;font-size:14px;color:var(--aho-color-ink-700)">DURUM</h4>
                <form method="post" action="/admin/destek-merkezi/<?= (int)$ticket['id'] ?>/guncelle">
                    <?= csrf() ?>
                    <select name="status" onchange="this.form.submit()" style="width:100%;padding:8px;border:1px solid var(--aho-color-border);border-radius:6px;font-weight:600;color:<?= $statusColor[$ticket['status']] ?? '#111' ?>">
                        <?php foreach ($statusLabel as $k => $v): ?>
                            <option value="<?= $k ?>" <?= $ticket['status']===$k?'selected':'' ?>><?= e($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <div class="aho-card" style="padding:16px;margin-bottom:12px">
                <h4 style="margin:0 0 12px;font-size:14px;color:var(--aho-color-ink-700)">ÖNCELİK</h4>
                <form method="post" action="/admin/destek-merkezi/<?= (int)$ticket['id'] ?>/guncelle">
                    <?= csrf() ?>
                    <select name="priority" onchange="this.form.submit()" style="width:100%;padding:8px;border:1px solid var(--aho-color-border);border-radius:6px;font-weight:600;color:<?= $priorityColor[$ticket['priority']] ?? '#111' ?>">
                        <?php foreach ($priorityLabel as $k => $v): ?>
                            <option value="<?= $k ?>" <?= $ticket['priority']===$k?'selected':'' ?>><?= e($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <div class="aho-card" style="padding:16px">
                <h4 style="margin:0 0 12px;font-size:14px;color:var(--aho-color-ink-700)">DETAY</h4>
                <div style="font-size:13px;line-height:1.9">
                    <div><strong>No:</strong> <code><?= e($ticket['ticket_number']) ?></code></div>
                    <div><strong>Oluşturma:</strong> <?= e(date('d.m.Y H:i', strtotime((string)$ticket['created_at']))) ?></div>
                    <?php if (!empty($ticket['last_reply_at'])): ?>
                        <div><strong>Son yanıt:</strong> <?= e(date('d.m.Y H:i', strtotime((string)$ticket['last_reply_at']))) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($ticket['closed_at'])): ?>
                        <div><strong>Kapatıldı:</strong> <?= e(date('d.m.Y H:i', strtotime((string)$ticket['closed_at']))) ?></div>
                    <?php endif; ?>
                    <div><strong>Toplam mesaj:</strong> <?= count($replies) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $view->endSection(); ?>
