<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>BTK / Yer Sağlayıcı Raporu</h1>
            <p>5651 sayılı kanun kapsamında müşteri ve hizmet raporunu CSV olarak dışa aktarın.</p>
        </div>
    </div>

    <div class="aho-feature-grid" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:var(--aho-space-4);margin-bottom:var(--aho-space-6)">
        <a href="/admin/btk-raporu/hosting" class="aho-card aho-card--hover" style="text-decoration:none;color:inherit;text-align:center">
            <div class="aho-feature-card__icon">🖥️</div>
            <h3>Hosting Hesapları</h3>
            <p style="color:var(--aho-color-ink-500);font-size:var(--aho-text-sm);margin:0">
                Tüm hosting hesapları, domain, IP, müşteri, kayıt tarihi ve iletişim bilgileri.
            </p>
        </a>
        <a href="/admin/btk-raporu/domains" class="aho-card aho-card--hover" style="text-decoration:none;color:inherit;text-align:center">
            <div class="aho-feature-card__icon">🌐</div>
            <h3>Domainler</h3>
            <p style="color:var(--aho-color-ink-500);font-size:var(--aho-text-sm);margin:0">
                Tüm domain kayıtları, sahipleri, kayıt ve bitiş tarihleri.
            </p>
        </a>
        <a href="/admin/btk-raporu/customers" class="aho-card aho-card--hover" style="text-decoration:none;color:inherit;text-align:center">
            <div class="aho-feature-card__icon">👥</div>
            <h3>Müşteriler</h3>
            <p style="color:var(--aho-color-ink-500);font-size:var(--aho-text-sm);margin:0">
                Aktif müşteriler, TC/VKN, adres, iletişim, kayıt tarihi.
            </p>
        </a>
    </div>

    <div class="aho-alert aho-alert--info">
        📌 CSV dosyaları UTF-8 BOM ile üretilir (Excel uyumlu), ayırıcı olarak <code>;</code> kullanılır.
    </div>

    <?php if (!empty($exports)): ?>
        <h3 style="margin-top:var(--aho-space-6);margin-bottom:var(--aho-space-3)">Geçmiş Dışa Aktarımlar</h3>
        <div class="aho-card" style="padding:0;overflow:hidden">
            <table class="aho-admin-table">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Tip</th>
                        <th>Satır Sayısı</th>
                        <th>Boyut</th>
                        <th>Dosya</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exports as $ex): ?>
                        <tr>
                            <td><?= e($ex['created_at']) ?></td>
                            <td><span class="aho-admin-badge aho-admin-badge--info"><?= e($ex['type']) ?></span></td>
                            <td><?= (int)$ex['row_count'] ?></td>
                            <td><?= round(($ex['size_bytes'] ?? 0) / 1024, 1) ?> KB</td>
                            <td style="font-family:monospace;font-size:var(--aho-text-xs);color:var(--aho-color-ink-500)">
                                <?= e(basename($ex['file_path'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php $view->endSection(); ?>
