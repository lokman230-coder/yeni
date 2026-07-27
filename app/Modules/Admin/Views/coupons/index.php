<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>🎟️ Kuponlar</h1>
            <p>İndirim kuponları — yüzde veya sabit tutar, kullanım limitleri, tarih aralığı.</p>
        </div>
        <a href="/admin/kuponlar/yeni" class="aho-btn aho-btn--primary">+ Yeni Kupon</a>
    </div>

    <?php if (!empty($success)): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

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
            <div style="font-size:11px;color:var(--aho-color-ink-500)">TOPLAM KULLANIM</div>
            <div style="font-size:24px;font-weight:700"><?= (int)$metrics['uses'] ?></div>
        </div>
        <div class="aho-card" style="padding:14px">
            <div style="font-size:11px;color:var(--aho-color-ink-500)">SÜRESİ DOLMUŞ</div>
            <div style="font-size:24px;font-weight:700;color:#dc2626"><?= (int)$metrics['expired'] ?></div>
        </div>
    </div>

    <form method="get" class="aho-card" style="padding:16px;margin-bottom:16px;display:flex;gap:12px;align-items:end;flex-wrap:wrap">
        <div style="flex:1;min-width:220px">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Ara (kod veya isim)</label>
            <input type="text" name="q" value="<?= e($q) ?>" placeholder="WELCOME10..."
                   style="width:100%;padding:8px;border:1px solid var(--aho-color-border);border-radius:6px;box-sizing:border-box">
        </div>
        <div>
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Durum</label>
            <select name="status" style="padding:8px;border:1px solid var(--aho-color-border);border-radius:6px">
                <option value="">Hepsi</option>
                <option value="active"   <?= $status==='active'?'selected':'' ?>>Aktif</option>
                <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Pasif</option>
            </select>
        </div>
        <button type="submit" class="aho-btn aho-btn--primary">🔍 Filtrele</button>
        <?php if ($q || $status): ?>
            <a href="/admin/kuponlar" class="aho-btn aho-btn--ghost">Temizle</a>
        <?php endif; ?>
    </form>

    <div class="aho-card" style="padding:0;overflow:auto">
        <?php if (empty($coupons)): ?>
            <div style="padding:60px;text-align:center">
                <div style="font-size:48px">🎟️</div>
                <h3 style="margin:12px 0 8px">Kupon bulunamadı</h3>
                <a href="/admin/kuponlar/yeni" class="aho-btn aho-btn--primary">İlk kuponu oluştur →</a>
            </div>
        <?php else: ?>
        <table style="width:100%;border-collapse:collapse;font-size:14px">
            <thead style="background:var(--aho-color-ink-50);text-align:left">
                <tr>
                    <th style="padding:12px 16px">Kod</th>
                    <th style="padding:12px 16px">İsim</th>
                    <th style="padding:12px 16px;text-align:right">Değer</th>
                    <th style="padding:12px 16px;text-align:right">Kullanım</th>
                    <th style="padding:12px 16px">Bitiş</th>
                    <th style="padding:12px 16px;text-align:center">Durum</th>
                    <th style="padding:12px 16px;text-align:right">İşlem</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($coupons as $c):
                $isExpired = !empty($c['ends_at']) && strtotime((string)$c['ends_at']) < time();
                $isExhausted = $c['usage_limit'] && (int)$c['usage_count'] >= (int)$c['usage_limit'];
                $status = !$c['is_active'] ? ['◌ Pasif','#6b7280','#f3f4f6']
                        : ($isExpired ? ['⚠️ Süresi Geçti','#dc2626','#fee2e2']
                        : ($isExhausted ? ['💯 Doldu','#d97706','#fef3c7']
                        : ['✅ Aktif','#059669','#d1fae5']));
            ?>
                <tr style="border-top:1px solid var(--aho-color-border)">
                    <td style="padding:12px 16px"><code style="font-weight:700;color:var(--aho-color-primary-600);font-size:14px"><?= e($c['code']) ?></code></td>
                    <td style="padding:12px 16px;font-size:13px;color:var(--aho-color-ink-700)"><?= e($c['name']) ?></td>
                    <td style="padding:12px 16px;text-align:right;font-weight:600">
                        <?php if ($c['type'] === 'percent'): ?>
                            %<?= number_format((float)$c['value'], 1, ',', '.') ?>
                        <?php else: ?>
                            <?= number_format((float)$c['value'], 2, ',', '.') ?> <?= e($c['currency'] ?? 'TRY') ?>
                        <?php endif; ?>
                    </td>
                    <td style="padding:12px 16px;text-align:right">
                        <?= (int)$c['usage_count'] ?> / <?= $c['usage_limit'] ? (int)$c['usage_limit'] : '∞' ?>
                    </td>
                    <td style="padding:12px 16px;font-size:13px;color:var(--aho-color-ink-600)">
                        <?= $c['ends_at'] ? e(date('d.m.Y', strtotime((string)$c['ends_at']))) : 'Süresiz' ?>
                    </td>
                    <td style="padding:12px 16px;text-align:center">
                        <span style="padding:3px 10px;font-size:11px;border-radius:10px;color:<?= $status[1] ?>;background:<?= $status[2] ?>">
                            <?= e($status[0]) ?>
                        </span>
                    </td>
                    <td style="padding:12px 16px;text-align:right">
                        <a href="/admin/kuponlar/<?= (int)$c['id'] ?>" style="color:var(--aho-color-primary-600);text-decoration:none;font-size:13px;font-weight:600">Düzenle →</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php $view->endSection(); ?>
