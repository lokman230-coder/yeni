<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-customer-panel" style="padding:32px 0;background:#f9fafb;min-height:calc(100vh - 200px)">
    <div class="aho-container">
        <div style="display:grid;grid-template-columns:220px 1fr;gap:24px" class="aho-customer-layout">
            <?= $view->include('customer::_sidebar') ?>
            <div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
                    <div>
                        <h1 style="margin:0;font-size:24px">🌐 Domainlerim</h1>
                        <p style="color:var(--aho-color-ink-600);margin:4px 0 0">Kayıtlı domainleriniz ve süreleri.</p>
                    </div>
                    <a href="/domain" class="aho-btn aho-btn--primary">+ Yeni Domain</a>
                </div>

                <?php if (empty($domains)): ?>
                    <div class="aho-card" style="padding:60px;text-align:center">
                        <div style="font-size:56px">🌐</div>
                        <h3 style="margin:12px 0 8px">Henüz domaininiz yok</h3>
                        <p style="color:var(--aho-color-ink-600);margin:0 0 20px">İstediğiniz domain adını sorgulayın, hemen kayıt edin.</p>
                        <a href="/domain" class="aho-btn aho-btn--primary">Domain Sorgula</a>
                    </div>
                <?php else: ?>
                    <div class="aho-card" style="padding:0;overflow:auto">
                        <table style="width:100%;border-collapse:collapse;font-size:14px">
                            <thead style="background:var(--aho-color-ink-50);text-align:left">
                                <tr>
                                    <th style="padding:12px 16px">Domain</th>
                                    <th style="padding:12px 16px">Registrar</th>
                                    <th style="padding:12px 16px">Bitiş</th>
                                    <th style="padding:12px 16px;text-align:center">Auto Renew</th>
                                    <th style="padding:12px 16px;text-align:center">Durum</th>
                                    <th style="padding:12px 16px;text-align:right">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($domains as $d): ?>
                                <?php
                                $badge = match ($d['status']) {
                                    'active'          => ['✅ Aktif',      '#059669', '#d1fae5'],
                                    'pending'         => ['⏳ Kayıt',     '#d97706', '#fef3c7'],
                                    'pending_transfer'=> ['↔ Transfer',   '#d97706', '#fef3c7'],
                                    'expired'         => ['⚠️ Süresi geçti','#dc2626', '#fee2e2'],
                                    'cancelled'       => ['❌ İptal',       '#6b7280', '#f3f4f6'],
                                    'suspended'       => ['⏸ Askıda',     '#dc2626', '#fee2e2'],
                                    default           => [$d['status'],     '#6b7280', '#f3f4f6'],
                                };
                                $daysLeft = null;
                                if (!empty($d['expiry_date'])) {
                                    $daysLeft = (int) ((strtotime((string)$d['expiry_date']) - time()) / 86400);
                                }
                                ?>
                                <tr style="border-top:1px solid var(--aho-color-border)">
                                    <td style="padding:12px 16px;font-weight:600"><?= e($d['domain_name']) ?></td>
                                    <td style="padding:12px 16px;font-size:13px;color:var(--aho-color-ink-600)"><?= e($d['registrar_name'] ?? '—') ?></td>
                                    <td style="padding:12px 16px">
                                        <?php if ($d['expiry_date']): ?>
                                            <?= e(date('d.m.Y', strtotime((string)$d['expiry_date']))) ?>
                                            <?php if ($daysLeft !== null && $daysLeft < 30 && $daysLeft > 0): ?>
                                                <div style="font-size:11px;color:#d97706"><?= $daysLeft ?> gün</div>
                                            <?php elseif ($daysLeft !== null && $daysLeft <= 0): ?>
                                                <div style="font-size:11px;color:#dc2626">⚠️ Süresi geçti</div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color:var(--aho-color-ink-400)">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:12px 16px;text-align:center">
                                        <?= (int)$d['auto_renew'] === 1 ? '<span style="color:#059669">✓</span>' : '<span style="color:var(--aho-color-ink-400)">—</span>' ?>
                                    </td>
                                    <td style="padding:12px 16px;text-align:center">
                                        <span style="padding:3px 10px;font-size:12px;border-radius:12px;color:<?= $badge[1] ?>;background:<?= $badge[2] ?>">
                                            <?= e($badge[0]) ?>
                                        </span>
                                    </td>
                                    <td style="padding:12px 16px;text-align:right">
                                        <a href="/panel/domain/<?= (int)$d['id'] ?>" style="color:var(--aho-color-primary-600);text-decoration:none;font-size:13px;font-weight:600">Yönet →</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
