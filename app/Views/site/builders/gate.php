<section class="builder-public-page">
    <div class="builder-shell builder-gate">
        <div class="builder-gate-card">
            <?php $isAiGate = ($gateMode ?? '') === 'ai_trial'; ?>
            <span class="builder-badge"><?= $isAiGate ? 'AI DENEME HAKKI KULLANILDI' : e(strtoupper($format ?? 'ZIP')) . ' ÇIKTI KİLİTLİ' ?></span>
            <h1><?= $isAiGate ? 'AI ile tasarlamaya devam etmek için paket gerekli' : (($kind ?? '') === 'mobilebuilder' ? 'APK/AAB ve kaynak kod için paket gerekli' : 'ZIP ve kaynak kod için paket gerekli') ?></h1>
            <p><?= $isAiGate ? 'Ücretsiz AI tasarım denemeniz kullanıldı. Paket alarak AI yardımcı ile devam edebilir ya da AI yardımcı olmadan normal builder akışına dönebilirsiniz.' : 'Önizleme ve tasarım denemesi ziyaretçilere açıktır. Gerçek çıktı indirme, APK/AAB/ZIP üretimi, kaynak kod alma ve proje kaydetme işlemleri için kayıt olup uygun paketi satın almanız gerekir.' ?></p>
            <div class="builder-gate-actions">
                <a class="site-btn" href="<?= url($packageRoute ?? 'urunler') ?>"><?= $isAiGate ? 'AI ile tasarlamak için paket al' : 'Paketleri İncele' ?></a>
                <?php if(!$isAiGate): ?><a class="site-btn secondary" href="<?= url('client/login') ?>">Müşteri Girişi</a><?php endif; ?>
                <a class="site-btn ghost" href="<?= url($continueRoute ?? (($kind ?? '') === 'mobilebuilder' ? 'mobilebuilder/preview-public' : 'sitebuilder/preview-public')) ?>"><?= $isAiGate ? 'AI yardımcı olmadan devam et' : 'Önizlemeye Dön' ?></a>
            </div>
            <div class="builder-lock-list">
                <div>✓ Ziyaretçi: Şablon seçebilir ve önizleme yapabilir.</div>
                <div>✓ Müşteri: Proje kaydedebilir, düzenleyebilir ve paketine göre çıktı alabilir.</div>
                <div>✓ Paketli kullanıcı: ZIP / APK / AAB / PWA ve kaynak kod çıktısı alabilir.</div>
            </div>
        </div>
    </div>
</section>
