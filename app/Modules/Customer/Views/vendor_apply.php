<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-customer-panel" style="padding:32px 0;background:#f9fafb;min-height:calc(100vh - 200px)">
    <div class="aho-container" style="max-width:800px">
        <div style="text-align:center;margin-bottom:32px">
            <h1 style="font-size:32px;margin:0 0 8px">🏪 Marketplace Satıcısı Ol</h1>
            <p style="color:#6b7280;margin:0">Kendi ürünlerini (tema, script, tasarım, SEO paketi vb.) sat, komisyon ödemeni al.</p>
        </div>

        <?php if ($success = flash('success')): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
        <?php if ($error = flash('error')): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

        <div class="aho-card" style="padding:24px;margin-bottom:20px;background:#f0f9ff;border-left:4px solid #0ea5e9">
            <h3 style="margin:0 0 12px">💡 Nasıl Çalışır?</h3>
            <ol style="line-height:1.8;color:#0c4a6e;padding-left:20px">
                <li>Aşağıdaki formu doldur, başvurunu gönder.</li>
                <li>Admin ekibi 1-3 iş günü içinde inceler.</li>
                <li>Onaylandığında ürün ekleyebilir, satış yapabilirsin.</li>
                <li>Her satıştan <strong>%15 komisyon</strong> alırız, kalan senin.</li>
                <li>14 gün iade süresi sonrası kazançların ödemeye hazır olur, IBAN'a talep edebilirsin.</li>
            </ol>
        </div>

        <form method="post" action="/panel/satici/basvur" class="aho-card" style="padding:28px">
            <?= csrf() ?>
            <h3 style="margin:0 0 20px">📝 Başvuru Bilgileri</h3>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div style="grid-column:1/-1">
                    <label>Mağaza Adı *</label>
                    <input type="text" name="shop_name" required placeholder="Örn: Yılmaz Yazılım">
                </div>
                <div style="grid-column:1/-1">
                    <label>Kısa Açıklama</label>
                    <textarea name="description" rows="3" placeholder="Ne satıyorsun, nasıl bir hizmet sunuyorsun?"></textarea>
                </div>
                <div>
                    <label>İletişim E-postası *</label>
                    <input type="email" name="contact_email" required>
                </div>
                <div>
                    <label>Telefon</label>
                    <input type="tel" name="contact_phone">
                </div>
                <div>
                    <label>Website</label>
                    <input type="url" name="website" placeholder="https://...">
                </div>
                <div>
                    <label>Şehir</label>
                    <input type="text" name="city">
                </div>
                <div>
                    <label>Vergi No / TCKN</label>
                    <input type="text" name="tax_id" placeholder="Fatura kesim için">
                </div>
                <div>
                    <label>IBAN (Payout için)</label>
                    <input type="text" name="iban" placeholder="TR00 0000 0000 0000 0000 0000 00">
                </div>
                <div style="grid-column:1/-1">
                    <label>IBAN Sahibi</label>
                    <input type="text" name="iban_holder" placeholder="Ad Soyad / Firma Adı">
                </div>
            </div>

            <button type="submit" class="aho-btn aho-btn--primary" style="margin-top:20px;width:100%;padding:14px;font-size:16px">
                🚀 Başvuruyu Gönder
            </button>
        </form>
    </div>
</section>
<?php $view->endSection(); ?>
