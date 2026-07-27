<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div><h1>👥 Müşteriler</h1><p>Toplam listedeki müşteriler ve hızlı işlemler.</p></div>
        <div class="aho-admin-page__actions">
            <a href="/admin/musteriler/yeni" class="aho-btn aho-btn--primary">+ Yeni Müşteri</a>
        </div>
    </div>
    <?php if ($success = flash('success')): ?><div class="aho-alert aho-alert--success" style="margin-bottom:12px"><?= e($success) ?></div><?php endif; ?>

    <form method="get" class="aho-card" style="padding:12px 16px;margin-bottom:16px">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="E-posta, ad, soyad, telefon, firma..." style="width:400px">
        <button type="submit" class="aho-btn aho-btn--primary">Ara</button>
    </form>

    <div class="aho-card">
        <table class="aho-table">
            <thead>
                <tr><th>#</th><th>Ad Soyad</th><th>E-posta</th><th>Telefon</th><th>Firma</th><th>Durum</th><th>Kayıt</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $c): ?>
                    <tr>
                        <td>#<?= (int)$c['id'] ?></td>
                        <td><strong><?= e(trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''))) ?></strong></td>
                        <td><?= e($c['email']) ?></td>
                        <td><?= e($c['phone'] ?? '') ?></td>
                        <td><?= e($c['company'] ?? '') ?></td>
                        <td>
                            <?php
                            $status = $c['status'] ?? 'pending';
                            $statusBadge = match ($status) {
                                'active' => '<span class="aho-badge aho-badge--success">Aktif</span>',
                                'suspended' => '<span class="aho-badge aho-badge--danger">Askıda</span>',
                                'closed' => '<span class="aho-badge">Kapalı</span>',
                                default => '<span class="aho-badge aho-badge--warning">Bekliyor</span>',
                            };
                            echo $statusBadge;
                            ?>
                            <?php if (!empty($c['email_verified_at'])): ?>
                                <span class="aho-badge aho-badge--info">✉</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e(substr((string)$c['created_at'], 0, 10)) ?></td>
                        <td>
                            <a href="/admin/musteriler/<?= (int)$c['id'] ?>" class="aho-btn aho-btn--sm aho-btn--outline">Detay</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$customers): ?>
                    <tr><td colspan="8" style="text-align:center;padding:24px;color:var(--aho-muted)">Kayıt yok.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $view->endSection(); ?>
