<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');

$errors = $errors ?? [];
$old    = $old    ?? [];
$flashError = flash('error');

// Referral cookie varsa, kullanıcıya "arkadaşınız X sizi davet etti" bilgisi göster
$refCode = $_COOKIE['aho_ref'] ?? \App\Core\SessionManager::get('ref_code', null);
$refererName = null;
if ($refCode && class_exists(\App\Modules\Referral\Services\ReferralService::class)) {
    try {
        $codeRow = \App\Modules\Referral\Services\ReferralService::codeRow($refCode);
        if ($codeRow) {
            $c = \App\Core\Database\Connection::selectOne("SELECT first_name, last_name FROM customers WHERE id = ?", [$codeRow['customer_id']]);
            if ($c) $refererName = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
        }
    } catch (\Throwable) {}
}
?>
<section class="aho-customer-auth" style="padding:40px 0">
    <div class="aho-container" style="max-width:520px">
        <div class="aho-customer-auth__card aho-card" style="padding:36px">
            <h1 class="aho-customer-auth__title" style="margin:0 0 6px;font-size:24px">Ücretsiz Hesap Oluştur</h1>
            <p class="aho-customer-auth__subtitle" style="color:var(--aho-color-ink-600);font-size:14px;margin:0 0 20px">
                Ahost Bilişim müşterisi olun — hosting, domain, site builder, marketplace ve daha fazlası tek panelde.
            </p>

            <?php if ($refererName): ?>
                <div class="aho-alert aho-alert--info" style="margin-bottom:16px;background:#f0fdf4;border-color:#059669;color:#065f46">
                    🎁 <strong><?= e($refererName) ?></strong> sizi davet etti — kaydolduktan sonra alışverişleriniz onun komisyon havuzuna katkı sağlayacak. Bu size ek maliyet <em>getirmez</em>.
                </div>
            <?php endif; ?>

            <?php if ($flashError): ?>
                <div class="aho-alert aho-alert--danger" style="margin-bottom:16px"><?= e($flashError) ?></div>
            <?php endif; ?>
            <?php if (!empty($errors['general'])): ?>
                <div class="aho-alert aho-alert--danger" style="margin-bottom:16px"><?= e($errors['general']) ?></div>
            <?php endif; ?>

            <form method="post" action="/kayit" novalidate>
                <?= csrf() ?>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px">Ad *</label>
                        <input type="text" name="first_name" required maxlength="100"
                               value="<?= e($old['first_name'] ?? '') ?>"
                               style="width:100%;padding:10px;border:1px solid <?= !empty($errors['first_name']) ? '#dc2626' : 'var(--aho-color-border)' ?>;border-radius:8px;font-size:14px;box-sizing:border-box">
                        <?php if (!empty($errors['first_name'])): ?>
                            <div style="color:#dc2626;font-size:12px;margin-top:2px"><?= e($errors['first_name']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px">Soyad *</label>
                        <input type="text" name="last_name" required maxlength="100"
                               value="<?= e($old['last_name'] ?? '') ?>"
                               style="width:100%;padding:10px;border:1px solid <?= !empty($errors['last_name']) ? '#dc2626' : 'var(--aho-color-border)' ?>;border-radius:8px;font-size:14px;box-sizing:border-box">
                        <?php if (!empty($errors['last_name'])): ?>
                            <div style="color:#dc2626;font-size:12px;margin-top:2px"><?= e($errors['last_name']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="margin-bottom:14px">
                    <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px">E-posta *</label>
                    <input type="email" name="email" required maxlength="191"
                           value="<?= e($old['email'] ?? '') ?>"
                           autocomplete="email"
                           style="width:100%;padding:10px;border:1px solid <?= !empty($errors['email']) ? '#dc2626' : 'var(--aho-color-border)' ?>;border-radius:8px;font-size:14px;box-sizing:border-box">
                    <?php if (!empty($errors['email'])): ?>
                        <div style="color:#dc2626;font-size:12px;margin-top:2px"><?= e($errors['email']) ?></div>
                    <?php endif; ?>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px">Telefon</label>
                        <input type="tel" name="phone" maxlength="32"
                               value="<?= e($old['phone'] ?? '') ?>"
                               placeholder="0555 111 22 33"
                               style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:8px;font-size:14px;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px">Firma (opsiyonel)</label>
                        <input type="text" name="company" maxlength="191"
                               value="<?= e($old['company'] ?? '') ?>"
                               style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:8px;font-size:14px;box-sizing:border-box">
                    </div>
                </div>

                <div style="margin-bottom:14px">
                    <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px">Şifre * <span style="color:var(--aho-color-ink-500);font-weight:400;font-size:11px">(en az 8 karakter)</span></label>
                    <input type="password" name="password" required minlength="8"
                           autocomplete="new-password"
                           style="width:100%;padding:10px;border:1px solid <?= !empty($errors['password']) ? '#dc2626' : 'var(--aho-color-border)' ?>;border-radius:8px;font-size:14px;box-sizing:border-box">
                    <?php if (!empty($errors['password'])): ?>
                        <div style="color:#dc2626;font-size:12px;margin-top:2px"><?= e($errors['password']) ?></div>
                    <?php endif; ?>
                </div>

                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px">Şifre Tekrar *</label>
                    <input type="password" name="password_confirm" required minlength="8"
                           autocomplete="new-password"
                           style="width:100%;padding:10px;border:1px solid <?= !empty($errors['password_confirm']) ? '#dc2626' : 'var(--aho-color-border)' ?>;border-radius:8px;font-size:14px;box-sizing:border-box">
                    <?php if (!empty($errors['password_confirm'])): ?>
                        <div style="color:#dc2626;font-size:12px;margin-top:2px"><?= e($errors['password_confirm']) ?></div>
                    <?php endif; ?>
                </div>

                <div style="margin-bottom:20px">
                    <label style="display:flex;gap:8px;align-items:flex-start;cursor:pointer;font-size:13px;line-height:1.5">
                        <input type="checkbox" name="kvkk" value="1" required style="margin-top:3px">
                        <span>
                            <a href="/sayfa/uyelik-sozlesmesi" target="_blank">Üyelik Sözleşmesi</a>'ni,
                            <a href="/sayfa/kvkk-aydinlatma" target="_blank">KVKK Aydınlatma Metni</a>'ni ve
                            <a href="/sayfa/gizlilik-politikasi" target="_blank">Gizlilik Politikası</a>'nı okudum, kabul ediyorum. *
                        </span>
                    </label>
                    <?php if (!empty($errors['kvkk'])): ?>
                        <div style="color:#dc2626;font-size:12px;margin-top:2px;margin-left:22px"><?= e($errors['kvkk']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="aho-btn aho-btn--primary aho-btn--lg" style="width:100%;font-size:15px">
                    Hesap Oluştur
                </button>
            </form>

            <div class="aho-customer-auth__footer" style="text-align:center;margin-top:20px;padding-top:16px;border-top:1px solid var(--aho-color-border);font-size:14px;color:var(--aho-color-ink-600)">
                Zaten hesabınız var mı? <a href="/giris" style="font-weight:600">Giriş yapın</a>
            </div>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
