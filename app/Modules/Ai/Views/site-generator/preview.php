<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
$sectorData = $sectors[$detected['sector']] ?? ['icon' => '🌐', 'label' => 'Genel'];
$confidencePct = (int) round($detected['confidence'] * 100);
?>
<section class="aho-pages-hero" style="background:linear-gradient(135deg,#0ea5e9 0%,#8b5cf6 100%);color:#fff">
    <div class="aho-container" style="text-align:center;padding:40px 20px">
        <h1 style="margin:0 0 8px;font-size:28px">✅ Anladık — İşte planımız</h1>
        <p style="opacity:.9;margin:0">Onaylarsanız hemen sitenizi oluşturalım</p>
    </div>
</section>

<section style="padding:40px 0">
    <div class="aho-container" style="max-width:820px">

        <div class="aho-card" style="padding:24px;margin-bottom:20px">
            <div style="font-size:13px;color:var(--aho-color-ink-500);margin-bottom:4px">Sizin talebiniz:</div>
            <div style="font-style:italic;font-size:15px;color:var(--aho-color-ink-700);border-left:3px solid var(--aho-color-primary-600);padding:8px 16px;background:#f9fafb;border-radius:0 6px 6px 0">
                "<?= e($prompt) ?>"
            </div>
        </div>

        <div class="aho-card" style="padding:28px;text-align:center;background:linear-gradient(180deg,#fff 0%,#f0f9ff 100%)">
            <div style="font-size:64px;line-height:1"><?= $sectorData['icon'] ?></div>
            <h2 style="margin:12px 0 4px;font-size:24px">Tahminimiz: <?= e($sectorData['label']) ?></h2>
            <div style="color:var(--aho-color-ink-500);font-size:14px">
                Güven: <strong style="color:<?= $confidencePct >= 60 ? '#059669' : ($confidencePct >= 30 ? '#d97706' : '#dc2626') ?>"><?= $confidencePct ?>%</strong>
                <?php if (!empty($detected['matched_keywords'])): ?>
                    · Eşleşen: <?= e(implode(', ', array_slice($detected['matched_keywords'], 0, 4))) ?>
                <?php endif; ?>
            </div>
        </div>

        <form method="post" action="/ai/site-olustur/uret" class="aho-card" style="padding:28px;margin-top:20px">
            <?= csrf() ?>
            <input type="hidden" name="prompt" value="<?= e($prompt) ?>">

            <div style="display:grid;grid-template-columns:1fr 2fr;gap:16px;margin-bottom:20px">
                <div>
                    <label style="display:block;font-weight:600;font-size:13px;margin-bottom:6px">Sektör (düzelt)</label>
                    <select name="sector" style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:8px;font-size:14px">
                        <?php foreach ($sectors as $slug => $data): ?>
                            <option value="<?= e($slug) ?>" <?= $slug === $detected['sector'] ? 'selected' : '' ?>>
                                <?= $data['icon'] ?> <?= e($data['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-weight:600;font-size:13px;margin-bottom:6px">İşletme Adı (isteğe bağlı)</label>
                    <input type="text" name="name" placeholder="<?= e($detected['app_name_guess'] ?? 'Yeni Sitem') ?>"
                           value="<?= e($detected['app_name_guess'] ?? '') ?>"
                           style="width:100%;padding:10px;border:1px solid var(--aho-color-border);border-radius:8px;font-size:14px;box-sizing:border-box">
                </div>
            </div>

            <div style="display:flex;gap:12px">
                <a href="/ai/site-olustur" class="aho-btn aho-btn--ghost">← Değiştir</a>
                <button type="submit" class="aho-btn aho-btn--primary aho-btn--lg" style="flex:1;font-size:16px">
                    🚀 Sitemi Oluştur
                </button>
            </div>

            <p style="margin-top:14px;color:var(--aho-color-ink-500);font-size:12px;text-align:center">
                Site anasayfası oluşturulacak → Site Builder editöründe düzenlemeye devam edebilirsiniz.
            </p>
        </form>
    </div>
</section>
<?php $view->endSection(); ?>
