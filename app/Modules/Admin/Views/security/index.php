<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>🔐 Güvenlik</h1>
            <p>Admin hesabınızın güvenlik ayarları — 2FA, şifre, oturum yönetimi.</p>
        </div>
    </div>

    <?php if (!empty($success)): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

    <?php if (!empty($recovery)): ?>
        <div class="aho-card" style="padding:24px;margin-bottom:16px;border:2px solid #059669;background:#f0fdf4">
            <h3 style="margin-top:0;color:#065f46">🔑 Kurtarma Kodları</h3>
            <p style="color:var(--aho-color-ink-700);font-size:14px;margin:8px 0 12px">
                <strong>Bu kodları GÜVENLİ bir yere kaydedin</strong> — bir daha gösterilmeyecek.
                Authenticator uygulamanıza erişemediğinizde bu kodlardan biriyle giriş yapabilirsiniz.
            </p>
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;font-family:monospace;font-size:15px;background:#fff;padding:16px;border-radius:8px">
                <?php foreach ($recovery as $c): ?>
                    <div style="padding:6px 10px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;text-align:center;font-weight:600"><?= e($c) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="aho-card" style="padding:24px;margin-bottom:16px">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px">
            <div style="flex:1">
                <h3 style="margin:0 0 6px;display:flex;gap:8px;align-items:center">
                    🔒 İki Faktörlü Doğrulama (2FA)
                    <?php if ($twofa_enabled): ?>
                        <span style="padding:3px 10px;font-size:11px;border-radius:10px;background:#d1fae5;color:#065f46">✓ Aktif</span>
                    <?php else: ?>
                        <span style="padding:3px 10px;font-size:11px;border-radius:10px;background:#fef3c7;color:#92400e">⚠ Pasif — ÖNERİLİR</span>
                    <?php endif; ?>
                </h3>
                <p style="color:var(--aho-color-ink-600);font-size:14px;margin:8px 0 0">
                    <strong>Admin hesapları için 2FA şiddetle önerilir.</strong> Google Authenticator, Authy veya 1Password ile 6 haneli kod eklenir.
                    Şifreniz sızsa bile hesabınıza girilemez.
                </p>
            </div>
            <div>
                <?php if ($twofa_enabled): ?>
                    <form method="post" action="/admin/guvenlik/2fa-kapat" onsubmit="return confirm('2FA kapatılsın mı? Hesap güvenliği düşecek.')">
                        <?= csrf() ?>
                        <button type="submit" style="padding:10px 16px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:8px;font-weight:600;cursor:pointer">Devre Dışı Bırak</button>
                    </form>
                <?php else: ?>
                    <form method="post" action="/admin/guvenlik/2fa-baslat">
                        <?= csrf() ?>
                        <button type="submit" class="aho-btn aho-btn--primary">2FA Kur</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($setup)): ?>
        <div class="aho-card" style="padding:24px">
            <h3 style="margin-top:0">📱 Adım 1: QR Kodu Okutun</h3>
            <p style="color:var(--aho-color-ink-600);font-size:14px">
                Authenticator uygulamanızı açın, "+" ile hesap ekleyin ve QR kodu okutun.
            </p>

            <div style="display:grid;grid-template-columns:auto 1fr;gap:24px;align-items:center;margin:16px 0">
                <div style="padding:16px;background:#fff;border:1px solid var(--aho-color-border);border-radius:12px"><?= $setup['qr_svg'] ?></div>
                <div>
                    <div style="font-size:13px;color:var(--aho-color-ink-500);margin-bottom:4px">QR okutamıyorsanız secret:</div>
                    <code style="display:block;padding:12px;background:#f9fafb;border:1px dashed var(--aho-color-border);border-radius:8px;font-size:14px;word-break:break-all"><?= e($setup['secret']) ?></code>
                </div>
            </div>

            <hr style="border:0;border-top:1px solid var(--aho-color-border);margin:20px 0">

            <h3 style="margin-top:0">✅ Adım 2: 6 Haneli Kodu Onaylayın</h3>
            <form method="post" action="/admin/guvenlik/2fa-onayla" style="display:flex;gap:12px;align-items:center;max-width:400px">
                <?= csrf() ?>
                <input type="text" name="code" required autocomplete="one-time-code"
                       inputmode="numeric" maxlength="6" placeholder="123456"
                       style="flex:1;padding:12px;border:2px solid var(--aho-color-border);border-radius:8px;font-size:20px;text-align:center;letter-spacing:6px;font-family:monospace">
                <button type="submit" class="aho-btn aho-btn--primary">Onayla</button>
            </form>
        </div>
    <?php endif; ?>

    <div class="aho-card" style="padding:20px;margin-top:16px;background:#eff6ff;border-left:4px solid #0ea5e9;font-size:13px;color:var(--aho-color-ink-700);line-height:1.6">
        💡 <strong>Yönetici hesapları için ek güvenlik önerileri:</strong>
        <ul style="margin:8px 0 0;padding-left:20px">
            <li>Şifrenizi periyodik olarak değiştirin (60 gün)</li>
            <li>Farklı sitelerde aynı şifreyi kullanmayın</li>
            <li>Şifre yöneticisi kullanın (1Password, Bitwarden, KeePass)</li>
            <li>Kurtarma kodlarınızı fiziksel bir yerde de saklayın</li>
        </ul>
    </div>
</div>
<?php $view->endSection(); ?>
