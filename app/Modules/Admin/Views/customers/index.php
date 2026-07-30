<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');

$totalCustomers = count($customers);
$activeCustomers = 0;
$verifiedCustomers = 0;
foreach ($customers as $customerRow) {
    if (($customerRow['status'] ?? '') === 'active') $activeCustomers++;
    if (!empty($customerRow['email_verified_at'])) $verifiedCustomers++;
}
?>
<div class="aho-admin-page aho-customers-page">
    <div class="aho-admin-page__header aho-customers-hero">
        <div>
            <span class="aho-customers-hero__eyebrow">CRM</span>
            <h1>Musteriler</h1>
            <p>Musteri hesaplarini, iletisim bilgilerini ve durumlarini tek ekrandan yonetin.</p>
        </div>
        <div class="aho-admin-page__actions">
            <a href="/admin/musteriler/yeni" class="aho-btn aho-btn--primary">+ Yeni Musteri</a>
        </div>
    </div>

    <?php if ($success = flash('success')): ?><div class="aho-alert aho-alert--success" style="margin-bottom:12px"><?= e($success) ?></div><?php endif; ?>

    <div class="aho-customers-metrics">
        <div class="aho-customers-metric">
            <span>Listelenen</span>
            <strong><?= (int)$totalCustomers ?></strong>
        </div>
        <div class="aho-customers-metric">
            <span>Aktif</span>
            <strong><?= (int)$activeCustomers ?></strong>
        </div>
        <div class="aho-customers-metric">
            <span>Dogrulanmis</span>
            <strong><?= (int)$verifiedCustomers ?></strong>
        </div>
    </div>

    <form method="get" class="aho-customers-filter">
        <div class="aho-customers-filter__search">
            <span>Search</span>
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="E-posta, ad, soyad, telefon, firma..." />
        </div>
        <button type="submit" class="aho-btn aho-btn--primary">Ara</button>
    </form>

    <div class="aho-customers-table-card">
        <table class="aho-customers-table">
            <thead>
                <tr>
                    <th>Musteri</th>
                    <th>Iletisim</th>
                    <th>Firma</th>
                    <th>Durum</th>
                    <th>Kayit</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $c):
                    $fullName = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
                    $fullName = $fullName !== '' ? $fullName : 'Isimsiz Musteri';
                    $initials = mb_strtoupper(mb_substr($fullName, 0, 1));
                    $status = $c['status'] ?? 'pending';
                    $statusClass = match ($status) {
                        'active' => 'is-active',
                        'suspended' => 'is-suspended',
                        'closed' => 'is-closed',
                        default => 'is-pending',
                    };
                    $statusLabel = match ($status) {
                        'active' => 'Aktif',
                        'suspended' => 'Askida',
                        'closed' => 'Kapali',
                        default => 'Bekliyor',
                    };
                ?>
                    <tr>
                        <td>
                            <div class="aho-customer-cell">
                                <span class="aho-customer-avatar"><?= e($initials) ?></span>
                                <span>
                                    <strong><?= e($fullName) ?></strong>
                                    <small>#<?= (int)$c['id'] ?></small>
                                </span>
                            </div>
                        </td>
                        <td>
                            <div class="aho-customer-contact">
                                <a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a>
                                <small><?= e($c['phone'] ?? '-') ?></small>
                            </div>
                        </td>
                        <td>
                            <span class="aho-customer-company"><?= e(($c['company'] ?? '') ?: '-') ?></span>
                        </td>
                        <td>
                            <div class="aho-customer-status">
                                <span class="aho-customer-badge <?= e($statusClass) ?>"><?= e($statusLabel) ?></span>
                                <?php if (!empty($c['email_verified_at'])): ?>
                                    <span class="aho-customer-verified" title="E-posta dogrulanmis">Mail</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="aho-customer-date"><?= e(substr((string)$c['created_at'], 0, 10)) ?></span>
                        </td>
                        <td class="aho-customers-table__actions">
                            <a href="/admin/musteriler/<?= (int)$c['id'] ?>" class="aho-customer-detail">Detay</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$customers): ?>
                    <tr>
                        <td colspan="6">
                            <div class="aho-customers-empty">Kayit bulunamadi.</div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $view->endSection(); ?>
