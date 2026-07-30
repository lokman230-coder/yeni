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
                        <h1 style="margin:0;font-size:24px">🖥️ Hizmetlerim</h1>
                        <p style="color:var(--aho-color-ink-600);margin:4px 0 0">Aktif hosting ve sunucu hizmetleriniz.</p>
                    </div>
                    <a href="/hosting" class="aho-btn aho-btn--primary">+ Yeni Hosting</a>
                </div>

                <?php if (empty($services)): ?>
                    <div class="aho-card" style="padding:60px;text-align:center">
                        <div style="font-size:56px">🌱</div>
                        <h3 style="margin:12px 0 8px">Henüz hizmetiniz yok</h3>
                        <p style="color:var(--aho-color-ink-600);margin:0 0 20px">İlk hosting paketinizi hemen alın, dakikalar içinde kurulum tamamlansın.</p>
                        <a href="/hosting" class="aho-btn aho-btn--primary">Hosting Paketlerini Gör</a>
                    </div>
                <?php else: ?>
                    <div class="aho-card" style="padding:0;overflow:auto">
                        <table style="width:100%;border-collapse:collapse;font-size:14px">
                            <thead style="background:var(--aho-color-ink-50);text-align:left">
                                <tr>
                                    <th style="padding:12px 16px">Domain</th>
                                    <th style="padding:12px 16px">Paket</th>
                                    <th style="padding:12px 16px">Yenileme</th>
                                    <th style="padding:12px 16px;text-align:center">Durum</th>
                                    <th style="padding:12px 16px;text-align:right">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($services as $s): ?>
                                <tr style="border-top:1px solid var(--aho-color-border)">
                                    <td style="padding:12px 16px">
                                        <div style="font-weight:600"><?= e($s['domain']) ?></div>
                                        <div style="font-size:12px;color:var(--aho-color-ink-500)"><?= e($s['username'] ?? '-') ?></div>
                                    </td>
                                    <td style="padding:12px 16px"><?= e($s['product_name'] ?? $s['package'] ?? '—') ?></td>
                                    <td style="padding:12px 16px">
                                        <?php if ($s['next_due_date']): ?>
                                            <?= e(date('d.m.Y', strtotime((string)$s['next_due_date']))) ?>
                                            <?php
                                            $days = (strtotime((string)$s['next_due_date']) - time()) / 86400;
                                            if ($days < 30 && $days > 0): ?>
                                                <div style="font-size:11px;color:#d97706"><?= (int)$days ?> gün kaldı</div>
                                            <?php elseif ($days <= 0): ?>
                                                <div style="font-size:11px;color:#dc2626">⚠️ Süresi geçti</div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color:var(--aho-color-ink-400)">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:12px 16px;text-align:center">
                                        <?php
                                        $badge = match ($s['status']) {
                                            'active'     => ['✅ Aktif',     '#059669', '#d1fae5'],
                                            'pending'    => ['⏳ Kuruluyor', '#d97706', '#fef3c7'],
                                            'suspended'  => ['⏸ Askıda',   '#dc2626', '#fee2e2'],
                                            'terminated' => ['❌ Kapatıldı', '#6b7280', '#f3f4f6'],
                                            default      => [$s['status'],   '#6b7280', '#f3f4f6'],
                                        };
                                        ?>
                                        <span style="padding:3px 10px;font-size:12px;border-radius:12px;color:<?= $badge[1] ?>;background:<?= $badge[2] ?>">
                                            <?= e($badge[0]) ?>
                                        </span>
                                    </td>
                                    <td style="padding:12px 16px;text-align:right">
                                        <a href="/panel/hizmet/<?= (int)$s['id'] ?>" style="color:var(--aho-color-primary-600);text-decoration:none;font-size:13px;font-weight:600">Detay →</a>
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
