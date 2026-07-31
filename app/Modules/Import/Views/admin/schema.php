<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>🔧 Import Şema Kontrolü</h1>
            <p>Veri aktarımının ihtiyaç duyduğu sütunlar hedef veritabanında var mı, burada görürsün.</p>
        </div>
        <div class="aho-admin-page__actions">
            <a href="/admin/veri-aktarimi" class="aho-btn aho-btn--ghost">← Veri Aktarımı</a>
        </div>
    </div>

    <?php if ($success = flash('success')): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error = flash('error')): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

    <div class="aho-card">
        <h3>Durum</h3>
        <div class="aho-table-wrap"><table class="aho-table">
            <thead><tr><th>Tablo</th><th>imported_from</th><th>external_id</th><th>Durum</th></tr></thead>
            <tbody>
            <?php foreach ($report as $r): ?>
                <tr>
                    <td><code><?= e($r['table']) ?></code></td>
                    <td><?= !$r['exists'] ? '—' : ($r['has_imported_from'] ? '✅' : '❌ eksik') ?></td>
                    <td><?= !$r['exists'] ? '—' : ($r['has_external_id'] ? '✅' : '❌ eksik') ?></td>
                    <td>
                        <?php if (!$r['exists']): ?>
                            <span class="aho-badge" style="background:#f3f4f6;color:#6b7280">tablo yok</span>
                        <?php elseif ($r['has_imported_from'] && $r['has_external_id']): ?>
                            <span class="aho-badge" style="background:#d1fae5;color:#059669">tamam</span>
                        <?php else: ?>
                            <span class="aho-badge" style="background:#fee2e2;color:#dc2626">eksik</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>

        <form method="post" action="/admin/veri-aktarimi/sema-kontrol/otomatik-onar" style="margin-top:14px" onsubmit="return confirm('Eksik sütunlar (varsa) güvenli/nullable şekilde otomatik eklenecek. Devam?')">
            <?= csrf() ?>
            <button class="aho-btn aho-btn--primary">🩹 Eksikleri Otomatik Onar</button>
        </form>
    </div>

    <div class="aho-card" style="margin-top:16px">
        <h3>Elle Sütun Ekle</h3>
        <p style="font-size:13px;color:var(--aho-color-ink-500)">
            Otomatik onarım yetmezse (örn. import başka bir sütuna daha ihtiyaç duyuyorsa), burada elle ekleyebilirsin.
            Sadece <strong>yeni, boş bırakılabilir (NULL) bir sütun</strong> eklenir — mevcut veriye dokunulmaz, tablo silinmez.
        </p>
        <form method="post" action="/admin/veri-aktarimi/sema-kontrol/elle-ekle" class="aho-admin-form-row aho-admin-form-row--3" onsubmit="return confirm('Bu sütun eklenecek. Emin misin?')">
            <?= csrf() ?>
            <div>
                <label>Tablo</label>
                <select name="table" class="aho-form-select" required>
                    <?php foreach ($allTables as $t): ?>
                        <option value="<?= e($t) ?>"><?= e($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Sütun Adı</label><input type="text" name="column" class="aho-form-input" required pattern="[a-zA-Z_][a-zA-Z0-9_]*" placeholder="orn_sutun_adi"></div>
            <div>
                <label>Tip</label>
                <select name="type" class="aho-form-select">
                    <option value="VARCHAR">VARCHAR (kısa metin)</option>
                    <option value="TEXT">TEXT (uzun metin)</option>
                    <option value="INT">INT (tam sayı)</option>
                    <option value="BIGINT">BIGINT (büyük tam sayı)</option>
                    <option value="DECIMAL">DECIMAL (ondalıklı, 14,4)</option>
                    <option value="DATE">DATE</option>
                    <option value="DATETIME">DATETIME</option>
                    <option value="TINYINT">TINYINT (bool/küçük sayı)</option>
                    <option value="JSON">JSON</option>
                </select>
            </div>
            <div><label>Uzunluk (sadece VARCHAR için)</label><input type="number" name="length" class="aho-form-input" value="191" min="1" max="1000"></div>
            <div style="grid-column:1/-1"><button class="aho-btn aho-btn--outline">+ Sütunu Ekle</button></div>
        </form>
    </div>
</div>
<?php $view->endSection(); ?>
