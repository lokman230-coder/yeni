<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
$isEdit = $server !== null;
$s = $server ?? [];
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>🖥️ <?= $isEdit ? e($s['name']) : 'Yeni Sunucu' ?></h1>
            <p><?= $isEdit ? 'Sunucu bilgilerini düzenle veya bağlantıyı test et.' : 'Yeni hosting sunucusu ekle.' ?></p>
        </div>
        <a href="/admin/hosting-sunucu" class="aho-btn aho-btn--ghost">← Listeye Dön</a>
    </div>

    <?php if (!empty($success)): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

    <form method="post" action="<?= $isEdit ? '/admin/hosting-sunucu/'.(int)$s['id'].'/kaydet' : '/admin/hosting-sunucu/kaydet' ?>">
        <?= csrf() ?>

        <div class="aho-card" style="padding:24px;margin-bottom:16px">
            <h3 style="margin-top:0">Genel</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Sunucu Adı *</label>
                    <input type="text" name="name" required value="<?= e($s['name'] ?? '') ?>" placeholder="server1.ahost.local" style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Hostname *</label>
                    <input type="text" name="hostname" required value="<?= e($s['hostname'] ?? '') ?>" placeholder="server1.example.com" style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px;font-family:monospace;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">IP</label>
                    <input type="text" name="ip" value="<?= e($s['ip'] ?? '') ?>" placeholder="1.2.3.4" style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px;font-family:monospace;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Server Group</label>
                    <input type="text" name="server_group" value="<?= e($s['server_group'] ?? '') ?>" placeholder="tr-shared" style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px;box-sizing:border-box">
                </div>
            </div>
        </div>

        <div class="aho-card" style="padding:24px;margin-bottom:16px">
            <h3 style="margin-top:0">Panel & Bağlantı</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Panel</label>
                    <select name="panel" style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px" onchange="updatePort(this.value)">
                        <?php foreach (['cpanel'=>'cPanel/WHM','da'=>'DirectAdmin','plesk'=>'Plesk','manual'=>'Manuel'] as $k=>$v): ?>
                            <option value="<?= $k ?>" <?= ($s['panel'] ?? 'cpanel')===$k?'selected':'' ?>><?= e($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Port</label>
                    <input type="number" name="port" id="serverPort" value="<?= e($s['port'] ?? 2087) ?>" style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Kullanıcı Adı</label>
                    <input type="text" name="username" value="<?= e($s['username'] ?? '') ?>" placeholder="root" style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px;font-family:monospace;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Şifre <?= $isEdit ? '(boş → değişmez)' : '' ?></label>
                    <input type="password" name="password" placeholder="<?= $isEdit ? '••••••••' : '' ?>" autocomplete="new-password" style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px;box-sizing:border-box">
                </div>
                <div style="grid-column:1/-1">
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">API Key / Token <?= $isEdit ? '(boş → değişmez)' : '' ?></label>
                    <input type="password" name="api_key" placeholder="<?= $isEdit ? '••••••••' : 'cPanel API token veya Plesk API key' ?>" autocomplete="new-password" style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px;font-family:monospace;box-sizing:border-box">
                </div>
            </div>

            <div style="display:flex;gap:20px;margin-top:16px">
                <label style="display:flex;gap:6px;align-items:center;font-size:13px;cursor:pointer">
                    <input type="checkbox" name="use_ssl" value="1" <?= (int)($s['use_ssl'] ?? 1)===1?'checked':'' ?>>
                    SSL/TLS Kullan
                </label>
                <label style="display:flex;gap:6px;align-items:center;font-size:13px;cursor:pointer">
                    <input type="checkbox" name="is_active" value="1" <?= (int)($s['is_active'] ?? 1)===1?'checked':'' ?>>
                    Aktif (yeni siparişler bu sunucuya yönlenebilir)
                </label>
            </div>
        </div>

        <div class="aho-card" style="padding:24px;margin-bottom:16px">
            <h3 style="margin-top:0">Kapasite</h3>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Maksimum Hesap</label>
                <input type="number" name="max_accounts" value="<?= e($s['max_accounts'] ?? '') ?>" placeholder="Sınırsız için boş bırakın" style="width:200px;padding:10px;border:1px solid var(--aho-color-border);border-radius:6px;box-sizing:border-box">
                <div style="font-size:12px;color:var(--aho-color-ink-500);margin-top:4px">Bu sınıra ulaşıldığında yeni siparişler diğer sunuculara yönlenir.</div>
            </div>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center">
            <div style="display:flex;gap:8px">
                <?php if ($isEdit): ?>
                    <button type="button" id="testBtn" onclick="testConnection(<?= (int)$s['id'] ?>)" style="padding:10px 16px;background:#0ea5e9;color:#fff;border:0;border-radius:8px;font-weight:600;cursor:pointer">🔍 Bağlantıyı Test Et</button>
                    <form method="post" action="/admin/hosting-sunucu/<?= (int)$s['id'] ?>/sil" style="display:inline" onsubmit="return confirm('<?= e($s['name']) ?> silinsin mi?')">
                        <?= csrf() ?>
                        <button type="submit" style="padding:10px 16px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:8px;cursor:pointer;font-weight:600">🗑️ Sil</button>
                    </form>
                <?php endif; ?>
            </div>
            <button type="submit" class="aho-btn aho-btn--primary">💾 <?= $isEdit ? 'Değişiklikleri Kaydet' : 'Sunucu Ekle' ?></button>
        </div>
    </form>
</div>

<script>
function updatePort(panel) {
    const el = document.getElementById('serverPort');
    if (!el || el.dataset.userChanged) return;
    el.value = { cpanel: 2087, da: 2222, plesk: 8443, manual: 0 }[panel] ?? 2087;
}
document.getElementById('serverPort')?.addEventListener('input', function(){ this.dataset.userChanged = '1'; });

<?php if ($isEdit): ?>
function testConnection(id) {
    const btn = document.getElementById('testBtn');
    const originalText = btn.textContent;
    btn.textContent = '⏳ Test ediliyor...';
    btn.disabled = true;
    fetch('/admin/hosting-sunucu/' + id + '/test', {
        method: 'POST',
        headers: {'X-CSRF-Token': '<?= csrf_token() ?>', 'Accept': 'application/json'}
    })
    .then(r => r.json())
    .then(d => {
        alert((d.ok ? '✓ Bağlantı başarılı' : '✗ Bağlantı hatası') + '\n' + (d.message || '') + '\nDriver: ' + (d.driver || ''));
        btn.textContent = originalText;
        btn.disabled = false;
    })
    .catch(e => { alert('Hata: ' + e.message); btn.textContent = originalText; btn.disabled = false; });
}
<?php endif; ?>
</script>
<?php $view->endSection(); ?>
