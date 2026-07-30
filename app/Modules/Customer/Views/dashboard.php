<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
$stats = $stats ?? ['services_active' => 0, 'services_all' => 0, 'domains' => 0, 'invoices_unpaid' => 0, 'invoices_all' => 0, 'unpaid_total' => 0, 'balance' => 0];
$success = flash('success');
?>
<section class="aho-customer-panel" style="padding:32px 0;background:#f9fafb;min-height:calc(100vh - 200px)">
    <div class="aho-container">

        <?php if ($success): ?>
            <div class="aho-alert aho-alert--success" style="margin-bottom:20px"><?= e($success) ?></div>
        <?php endif; ?>

        <?php
        // E-posta doğrulama uyarı bandı
        $emailVerified = !empty($customer['email_verified_at']);
        if (!$emailVerified): ?>
            <div class="aho-alert" style="margin-bottom:20px;padding:14px 20px;background:#fef3c7;border-left:4px solid #d97706;color:#92400e;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
                <div>
                    ✉️ <strong>E-posta adresiniz henüz doğrulanmadı.</strong>
                    <span style="font-size:13px;opacity:.85"><?= e($customer['email']) ?> adresine gönderdiğimiz doğrulama bağlantısına tıklayın.</span>
                </div>
                <form method="post" action="/email-dogrula/tekrar-gonder" style="margin:0">
                    <?= csrf() ?>
                    <button type="submit" style="padding:8px 16px;background:#d97706;color:#fff;border:0;border-radius:8px;font-weight:600;cursor:pointer;font-size:13px">Tekrar Gönder</button>
                </form>
            </div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:220px 1fr;gap:24px" class="aho-customer-layout">
            <!-- Sol menü -->
            <?= $view->include('customer::_sidebar') ?>

            <!-- Sağ içerik -->
            <div>
                <div style="margin-bottom:24px">
                    <h1 style="margin:0 0 4px;font-size:24px">👋 Hoş geldiniz, <?= e($customer['first_name'] ?? 'Müşteri') ?></h1>
                    <p style="color:var(--aho-color-ink-600);margin:0">İşlerinizi tek panelden yönetin.</p>
                </div>

                <!-- Ana metrik kartları -->
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:24px">
                    <a href="/panel/hizmetlerim" class="aho-card" style="padding:20px;text-decoration:none;color:inherit">
                        <div style="font-size:11px;color:var(--aho-color-ink-500)">HİZMETLER</div>
                        <div style="font-size:28px;font-weight:700;margin-top:4px"><?= (int)$stats['services_active'] ?></div>
                        <div style="font-size:12px;color:var(--aho-color-ink-500)">
                            <?= (int)$stats['services_all'] ?> toplam
                        </div>
                    </a>
                    <a href="/panel/domainlerim" class="aho-card" style="padding:20px;text-decoration:none;color:inherit">
                        <div style="font-size:11px;color:var(--aho-color-ink-500)">DOMAIN</div>
                        <div style="font-size:28px;font-weight:700;margin-top:4px"><?= (int)$stats['domains'] ?></div>
                        <div style="font-size:12px;color:var(--aho-color-ink-500)">aktif</div>
                    </a>
                    <a href="/panel/faturalarim" class="aho-card" style="padding:20px;text-decoration:none;color:inherit;<?= $stats['invoices_unpaid'] > 0 ? 'border-left:4px solid #d97706' : '' ?>">
                        <div style="font-size:11px;color:var(--aho-color-ink-500)">ÖDENMEMİŞ</div>
                        <div style="font-size:28px;font-weight:700;margin-top:4px;color:<?= $stats['invoices_unpaid'] > 0 ? '#d97706' : 'inherit' ?>">
                            <?= (int)$stats['invoices_unpaid'] ?>
                        </div>
                        <div style="font-size:12px;color:var(--aho-color-ink-500)">
                            <?= number_format($stats['unpaid_total'], 2, ',', '.') ?> ₺
                        </div>
                    </a>
                    <div class="aho-card" style="padding:20px;background:linear-gradient(135deg,#059669 0%,#0891b2 100%);color:#fff">
                        <div style="font-size:11px;opacity:.9">BAKİYE</div>
                        <div style="font-size:24px;font-weight:700;margin-top:4px"><?= number_format($stats['balance'], 2, ',', '.') ?> ₺</div>
                        <div style="font-size:12px;opacity:.85">kullanılabilir</div>
                    </div>
                </div>

                <!-- Hızlı erişim kartları -->
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:24px">
                    <a href="/panel/referanslarim" class="aho-card" style="padding:20px;text-decoration:none;color:inherit;display:block;background:linear-gradient(135deg,#059669 0%,#0891b2 100%);color:#fff">
                        <div style="font-size:32px">🎁</div>
                        <div style="font-weight:700;margin-top:8px">Referans Programım</div>
                        <div style="font-size:13px;opacity:.9;margin-top:4px">Link paylaş, komisyon kazan</div>
                    </a>
                    <a href="/ai/site-olustur" class="aho-card" style="padding:20px;text-decoration:none;color:inherit;display:block;background:linear-gradient(135deg,#8b5cf6 0%,#ec4899 100%);color:#fff">
                        <div style="font-size:32px">🤖</div>
                        <div style="font-weight:700;margin-top:8px">AI ile Site Oluştur</div>
                        <div style="font-size:13px;opacity:.9;margin-top:4px">Bir cümleyle site yap</div>
                    </a>
                    <a href="/hosting" class="aho-card" style="padding:20px;text-decoration:none;color:inherit;display:block">
                        <div style="font-size:32px">🖥️</div>
                        <div style="font-weight:700;margin-top:8px;color:var(--aho-color-ink-900)">Yeni Hosting</div>
                        <div style="font-size:13px;color:var(--aho-color-ink-500);margin-top:4px">Paketleri incele</div>
                    </a>
                </div>

                <?php if ($stats['invoices_unpaid'] > 0): ?>
                <div class="aho-card" style="padding:20px;background:#fef3c7;border-left:4px solid #d97706">
                    <strong>⚠️ Ödenmemiş faturanız var.</strong>
                    <?= (int)$stats['invoices_unpaid'] ?> fatura, toplam <strong><?= number_format($stats['unpaid_total'], 2, ',', '.') ?> ₺</strong>.
                    <a href="/panel/faturalarim" style="font-weight:600;margin-left:8px">→ Faturalarımı Gör</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
