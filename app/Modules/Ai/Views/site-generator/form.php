<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-pages-hero" style="background:linear-gradient(135deg,#0ea5e9 0%,#8b5cf6 100%);color:#fff">
    <div class="aho-container" style="text-align:center;padding:60px 20px">
        <div style="font-size:56px;margin-bottom:8px">🤖✨</div>
        <h1 style="margin:0 0 12px;font-size:36px">AI ile Site Oluştur</h1>
        <p style="opacity:.9;font-size:18px;max-width:640px;margin:0 auto">
            İşinizi bir cümlede anlatın — <strong>saniyeler içinde</strong> sektörünüze uygun,
            içeriği hazır bir site oluşturalım. Sonra Site Builder ile istediğiniz gibi düzenleyin.
        </p>
    </div>
</section>

<section style="padding:40px 0">
    <div class="aho-container" style="max-width:820px">

        <?php if (!empty($error)): ?>
            <div class="aho-alert aho-alert--danger" style="margin-bottom:20px"><?= e($error) ?></div>
        <?php endif; ?>

        <?php $kind = $_GET['kind'] ?? 'site'; ?>
        <form method="post" action="/ai/site-olustur/onizle" class="aho-card" style="padding:32px">
            <?= csrf() ?>
            <input type="hidden" name="kind" value="<?= e($kind) ?>">

            <!-- Kind toggle -->
            <div style="display:flex;gap:8px;margin-bottom:20px;background:#f3f4f6;padding:4px;border-radius:10px">
                <a href="?kind=site" style="flex:1;text-align:center;padding:10px;border-radius:8px;text-decoration:none;font-weight:600;background:<?= $kind === 'site' ? '#fff' : 'transparent' ?>;color:<?= $kind === 'site' ? '#0ea5e9' : '#6b7280' ?>;box-shadow:<?= $kind === 'site' ? '0 2px 4px rgba(0,0,0,0.05)' : 'none' ?>">
                    🖥 Web Sitesi
                </a>
                <a href="?kind=mobile" style="flex:1;text-align:center;padding:10px;border-radius:8px;text-decoration:none;font-weight:600;background:<?= $kind === 'mobile' ? '#fff' : 'transparent' ?>;color:<?= $kind === 'mobile' ? '#0ea5e9' : '#6b7280' ?>;box-shadow:<?= $kind === 'mobile' ? '0 2px 4px rgba(0,0,0,0.05)' : 'none' ?>">
                    📱 Mobil Uygulama
                </a>
            </div>

            <label for="promptField" style="display:block;font-weight:600;margin-bottom:8px;font-size:15px">
                💭 <?= $kind === 'mobile' ? 'Uygulamanızı' : 'İşinizi' ?> bir cümlede anlatın
            </label>
            <textarea id="promptField" name="prompt" rows="3" required minlength="4" maxlength="500"
                      placeholder="Örn: Ali Diş Kliniği için modern, güven veren bir diş hekimi sitesi yap"
                      style="width:100%;padding:14px;border:2px solid var(--aho-color-border);border-radius:10px;font-size:15px;font-family:inherit;resize:vertical;box-sizing:border-box"></textarea>

            <div style="margin-top:16px;color:var(--aho-color-ink-500);font-size:13px">
                💡 <strong>İpucu:</strong> Sektörünüzü, işletme adınızı ve tarzınızı belirtirseniz
                sonuç çok daha iyi olur. Ör: <em>"'Napoli Pizza' için sıcak ve iştah açıcı bir restoran sitesi"</em>
            </div>

            <button type="submit" class="aho-btn aho-btn--primary aho-btn--lg" style="margin-top:20px;width:100%;font-size:16px">
                ✨ Sektörü Tahmin Et →
            </button>
        </form>

        <div style="margin-top:32px">
            <h3 style="font-size:16px;color:var(--aho-color-ink-700);margin-bottom:12px">🎯 Hazır örnekler</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px">
                <?php foreach ($examples as $ex): ?>
                    <button type="button" onclick="document.getElementById('promptField').value = <?= json_encode($ex['prompt'], JSON_UNESCAPED_UNICODE) ?>; document.getElementById('promptField').focus();"
                            class="aho-card"
                            style="text-align:left;padding:14px;cursor:pointer;border:1px solid var(--aho-color-border);transition:transform .15s;background:#fff">
                        <div style="font-size:22px"><?= $ex['icon'] ?></div>
                        <div style="font-weight:600;margin-top:4px;font-size:14px"><?= e($ex['label']) ?></div>
                        <div style="font-size:12px;color:var(--aho-color-ink-500);margin-top:4px;line-height:1.4">
                            "<?= e(mb_substr($ex['prompt'], 0, 60)) ?>…"
                        </div>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="aho-card" style="margin-top:32px;padding:20px;background:#f0f9ff;border-left:4px solid #0ea5e9">
            <h4 style="margin:0 0 8px;font-size:15px">🔒 Sizin verinizle çalışır</h4>
            <p style="margin:0;font-size:14px;color:var(--aho-color-ink-700)">
                Oluşturulan site sizin hesabınıza kaydedilir. İçeriği ve tasarımı istediğiniz gibi düzenleyebilir,
                yayına aldığınızda kendi domain'inizde çalıştırabilirsiniz. AI sizden ödeme aldıktan sonra
                daha fazla özellik (dilediğiniz kadar sayfa üretme, çoklu dil vs.) açılır.
            </p>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
