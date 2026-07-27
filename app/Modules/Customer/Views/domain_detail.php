<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
$daysLeft = null;
if (!empty($domain['expiry_date'])) {
    $daysLeft = (int) ((strtotime((string)$domain['expiry_date']) - time()) / 86400);
}
?>
<section class="aho-customer-panel" style="padding:32px 0;background:#f9fafb;min-height:calc(100vh - 200px)">
    <div class="aho-container">
        <div style="display:grid;grid-template-columns:220px 1fr;gap:24px" class="aho-customer-layout">
            <?= $view->include('customer::_sidebar') ?>
            <div>
                <a href="/panel/domainlerim" style="color:#6b7280;text-decoration:none;font-size:13px">← Domainlerim</a>

                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin:12px 0 24px">
                    <div>
                        <h1 style="margin:0 0 4px;font-size:26px;font-family:monospace">🌐 <?= e($domain['domain_name']) ?></h1>
                        <p style="color:#6b7280;margin:0">Registrar: <?= e($domain['registrar_name'] ?? '—') ?></p>
                    </div>
                </div>

                <?php if ($success = flash('success')): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
                <?php if ($info = flash('info')): ?><div class="aho-alert aho-alert--info" style="white-space:pre-line"><?= e($info) ?></div><?php endif; ?>
                <?php if ($error = flash('error')): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

                <!-- Süre uyarısı -->
                <?php if ($daysLeft !== null): ?>
                <div class="aho-card" style="padding:20px;margin-bottom:16px;background:<?= $daysLeft < 30 ? '#fef3c7' : '#f0fdf4' ?>;border-left:4px solid <?= $daysLeft < 30 ? '#f59e0b' : '#22c55e' ?>">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <div style="font-size:12px;color:#78350f;text-transform:uppercase">Bitiş Tarihi</div>
                            <div style="font-size:22px;font-weight:700;margin-top:4px"><?= e(date('d.m.Y', strtotime((string)$domain['expiry_date']))) ?></div>
                            <div style="font-size:13px;margin-top:4px"><?= $daysLeft ?> gün kaldı</div>
                        </div>
                        <form method="post" action="/panel/domain/<?= (int)$domain['id'] ?>/yenile">
                            <?= csrf() ?>
                            <select name="years" style="padding:8px 12px;border-radius:6px;border:1px solid #d1d5db">
                                <?php for ($y = 1; $y <= 10; $y++): ?><option value="<?= $y ?>"><?= $y ?> yıl</option><?php endfor; ?>
                            </select>
                            <button type="submit" class="aho-btn aho-btn--primary">🔄 Yenile</button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Nameservers -->
                <div class="aho-card" style="padding:20px;margin-bottom:16px">
                    <h3 style="margin:0 0 12px">🌍 Nameserver'lar</h3>
                    <form method="post" action="/panel/domain/<?= (int)$domain['id'] ?>/nameserver">
                        <?= csrf() ?>
                        <textarea name="nameservers" rows="4" style="width:100%;padding:12px;font-family:monospace;font-size:13px;border:1px solid #d1d5db;border-radius:6px" placeholder="ns1.ahost.web.tr&#10;ns2.ahost.web.tr"><?= e($domain['nameservers'] ?? '') ?></textarea>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px">
                            <small style="color:#6b7280">Her satıra bir nameserver (min 2, max 5). Değişiklik 4-24 saatte yayılır.</small>
                            <button type="submit" class="aho-btn aho-btn--primary">Kaydet</button>
                        </div>
                    </form>
                </div>

                <!-- Toggle ayarları -->
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:16px">
                    <!-- Auto Renew -->
                    <div class="aho-card" style="padding:16px">
                        <div style="font-weight:600;margin-bottom:6px">🔄 Otomatik Yenileme</div>
                        <div style="font-size:12px;color:#6b7280;margin-bottom:10px">Domain süresi dolmadan önce otomatik yenile.</div>
                        <form method="post" action="/panel/domain/<?= (int)$domain['id'] ?>/auto-renew">
                            <?= csrf() ?>
                            <button type="submit" class="aho-btn aho-btn--sm aho-btn--<?= (int)$domain['auto_renew'] === 1 ? 'primary' : 'outline' ?>" style="width:100%">
                                <?= (int)$domain['auto_renew'] === 1 ? '✓ AÇIK' : '○ Kapalı' ?>
                            </button>
                        </form>
                    </div>

                    <!-- Transfer Lock -->
                    <div class="aho-card" style="padding:16px">
                        <div style="font-weight:600;margin-bottom:6px">🔒 Transfer Kilidi</div>
                        <div style="font-size:12px;color:#6b7280;margin-bottom:10px">İzinsiz transferi engelle.</div>
                        <form method="post" action="/panel/domain/<?= (int)$domain['id'] ?>/transfer-lock">
                            <?= csrf() ?>
                            <button type="submit" class="aho-btn aho-btn--sm aho-btn--<?= (int)$domain['transfer_lock'] === 1 ? 'primary' : 'outline' ?>" style="width:100%">
                                <?= (int)$domain['transfer_lock'] === 1 ? '🔒 KİLİTLİ' : '🔓 Açık' ?>
                            </button>
                        </form>
                    </div>

                    <!-- WHOIS Privacy -->
                    <div class="aho-card" style="padding:16px">
                        <div style="font-weight:600;margin-bottom:6px">🕵 WHOIS Gizliliği</div>
                        <div style="font-size:12px;color:#6b7280;margin-bottom:10px">Kişisel bilgilerini WHOIS'ta gizle.</div>
                        <form method="post" action="/panel/domain/<?= (int)$domain['id'] ?>/whois-privacy">
                            <?= csrf() ?>
                            <button type="submit" class="aho-btn aho-btn--sm aho-btn--<?= (int)$domain['whois_privacy'] === 1 ? 'primary' : 'outline' ?>" style="width:100%">
                                <?= (int)$domain['whois_privacy'] === 1 ? '👁‍🗨 GİZLİ' : '👁 Açık' ?>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Belgeler Kısayolu -->
                <?php
                $parts = explode('.', (string)$domain['domain_name'], 2);
                $tld = $parts[1] ?? 'com';
                $reqDocs = \App\Services\Domain\TldPricingService::requiresDocuments($tld);
                if ($reqDocs['required']):
                ?>
                <div class="aho-card" style="padding:16px;margin-bottom:16px;background:#fef3c7;border-left:4px solid #f59e0b">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <strong>📄 Belge gerektiriyor</strong>
                            <p style="margin:4px 0 0;font-size:13px;color:#78350f">Bu TLD için TCKN/vergi/marka belgesi gerekli. Belgeler onaylanmadan aktif olmaz.</p>
                        </div>
                        <a href="/panel/domain/<?= (int)$domain['id'] ?>/belgeler" class="aho-btn aho-btn--primary">📄 Belgeler</a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- EPP Transfer Kodu -->
                <div class="aho-card" style="padding:20px;background:#fefce8;border-left:4px solid #eab308">
                    <h3 style="margin:0 0 8px">🔑 EPP Transfer Kodu</h3>
                    <p style="color:#78350f;margin:0 0 12px;font-size:13px">Domain'ini başka bir registrar'a taşımak için gereken kod. Alındığında transfer kilidi otomatik kapatılır.</p>
                    <?php if (!empty($domain['epp_code'])): ?>
                        <div style="background:#fff;padding:14px;border-radius:8px;display:flex;justify-content:space-between;align-items:center">
                            <code style="font-size:18px;font-weight:700"><?= e($domain['epp_code']) ?></code>
                            <button type="button" onclick="navigator.clipboard.writeText('<?= e($domain['epp_code']) ?>');this.textContent='✓ Kopyalandı'" class="aho-btn aho-btn--sm aho-btn--outline">📋 Kopyala</button>
                        </div>
                    <?php else: ?>
                        <form method="post" action="/panel/domain/<?= (int)$domain['id'] ?>/epp-al">
                            <?= csrf() ?>
                            <button type="submit" class="aho-btn aho-btn--primary">🔑 EPP Kodu Oluştur (e-postana yollanır)</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
