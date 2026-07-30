<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
$error = flash('error');
$info  = flash('info');
$success = flash('success');
$maskedPhone = preg_replace('/(\d{2})\d{4}(\d{2})/', '$1****$2', preg_replace('/\D/', '', $phone) ?? '');
?>
<section class="aho-auth" style="padding:48px 0;background:#f9fafb;min-height:calc(100vh - 200px)">
    <div class="aho-container" style="max-width:440px">
        <div class="aho-card">
            <div class="aho-card__body" style="padding:32px;text-align:center">
                <h1 style="margin:0 0 8px">🔢 Kodu Girin</h1>
                <p style="color:var(--aho-muted);margin:0 0 24px">
                    <?= e($maskedPhone) ?> numarasına yollanan 6 haneli kodu girin.
                </p>

                <?php if ($error):   ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>
                <?php if ($info):    ?><div class="aho-alert aho-alert--info"><?= e($info) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>

                <form method="post" action="/giris/sms/kod-dogrula">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <div style="margin-bottom:16px">
                        <input type="text" name="code" required autofocus inputmode="numeric" pattern="\d{6}" maxlength="6"
                               placeholder="000000" style="font-size:28px;text-align:center;letter-spacing:8px;font-weight:600">
                    </div>
                    <button type="submit" class="aho-btn aho-btn--primary" style="width:100%">Doğrula ve Giriş Yap</button>
                </form>

                <div style="margin-top:20px;font-size:14px">
                    <a href="/giris/sms">← Farklı numara</a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
