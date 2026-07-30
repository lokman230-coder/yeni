<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>🌐 Domain Center</h1>
            <p>Tüm kayıtlı domainlerin yönetimi, filtreleme ve düzenleme.</p>
        </div>
        <a href="/admin/domain-center/yeni" class="aho-btn aho-btn--primary">+ Yeni Domain</a>
    </div>

    <?php if (!empty($success)): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

    <!-- Metrikler -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:20px">
        <div class="aho-card" style="padding:14px">
            <div style="font-size:11px;color:var(--aho-color-ink-500)">TOPLAM</div>
            <div style="font-size:24px;font-weight:700"><?= (int)$metrics['total'] ?></div>
        </div>
        <div class="aho-card" style="padding:14px">
            <div style="font-size:11px;color:var(--aho-color-ink-500)">AKTİF</div>
            <div style="font-size:24px;font-weight:700;color:#059669"><?= (int)$metrics['active'] ?></div>
        </div>
        <div class="aho-card" style="padding:14px">
            <div style="font-size:11px;color:var(--aho-color-ink-500)">BEKLEYEN</div>
            <div style="font-size:24px;font-weight:700;color:#d97706"><?= (int)$metrics['pending'] ?></div>
        </div>
        <div class="aho-card" style="padding:14px">
            <div style="font-size:11px;color:var(--aho-color-ink-500)">SÜRESİ GEÇEN</div>
            <div style="font-size:24px;font-weight:700;color:#dc2626"><?= (int)$metrics['expired'] ?></div>
        </div>
        <div class="aho-card" style="padding:14px;background:#fef3c7">
            <div style="font-size:11px;color:#92400e">30 GÜNE BİTECEK</div>
            <div style="font-size:24px;font-weight:700;color:#92400e"><?= (int)$metrics['expiring30'] ?></div>
        </div>
    </div>

    <!-- Filtre -->
    <form method="get" class="aho-card" style="padding:16px;margin-bottom:16px;display:flex;gap:12px;align-items:end;flex-wrap:wrap">
        <div style="flex:1;min-width:220px">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Ara (domain veya e-posta)</label>
            <input type="text" name="q" value="<?= e($q) ?>" placeholder="ornek.com veya musteri@..."
                   style="width:100%;padding:8px;border:1px solid var(--aho-color-border);border-radius:6px;box-sizing:border-box">
        </div>
        <div>
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Durum</label>
            <select name="status" style="padding:8px;border:1px solid var(--aho-color-border);border-radius:6px">
                <option value="">Hepsi</option>
                <?php foreach (['active'=>'Aktif','pending'=>'Bekleyen','pending_transfer'=>'Transfer','expired'=>'Süresi Geçmiş','cancelled'=>'İptal','suspended'=>'Askıda'] as $k=>$v): ?>
                    <option value="<?= e($k) ?>" <?= $status===$k?'selected':'' ?>><?= e($v) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="aho-btn aho-btn--primary">🔍 Filtrele</button>
        <?php if ($q !== '' || $status !== ''): ?>
            <a href="/admin/domain-center" class="aho-btn aho-btn--ghost">Temizle</a>
        <?php endif; ?>
    </form>

    <!-- Liste -->
    <div class="aho-card" style="padding:0;overflow:auto">
        <?php if (empty($domains)): ?>
            <div style="padding:40px;text-align:center;color:var(--aho-color-ink-500)">
                <?= ($q !== '' || $status !== '') ? 'Filtreye uyan sonuç yok.' : 'Henüz domain yok.' ?>
            </div>
        <?php else: ?>
        <table style="width:100%;border-collapse:collapse;font-size:14px">
            <thead style="background:var(--aho-color-ink-50);text-align:left">
                <tr>
                    <th style="padding:12px 16px">Domain</th>
                    <th style="padding:12px 16px">Müşteri</th>
                    <th style="padding:12px 16px">Registrar</th>
                    <th style="padding:12px 16px">Bitiş</th>
                    <th style="padding:12px 16px;text-align:center">Auto</th>
                    <th style="padding:12px 16px;text-align:center">Durum</th>
                    <th style="padding:12px 16px;text-align:right">İşlem</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($domains as $d):
                $daysLeft = !empty($d['expiry_date']) ? (int) ((strtotime((string)$d['expiry_date']) - time()) / 86400) : null;
                $badge = match ($d['status']) {
                    'active'          => ['✅ Aktif',       '#059669', '#d1fae5'],
                    'pending'         => ['⏳ Bekliyor',    '#d97706', '#fef3c7'],
                    'pending_transfer'=> ['↔ Transfer',    '#d97706', '#fef3c7'],
                    'expired'         => ['⚠️ Süresi geçti','#dc2626', '#fee2e2'],
                    'cancelled'       => ['❌ İptal',        '#6b7280', '#f3f4f6'],
                    'suspended'       => ['⏸ Askıda',      '#dc2626', '#fee2e2'],
                    default           => [$d['status'],      '#6b7280', '#f3f4f6'],
                };
            ?>
                <tr style="border-top:1px solid var(--aho-color-border)">
                    <td style="padding:12px 16px;font-weight:600"><?= e($d['domain_name']) ?></td>
                    <td style="padding:12px 16px;font-size:13px">
                        <?= e(trim((string)$d['customer_name']) ?: '—') ?>
                        <div style="font-size:11px;color:var(--aho-color-ink-500)"><?= e($d['customer_email'] ?? '') ?></div>
                    </td>
                    <td style="padding:12px 16px;font-size:13px;color:var(--aho-color-ink-600)"><?= e($d['registrar_name'] ?? '—') ?></td>
                    <td style="padding:12px 16px;font-size:13px">
                        <?= $d['expiry_date'] ? e(date('d.m.Y', strtotime((string)$d['expiry_date']))) : '—' ?>
                        <?php if ($daysLeft !== null && $daysLeft > 0 && $daysLeft < 30): ?>
                            <div style="font-size:11px;color:#d97706"><?= $daysLeft ?> gün</div>
                        <?php elseif ($daysLeft !== null && $daysLeft <= 0): ?>
                            <div style="font-size:11px;color:#dc2626">geçti</div>
                        <?php endif; ?>
                    </td>
                    <td style="padding:12px 16px;text-align:center"><?= (int)$d['auto_renew']===1 ? '✓' : '—' ?></td>
                    <td style="padding:12px 16px;text-align:center">
                        <span style="padding:3px 8px;font-size:11px;border-radius:10px;color:<?= $badge[1] ?>;background:<?= $badge[2] ?>">
                            <?= e($badge[0]) ?>
                        </span>
                    </td>
                    <td style="padding:12px 16px;text-align:right">
                        <a href="/admin/domain-center/<?= (int)$d['id'] ?>" style="color:var(--aho-color-primary-600);text-decoration:none;font-size:13px;font-weight:600">Düzenle →</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php $view->endSection(); ?>
