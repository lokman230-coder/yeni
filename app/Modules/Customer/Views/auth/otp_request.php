<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
$error = flash('error');
$info  = flash('info');
?>
<section class="aho-auth" style="padding:48px 0;background:#f9fafb;min-height:calc(100vh - 200px)">
    <div class="aho-container" style="max-width:440px">
        <div class="aho-card">
            <div class="aho-card__body" style="padding:32px">
                <h1 style="margin:0 0 8px">📱 SMS ile Giriş</h1>
                <p style="color:var(--aho-muted);margin:0 0 24px">Kayıtlı telefon numaranıza tek kullanımlık kod yollarız.</p>

                <?php if ($error): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>
                <?php if ($info):  ?><div class="aho-alert aho-alert--info"><?= e($info) ?></div><?php endif; ?>

                <form method="post" action="/giris/sms/kod-gonder">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <div style="margin-bottom:16px">
                        <label>Telefon Numarası *</label>
                        <input type="tel" name="phone" required placeholder="05XX XXX XX XX" autofocus>
                    </div>
                    <button type="submit" class="aho-btn aho-btn--primary" style="width:100%">Kod Gönder</button>
                </form>

                <div style="margin-top:20px;text-align:center;font-size:14px">
                    <a href="/giris">← E-posta ile giriş</a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
