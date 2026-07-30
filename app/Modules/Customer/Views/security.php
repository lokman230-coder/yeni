<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-customer-panel" style="padding:32px 0;background:#f9fafb;min-height:calc(100vh - 200px)">
    <div class="aho-container">
        <div style="display:grid;grid-template-columns:220px 1fr;gap:24px" class="aho-customer-layout">
            <?= $view->include('customer::_sidebar') ?>
            <div>
                <h1 style="margin:0 0 4px;font-size:24px">🔐 Güvenlik</h1>
                <p style="color:var(--aho-color-ink-600);margin:0 0 20px">Hesap güvenliğinizi ayarlayın.</p>

                <?php if (!empty($success)): ?><div class="aho-alert aho-alert--success" style="margin-bottom:16px"><?= e($success) ?></div><?php endif; ?>
                <?php if (!empty($error)): ?><div class="aho-alert aho-alert--danger" style="margin-bottom:16px"><?= e($error) ?></div><?php endif; ?>

                <?php if (!empty($recovery)): ?>
                    <div class="aho-card" style="padding:24px;margin-bottom:16px;border:2px solid #059669;background:#f0fdf4">
                        <h3 style="margin-top:0;color:#065f46">🔑 Kurtarma Kodlarınız</h3>
                        <p style="color:var(--aho-color-ink-700);font-size:14px;margin:8px 0 12px">
                            <strong>Bu kodları GÜVENLİ bir yere kaydedin</strong> (yazıcı, şifre yöneticisi vb).
                            Bir daha size gösterilmeyecek. Telefonunuza erişemezseniz bu kodlardan biri ile giriş yapabilirsiniz.
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
                            <h3 style="margin:0 0 4px;display:flex;gap:8px;align-items:center">
                                🔒 İki Faktörlü Doğrulama (2FA)
                                <?php if ($twofa_enabled): ?>
                                    <span style="padding:3px 10px;font-size:11px;border-radius:10px;background:#d1fae5;color:#065f46">✓ Aktif</span>
                                <?php else: ?>
                                    <span style="padding:3px 10px;font-size:11px;border-radius:10px;background:#fef3c7;color:#92400e">⚠ Pasif</span>
                                <?php endif; ?>
                            </h3>
                            <p style="color:var(--aho-color-ink-600);font-size:14px;margin:8px 0 0">
                                Google Authenticator, Authy veya 1Password gibi bir uygulama ile 6 haneli kod eklenerek
                                hesabınıza yetkisiz erişimi engellersiniz.
                            </p>
                        </div>
                        <div>
                            <?php if ($twofa_enabled): ?>
                                <form method="post" action="/panel/guvenlik/2fa-kapat" onsubmit="return confirm('2FA\'yı kapatmak istediğinize emin misiniz? Hesabınız daha az güvenli olacak.')">
                                    <?= csrf() ?>
                                    <button type="submit" style="padding:10px 16px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:8px;font-weight:600;cursor:pointer">Devre Dışı Bırak</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="/panel/guvenlik/2fa-baslat" style="margin:0">
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
                            Authenticator uygulamanızı açın, QR kodu tarayın. Uygulama 6 haneli kod üretecek.
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
                        <form method="post" action="/panel/guvenlik/2fa-onayla" style="display:flex;gap:12px;align-items:center;max-width:400px">
                            <?= csrf() ?>
                            <input type="text" name="code" required autocomplete="one-time-code"
                                   inputmode="numeric" maxlength="6" placeholder="123456"
                                   style="flex:1;padding:12px;border:2px solid var(--aho-color-border);border-radius:8px;font-size:20px;text-align:center;letter-spacing:6px;font-family:monospace">
                            <button type="submit" class="aho-btn aho-btn--primary">Onayla</button>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- Şifre değiştirme kartı -->
                <div class="aho-card" style="padding:24px;margin-top:16px">
                    <h3 style="margin-top:0">🔑 Şifre Değiştir</h3>
                    <form method="post" action="/panel/guvenlik/sifre-degistir" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;max-width:640px">
                        <?= csrf() ?>
                        <div style="grid-column:1/-1">
                            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Mevcut Şifre *</label>
                            <input type="password" name="current_password" required autocomplete="current-password"
                                   style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:8px;box-sizing:border-box">
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Yeni Şifre * <span style="font-weight:400;color:var(--aho-color-ink-500)">(min 8)</span></label>
                            <input type="password" name="new_password" required minlength="8" autocomplete="new-password"
                                   style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:8px;box-sizing:border-box">
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Yeni Şifre Tekrar *</label>
                            <input type="password" name="new_password_confirm" required minlength="8" autocomplete="new-password"
                                   style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:8px;box-sizing:border-box">
                        </div>
                        <div style="grid-column:1/-1;text-align:right">
                            <button type="submit" class="aho-btn aho-btn--primary">Şifreyi Değiştir</button>
                        </div>
                    </form>
                </div>

                <div class="aho-card" style="padding:20px;margin-top:16px;background:#f9fafb;font-size:13px;color:var(--aho-color-ink-600);line-height:1.6">
                    💡 <strong>Uyarı:</strong> Şifrenizi kimseyle paylaşmayın. Ahost Bilişim size hiçbir zaman
                    şifrenizi veya 2FA kodunuzu sormaz. Şüpheli bir e-posta aldıysanız <a href="/destek">destek</a>'e bildirin.
                </div>
            </div>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
