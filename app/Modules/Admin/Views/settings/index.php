<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>⚙️ Ayarlar</h1>
            <p>Firma bilgileri, SMTP, ödeme sağlayıcıları, e-fatura, AI ve güvenlik.
               <strong>Şifreler AES-256-GCM ile güvenli şekilde saklanır.</strong></p>
        </div>
    </div>

    <?php if (!empty($success)): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:240px 1fr;gap:20px" class="aho-settings-layout">
        <!-- Sol menu -->
        <div class="aho-card" style="padding:12px;height:fit-content;position:sticky;top:20px">
            <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:2px">
                <?php foreach ($groups as $key => $g): $isActive = $key === $active; ?>
                    <li>
                        <a href="/admin/ayarlar?group=<?= e($key) ?>"
                           style="display:flex;gap:8px;align-items:center;padding:10px 12px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:500;
                                  color:<?= $isActive ? '#fff' : 'var(--aho-color-ink-700)' ?>;
                                  background:<?= $isActive ? 'var(--aho-color-primary-600, #0ea5e9)' : 'transparent' ?>;">
                            <span><?= $g['icon'] ?></span>
                            <?= e($g['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Sağ form -->
        <div>
            <form method="post" action="/admin/ayarlar/kaydet">
                <?= csrf() ?>
                <input type="hidden" name="group" value="<?= e($active) ?>">

                <div class="aho-card" style="padding:24px">
                    <?php foreach ($fields as $f): ?>
                        <?php if (isset($f['section'])): ?>
                            <h3 style="margin:24px 0 12px;padding-bottom:8px;border-bottom:2px solid var(--aho-color-primary-600, #0ea5e9);color:var(--aho-color-primary-600, #0ea5e9);font-size:16px">
                                <?= e($f['section']) ?>
                            </h3>
                            <?php continue; ?>
                        <?php endif; ?>

                        <div style="margin-bottom:16px">
                            <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px">
                                <?= e($f['label']) ?>
                                <?php if (($f['type'] ?? '') === 'password' && !empty($f['has_value'])): ?>
                                    <span style="color:#059669;font-size:11px;font-weight:400;margin-left:6px">✓ ayarlı</span>
                                <?php endif; ?>
                            </label>

                            <?php $name = 'settings[' . $f['key'] . ']';
                                  $val = $f['value'] ?? '';
                                  $placeholder = $f['placeholder'] ?? ($f['type'] === 'password' && !empty($f['has_value']) ? '(değiştirmek için yeni değer girin)' : '');
                            ?>
                            <?php if ($f['type'] === 'textarea'): ?>
                                <textarea name="<?= e($name) ?>" rows="3" placeholder="<?= e($placeholder) ?>"
                                          style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:8px;font-family:inherit;box-sizing:border-box"><?= e((string)$val) ?></textarea>
                            <?php elseif ($f['type'] === 'select'): ?>
                                <select name="<?= e($name) ?>" style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:8px;box-sizing:border-box">
                                    <?php foreach (($f['options'] ?? []) as $ok => $ov): ?>
                                        <option value="<?= e($ok) ?>" <?= (string)$val === (string)$ok ? 'selected' : '' ?>><?= e($ov) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif ($f['type'] === 'bool'): ?>
                                <label style="display:flex;gap:8px;align-items:center;cursor:pointer">
                                    <input type="checkbox" name="<?= e($name) ?>" value="1" <?= (string)$val === '1' ? 'checked' : '' ?> style="width:18px;height:18px">
                                    <span style="font-size:13px;color:var(--aho-color-ink-600)">Aktif</span>
                                </label>
                            <?php else: ?>
                                <div style="display:flex;gap:6px">
                                    <input type="<?= e($f['type']) ?>" name="<?= e($name) ?>"
                                           value="<?= e((string)$val) ?>" placeholder="<?= e($placeholder) ?>"
                                           <?= $f['type'] === 'password' ? 'autocomplete="new-password"' : '' ?>
                                           style="flex:1;padding:10px;border:1px solid var(--aho-color-border);border-radius:8px;box-sizing:border-box;<?= $f['type']==='password' ? 'font-family:monospace' : '' ?>">
                                    <?php if ($f['type'] === 'password' && !empty($f['has_value'])): ?>
                                        <button type="button" onclick="if(confirm('<?= e($f['label']) ?> temizlensin mi?')){this.form.action='/admin/ayarlar/temizle';this.form.querySelector('[name=key_to_clear]').value='<?= e($f['key']) ?>';this.form.submit();}"
                                                style="padding:6px 12px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:8px;cursor:pointer;font-size:12px" title="Temizle">🗑️</button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($f['hint'])): ?>
                                <div style="font-size:12px;color:var(--aho-color-ink-500);margin-top:4px"><?= e($f['hint']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <input type="hidden" name="key_to_clear" value="">

                    <div style="display:flex;gap:8px;justify-content:flex-end;padding-top:16px;border-top:1px solid var(--aho-color-border);margin-top:20px">
                        <?php if ($active === 'mail'): ?>
                            <button type="button" onclick="testSmtp()" style="padding:10px 16px;background:#0891b2;color:#fff;border:0;border-radius:8px;font-weight:600;cursor:pointer">✉️ Test Mail Gönder</button>
                        <?php endif; ?>
                        <button type="submit" class="aho-btn aho-btn--primary">💾 Kaydet</button>
                    </div>
                </div>
            </form>

            <?php if ($active === 'einvoice'): ?>
                <div class="aho-card" style="padding:16px;margin-top:16px;background:#fef3c7;border-left:4px solid #d97706;font-size:13px;color:var(--aho-color-ink-700);line-height:1.6">
                    ℹ️ <strong>E-Fatura zorunlu mu?</strong> Türkiye'de e-fatura sadece belirli mükellefler için zorunludur:
                    <ul style="margin:6px 0 0;padding-left:20px">
                        <li>Yıllık cirosu <strong>3 milyon TL üstü</strong> firmalar</li>
                        <li>E-ticaret aracı hizmet sağlayıcılar</li>
                        <li>Bazı sektörler (akaryakıt, tütün, gıda vb.)</li>
                    </ul>
                    Bu şartlardan hiçbiri geçerli değilse <strong>"Kullanma"</strong> seçili bırakabilir, normal PDF fatura ile devam edebilirsin. İhtiyaç doğduğunda buradan aktifleştirirsin.
                </div>
            <?php elseif ($active === 'payment' || $active === 'ai'): ?>
                <div class="aho-card" style="padding:16px;margin-top:16px;background:#f0f9ff;border-left:4px solid #0ea5e9;font-size:13px;color:var(--aho-color-ink-700)">
                    🔒 <strong>Güvenlik:</strong> Şifreler ve API anahtarları AES-256-GCM ile şifrelenir. Sunucu diskinde ham metin olarak tutulmaz.
                    <?php if ($active === 'payment'): ?>
                        <br>💡 <strong>Test için:</strong> Sandbox modunu açıp gerçek olmayan test kartlarıyla ödeme akışını doğrulayabilirsiniz.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($active === 'mail'): ?>
<script>
function testSmtp() {
    const to = prompt('Test mail hangi adrese gönderilsin?', '<?= e($_SESSION['admin_email'] ?? '') ?>');
    if (!to) return;
    fetch('/admin/ayarlar/smtp-test', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':'<?= csrf_token() ?>','Accept':'application/json'},
        body: '_csrf=<?= csrf_token() ?>&to=' + encodeURIComponent(to)
    })
    .then(r => r.json())
    .then(d => alert(d.ok ? (d.message || '✓ OK') : ('✗ Hata: ' + (d.error || ''))))
    .catch(e => alert('İstek hatası: ' + e.message));
}
</script>
<?php endif; ?>
<?php $view->endSection(); ?>
