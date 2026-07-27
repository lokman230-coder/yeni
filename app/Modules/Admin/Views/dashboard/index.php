<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
$admin = \App\Services\Auth\AuthService::admin();
$s = $stats;
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>👋 Hoş geldin, <?= e($admin['full_name'] ?? ($admin['first_name'] ?? 'Yönetici')) ?></h1>
            <p>Sistemin genel görünümü — <?= date('d.m.Y H:i') ?></p>
        </div>
        <div class="aho-admin-page__actions" style="display:flex;gap:8px">
            <a href="/" target="_blank" class="aho-btn aho-btn--outline aho-btn--sm">🌐 Siteyi Görüntüle</a>
            <a href="/admin/urun-merkezi/yeni" class="aho-btn aho-btn--primary aho-btn--sm">+ Yeni Ürün</a>
        </div>
    </div>

    <!-- Ana metrik grid -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-bottom:20px">
        <div class="aho-card" style="padding:18px">
            <div style="font-size:11px;color:var(--aho-color-ink-500)">TOPLAM MÜŞTERİ</div>
            <div style="font-size:28px;font-weight:700;margin-top:4px"><?= (int)$s['customers_total'] ?></div>
            <div style="font-size:12px;color:var(--aho-color-ink-500)"><?= (int)$s['customers_active'] ?> aktif</div>
        </div>
        <div class="aho-card" style="padding:18px">
            <div style="font-size:11px;color:var(--aho-color-ink-500)">BUGÜN SİPARİŞ</div>
            <div style="font-size:28px;font-weight:700;margin-top:4px;color:#0891b2"><?= (int)$s['orders_today'] ?></div>
            <div style="font-size:12px;color:var(--aho-color-ink-500)">Ay toplam: <?= (int)$s['orders_month'] ?></div>
        </div>
        <div class="aho-card" style="padding:18px;background:linear-gradient(135deg,#059669 0%,#0891b2 100%);color:#fff">
            <div style="font-size:11px;opacity:.9">AYLIK GELİR</div>
            <div style="font-size:22px;font-weight:700;margin-top:4px"><?= number_format($s['revenue_month'], 2, ',', '.') ?> ₺</div>
            <div style="font-size:12px;opacity:.85">Bugün: <?= number_format($s['revenue_today'], 2, ',', '.') ?> ₺</div>
        </div>
        <div class="aho-card" style="padding:18px;<?= $s['invoices_unpaid'] > 0 ? 'border-left:4px solid #d97706' : '' ?>">
            <div style="font-size:11px;color:var(--aho-color-ink-500)">ÖDENMEMİŞ FATURA</div>
            <div style="font-size:28px;font-weight:700;margin-top:4px;color:<?= $s['invoices_unpaid'] > 0 ? '#d97706' : 'inherit' ?>"><?= (int)$s['invoices_unpaid'] ?></div>
            <div style="font-size:12px;color:var(--aho-color-ink-500)"><?= number_format($s['unpaid_total'], 2, ',', '.') ?> ₺</div>
        </div>
    </div>

    <!-- İkinci metrik satırı -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:24px">
        <a href="/admin/hosting-sunucu" class="aho-card" style="padding:14px;text-decoration:none;color:inherit">
            <div style="font-size:11px;color:var(--aho-color-ink-500)">🖥️ AKTİF HİZMET</div>
            <div style="font-size:22px;font-weight:700;margin-top:4px"><?= (int)$s['services_active'] ?></div>
        </a>
        <a href="/admin/domain-center" class="aho-card" style="padding:14px;text-decoration:none;color:inherit">
            <div style="font-size:11px;color:var(--aho-color-ink-500)">🌐 AKTİF DOMAIN</div>
            <div style="font-size:22px;font-weight:700;margin-top:4px"><?= (int)$s['domains_active'] ?></div>
        </a>
        <a href="/admin/destek-merkezi" class="aho-card" style="padding:14px;text-decoration:none;color:inherit;<?= $s['tickets_open'] > 0 ? 'border-left:3px solid #dc2626' : '' ?>">
            <div style="font-size:11px;color:var(--aho-color-ink-500)">🎧 AÇIK TİCKET</div>
            <div style="font-size:22px;font-weight:700;margin-top:4px;color:<?= $s['tickets_open'] > 0 ? '#dc2626' : 'inherit' ?>"><?= (int)$s['tickets_open'] ?></div>
        </a>
        <a href="/admin/referral" class="aho-card" style="padding:14px;text-decoration:none;color:inherit;<?= $s['payouts_pending'] > 0 ? 'border-left:3px solid #d97706' : '' ?>">
            <div style="font-size:11px;color:var(--aho-color-ink-500)">💸 BEKLEYEN PAYOUT</div>
            <div style="font-size:22px;font-weight:700;margin-top:4px;color:<?= $s['payouts_pending'] > 0 ? '#d97706' : 'inherit' ?>"><?= (int)$s['payouts_pending'] ?></div>
        </a>
    </div>

    <?php if (!$onboardingDone): ?>
    <!-- Onboarding checklist — tüm maddeler tamamlanınca gizlenir -->
    <?php $done = count(array_filter($onboarding, fn($i)=>$i['done'])); $total = count($onboarding); $pct = (int) round($done / $total * 100); ?>
    <div class="aho-card" style="padding:20px;margin-bottom:20px;background:linear-gradient(135deg,#fff 0%,#f0f9ff 100%);border-left:4px solid #0ea5e9">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
            <div>
                <h3 style="margin:0;font-size:16px">🎯 Kurulum Sonrası Yapılacaklar</h3>
                <div style="font-size:12px;color:var(--aho-color-ink-500);margin-top:2px">
                    <?= $done ?> / <?= $total ?> tamamlandı — sistemin tam çalışması için önerilir
                </div>
            </div>
            <div style="font-size:22px;font-weight:700;color:<?= $pct === 100 ? '#059669' : '#0891b2' ?>"><?= $pct ?>%</div>
        </div>
        <!-- Progress bar -->
        <div style="background:#e5e7eb;border-radius:10px;height:6px;overflow:hidden;margin-bottom:16px">
            <div style="width:<?= $pct ?>%;height:100%;background:<?= $pct === 100 ? '#059669' : '#0ea5e9' ?>;transition:width .5s"></div>
        </div>
        <!-- Adımlar -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:8px">
            <?php foreach ($onboarding as $item): ?>
                <a href="<?= e($item['url']) ?>" style="display:flex;gap:10px;align-items:flex-start;padding:12px;background:<?= $item['done'] ? '#f0fdf4' : '#fff' ?>;border:1px solid <?= $item['done'] ? '#a7f3d0' : 'var(--aho-color-border)' ?>;border-radius:8px;text-decoration:none;color:inherit">
                    <div style="font-size:20px;flex-shrink:0"><?= $item['done'] ? '✅' : '⭕' ?></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:600;font-size:13px;color:<?= $item['done'] ? '#065f46' : 'var(--aho-color-ink-800)' ?>"><?= e($item['label']) ?></div>
                        <div style="font-size:11px;color:var(--aho-color-ink-500);margin-top:2px;line-height:1.4"><?= e($item['hint']) ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- İki kolon: Son siparişler + Açık ticketlar -->
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px">
        <!-- Son siparişler -->
        <div class="aho-card" style="padding:0;overflow:auto">
            <div style="padding:14px 20px;border-bottom:1px solid var(--aho-color-border);display:flex;justify-content:space-between;align-items:center">
                <h3 style="margin:0;font-size:15px">📦 Son Siparişler</h3>
                <a href="/admin/siparisler" style="font-size:12px;color:var(--aho-color-primary-600);text-decoration:none">Tümü →</a>
            </div>
            <?php if (empty($recentOrders)): ?>
                <div style="padding:32px;text-align:center;color:var(--aho-color-ink-500);font-size:13px">Henüz sipariş yok.</div>
            <?php else: ?>
            <table style="width:100%;border-collapse:collapse;font-size:13px">
                <?php foreach ($recentOrders as $o):
                    $badge = match ($o['status']) {
                        'paid' => ['✅ Ödendi',       '#059669'],
                        'pending' => ['⏳ Ödeme Bek.', '#d97706'],
                        'active' => ['✅ Aktif',      '#059669'],
                        'failed' => ['❌ Başarısız',  '#dc2626'],
                        default => [$o['status'],       '#6b7280'],
                    };
                ?>
                    <tr style="border-top:1px solid var(--aho-color-border)">
                        <td style="padding:10px 20px;font-family:monospace;font-size:12px;font-weight:600"><?= e($o['order_number']) ?></td>
                        <td style="padding:10px 20px;font-size:12px;color:var(--aho-color-ink-600)">
                            <?= e(trim((string)$o['customer_name']) ?: '—') ?>
                            <div style="font-size:11px;color:var(--aho-color-ink-500)"><?= e($o['email'] ?? '') ?></div>
                        </td>
                        <td style="padding:10px 20px;text-align:right;font-weight:600"><?= number_format((float)$o['total'], 2, ',', '.') ?> <?= e($o['currency']) ?></td>
                        <td style="padding:10px 20px;text-align:center;font-size:11px;color:<?= $badge[1] ?>;font-weight:600"><?= e($badge[0]) ?></td>
                        <td style="padding:10px 20px;text-align:right;font-size:11px;color:var(--aho-color-ink-500)"><?= e(date('d.m H:i', strtotime((string)$o['created_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>

        <!-- Açık ticketlar -->
        <div class="aho-card" style="padding:0;overflow:auto">
            <div style="padding:14px 20px;border-bottom:1px solid var(--aho-color-border);display:flex;justify-content:space-between;align-items:center">
                <h3 style="margin:0;font-size:15px">🎧 Açık Talepler</h3>
                <a href="/admin/destek-merkezi" style="font-size:12px;color:var(--aho-color-primary-600);text-decoration:none">Tümü →</a>
            </div>
            <?php if (empty($recentTickets)): ?>
                <div style="padding:32px;text-align:center;color:var(--aho-color-ink-500);font-size:13px">Açık ticket yok. 🎉</div>
            <?php else: ?>
            <?php foreach ($recentTickets as $t):
                $prioClr = match ($t['priority']) {
                    'urgent' => '#dc2626', 'high' => '#d97706', 'medium' => '#0891b2', default => '#6b7280',
                };
            ?>
                <a href="/admin/destek-merkezi/<?= (int)$t['id'] ?>" style="display:block;padding:12px 20px;border-top:1px solid var(--aho-color-border);text-decoration:none;color:inherit">
                    <div style="display:flex;justify-content:space-between;gap:8px;font-size:11px;color:var(--aho-color-ink-500);margin-bottom:4px">
                        <span style="font-family:monospace"><?= e($t['ticket_number']) ?></span>
                        <span style="color:<?= $prioClr ?>;font-weight:600"><?= strtoupper($t['priority']) ?></span>
                    </div>
                    <div style="font-weight:600;font-size:13px;color:var(--aho-color-ink-800)"><?= e(mb_substr($t['subject'], 0, 50)) ?></div>
                    <div style="font-size:11px;color:var(--aho-color-ink-500);margin-top:2px"><?= e($t['customer_email'] ?? '') ?></div>
                </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $view->endSection(); ?>
