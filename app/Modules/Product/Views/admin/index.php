<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
$success = flash('success');
$error = flash('error');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>Ürün Merkezi</h1>
            <p>Hosting, VPS, domain, builder ve diğer ürünlerinizi yönetin.</p>
        </div>
        <div class="aho-admin-page__actions">
            <a href="/admin/urun-merkezi/yeni" class="aho-btn aho-btn--primary">+ Yeni Ürün</a>
        </div>
    </div>

    <?php if ($success): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

    <!-- Filtre -->
    <div class="aho-card" style="margin-bottom:var(--aho-space-4)">
        <form method="get" style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:var(--aho-space-3);align-items:end">
            <div class="aho-form-group" style="margin:0">
                <label class="aho-form-label">Arama</label>
                <input type="text" name="q" value="<?= e($filters['q'] ?? '') ?>" class="aho-form-input" placeholder="Ürün adı, slug...">
            </div>
            <div class="aho-form-group" style="margin:0">
                <label class="aho-form-label">Tip</label>
                <select name="type" class="aho-form-select">
                    <option value="">Tümü</option>
                    <?php foreach ($types as $k => $v): ?>
                        <option value="<?= e($k) ?>" <?= ($filters['type'] ?? '') === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="aho-form-group" style="margin:0">
                <label class="aho-form-label">Durum</label>
                <select name="status" class="aho-form-select">
                    <option value="">Tümü</option>
                    <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Aktif</option>
                    <option value="hidden" <?= ($filters['status'] ?? '') === 'hidden' ? 'selected' : '' ?>>Gizli</option>
                    <option value="disabled" <?= ($filters['status'] ?? '') === 'disabled' ? 'selected' : '' ?>>Pasif</option>
                </select>
            </div>
            <button class="aho-btn aho-btn--primary">Filtrele</button>
        </form>
    </div>

    <div class="aho-card" style="padding:0;overflow:hidden">
        <?php if (empty($products)): ?>
            <div class="aho-empty-state" style="padding:var(--aho-space-12)">
                <div class="aho-empty-state__icon" style="font-size:64px">🛒</div>
                <div class="aho-empty-state__title">Henüz ürün eklenmemiş</div>
                <div class="aho-empty-state__text" style="margin-bottom:var(--aho-space-4)">
                    İlk ürününüzü eklemek için "Yeni Ürün" butonunu kullanın.
                </div>
                <a href="/admin/urun-merkezi/yeni" class="aho-btn aho-btn--primary">+ İlk Ürünü Ekle</a>
            </div>
        <?php else: ?>
            <table class="aho-admin-table">
                <thead>
                    <tr>
                        <th>Ad</th>
                        <th>Tip</th>
                        <th>Grup</th>
                        <th>Durum</th>
                        <th>Sıra</th>
                        <th style="width:220px">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <strong><?= e($p['name']) ?></strong><br>
                                <small style="color:var(--aho-color-ink-500)"><?= e($p['slug']) ?></small>
                            </td>
                            <td><?= e($types[$p['type']] ?? $p['type']) ?></td>
                            <td><?= e($p['group_name'] ?? '-') ?></td>
                            <td>
                                <span class="aho-admin-badge aho-admin-badge--<?= $p['status'] === 'active' ? 'active' : ($p['status'] === 'hidden' ? 'pending' : 'suspended') ?>">
                                    <?= $p['status'] === 'active' ? 'Aktif' : ($p['status'] === 'hidden' ? 'Gizli' : 'Pasif') ?>
                                </span>
                            </td>
                            <td><?= (int) $p['sort_order'] ?></td>
                            <td>
                                <a href="/admin/urun-merkezi/<?= (int)$p['id'] ?>/duzenle" class="aho-btn aho-btn--outline aho-btn--sm">Düzenle</a>
                                <form method="post" action="/admin/urun-merkezi/<?= (int)$p['id'] ?>/sil" style="display:inline"
                                      onsubmit="return confirm('Ürünü silmek istediğinize emin misiniz?');">
                                    <?= csrf() ?>
                                    <button class="aho-btn aho-btn--danger aho-btn--sm">Sil</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?php $view->endSection(); ?>
