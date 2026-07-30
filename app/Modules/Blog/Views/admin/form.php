<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
$isEdit = $post !== null;
$p = $post ?? [];
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>✍️ <?= $isEdit ? e($p['title']) : 'Yeni Blog Yazısı' ?></h1>
            <?php if ($isEdit): ?>
                <p style="font-size:13px;color:var(--aho-color-ink-500)">/<?= e($p['slug']) ?> · <?= (int)$p['views'] ?> görüntülenme</p>
            <?php else: ?>
                <p>AI ile hızlı taslak oluşturun, sonra düzenleyin.</p>
            <?php endif; ?>
        </div>
        <a href="/admin/blog" class="aho-btn aho-btn--ghost">← Listeye Dön</a>
    </div>

    <?php if (!empty($error)): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>
    <?php if (!empty($success)): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>

    <form method="post" action="<?= $isEdit ? '/admin/blog/'.(int)$p['id'].'/kaydet' : '/admin/blog/kaydet' ?>">
        <?= csrf() ?>

        <!-- AI Taslak Üretici -->
        <?php if (!$isEdit): ?>
        <div class="aho-card" style="padding:20px;margin-bottom:16px;background:linear-gradient(135deg,#fef3c7 0%,#fef9e7 100%);border-left:4px solid #d97706">
            <h3 style="margin-top:0;font-size:15px">🤖 AI ile Hızlı Taslak Oluştur</h3>
            <p style="color:var(--aho-color-ink-700);font-size:13px;margin:6px 0 12px">Konu ver, AI 30 saniyede taslak yazsın. Sonra düzenlersin.</p>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <input type="text" id="aiTopic" placeholder="Konu (ör: 'WordPress site hızlandırma')"
                       style="flex:1;min-width:280px;padding:10px;border:1px solid var(--aho-color-border);border-radius:8px">
                <input type="text" id="aiAngle" placeholder="Açı (opsiyonel: 'başlangıç seviye')"
                       style="flex:1;min-width:200px;padding:10px;border:1px solid var(--aho-color-border);border-radius:8px">
                <button type="button" onclick="aiGenerateBlog()" style="padding:10px 20px;background:linear-gradient(135deg,#8b5cf6,#ec4899);color:#fff;border:0;border-radius:8px;font-weight:600;cursor:pointer">🤖 Üret</button>
            </div>
        </div>
        <?php endif; ?>

        <div class="aho-card" style="padding:24px;margin-bottom:16px">
            <div class="aho-form-group">
                <label class="aho-form-label">Başlık *</label>
                <input type="text" name="title" id="postTitle" required class="aho-form-input" value="<?= e($p['title'] ?? '') ?>" style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:8px;font-size:16px;font-weight:600;box-sizing:border-box">
            </div>

            <div class="aho-form-group" style="margin-top:12px">
                <label class="aho-form-label">Kısa Özet</label>
                <textarea name="excerpt" id="postExcerpt" rows="2" placeholder="140 karakterlik özet — arama sonuçlarında görünür" style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:8px;font-family:inherit;resize:vertical;box-sizing:border-box"><?= e($p['excerpt'] ?? '') ?></textarea>
            </div>

            <div class="aho-form-group" style="margin-top:12px">
                <label class="aho-form-label">İçerik (HTML destekli) *</label>
                <textarea name="body_html" id="postBody" rows="18" required style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:8px;font-family:'JetBrains Mono',ui-monospace,monospace;font-size:13px;resize:vertical;box-sizing:border-box"><?= e($p['body_html'] ?? '') ?></textarea>
                <div style="font-size:11px;color:var(--aho-color-ink-500);margin-top:4px">HTML: &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;a&gt; etiketleri kullanılabilir.</div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-top:12px">
                <div class="aho-form-group">
                    <label class="aho-form-label">Kategori</label>
                    <input type="text" name="category" value="<?= e($p['category'] ?? '') ?>" placeholder="Hosting, Domain..." style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:8px;box-sizing:border-box">
                </div>
                <div class="aho-form-group">
                    <label class="aho-form-label">Etiketler</label>
                    <input type="text" name="tags" value="<?= e($p['tags'] ?? '') ?>" placeholder="virgülle ayırın" style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:8px;box-sizing:border-box">
                </div>
                <div class="aho-form-group">
                    <label class="aho-form-label">Durum</label>
                    <select name="status" style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:8px">
                        <option value="draft"     <?= ($p['status'] ?? 'draft') === 'draft'     ? 'selected' : '' ?>>📝 Taslak</option>
                        <option value="published" <?= ($p['status'] ?? '')      === 'published' ? 'selected' : '' ?>>✅ Yayında</option>
                        <option value="archived"  <?= ($p['status'] ?? '')      === 'archived'  ? 'selected' : '' ?>>📦 Arşiv</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- SEO -->
        <div class="aho-card" style="padding:24px;margin-bottom:16px">
            <h3 style="margin-top:0;font-size:15px;display:flex;justify-content:space-between;align-items:center">
                <span>🔍 SEO</span>
                <button type="button" onclick="aiFillSeo()" style="padding:4px 10px;background:linear-gradient(135deg,#8b5cf6,#ec4899);color:#fff;border:0;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer">🤖 AI ile Öner</button>
            </h3>
            <div class="aho-form-group">
                <label class="aho-form-label">SEO Başlık</label>
                <input type="text" name="seo_title" id="seoTitle" value="<?= e($p['seo_title'] ?? '') ?>" maxlength="60" style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:8px;box-sizing:border-box">
            </div>
            <div class="aho-form-group" style="margin-top:12px">
                <label class="aho-form-label">SEO Açıklama</label>
                <textarea name="seo_description" id="seoDesc" rows="2" maxlength="160" style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:8px;font-family:inherit;box-sizing:border-box"><?= e($p['seo_description'] ?? '') ?></textarea>
            </div>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center">
            <div>
                <?php if ($isEdit): ?>
                    <form method="post" action="/admin/blog/<?= (int)$p['id'] ?>/sil" style="display:inline" onsubmit="return confirm('Silinsin mi?')">
                        <?= csrf() ?>
                        <button type="submit" style="padding:10px 16px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:8px;cursor:pointer;font-weight:600">🗑️ Sil</button>
                    </form>
                <?php endif; ?>
            </div>
            <button type="submit" class="aho-btn aho-btn--primary">💾 <?= $isEdit ? 'Kaydet' : 'Oluştur' ?></button>
        </div>
    </form>
</div>

<script>
async function aiGenerateBlog() {
    const topic = document.getElementById('aiTopic').value.trim();
    const angle = document.getElementById('aiAngle').value.trim();
    if (!topic) { alert('Konu girin.'); return; }
    const btn = event.target;
    btn.disabled = true; const orig = btn.textContent; btn.textContent = '⏳ Yazıyor (10-20 sn)...';
    try {
        const r = await fetch('/admin/api/ai/blog', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':'<?= csrf_token() ?>','Accept':'application/json'},
            body: '_csrf=<?= csrf_token() ?>&topic=' + encodeURIComponent(topic) + '&angle=' + encodeURIComponent(angle)
        });
        const d = await r.json();
        if (!d.ok) { alert('Hata: ' + (d.error || '')); return; }
        document.getElementById('postTitle').value = d.title || topic;
        document.getElementById('postExcerpt').value = d.excerpt || '';
        document.getElementById('postBody').value = d.body_html || '';
    } catch(e) { alert('İstek hatası: ' + e.message); }
    finally { btn.disabled = false; btn.textContent = orig; }
}

async function aiFillSeo() {
    const title = document.getElementById('postTitle').value.trim();
    const content = document.getElementById('postBody').value;
    if (!title) { alert('Önce başlık girin.'); return; }
    const btn = event.target;
    btn.disabled = true; const orig = btn.textContent; btn.textContent = '⏳';
    try {
        const r = await fetch('/admin/api/ai/seo', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':'<?= csrf_token() ?>','Accept':'application/json'},
            body: '_csrf=<?= csrf_token() ?>&title=' + encodeURIComponent(title) + '&content=' + encodeURIComponent(content.substring(0, 500))
        });
        const d = await r.json();
        if (d.ok) {
            document.getElementById('seoTitle').value = d.title || '';
            document.getElementById('seoDesc').value = d.description || '';
        }
    } catch(e) { alert('Hata: ' + e.message); }
    finally { btn.disabled = false; btn.textContent = orig; }
}
</script>
<?php $view->endSection(); ?>
