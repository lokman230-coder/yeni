<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
$configs = $configs ?? [];
?>
<div class="aho-admin-page aho-registrars-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>Registrarlar</h1>
            <p>DomainNameAPI, manuel registrar ve domain sağlayıcı ayarlarını yönetin.</p>
        </div>
    </div>

    <?php if (!empty($success)): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

    <div class="aho-registrar-grid">
        <section class="aho-card">
            <h3>Yeni Registrar</h3>
            <form method="post" action="/admin/registrarlar/kaydet" class="aho-registrar-form">
                <?= csrf() ?>
                <label>Ad</label>
                <input type="text" name="name" class="aho-form-input" placeholder="DomainNameAPI" required>
                <label>Slug</label>
                <input type="text" name="slug" class="aho-form-input" placeholder="domainnameapi">
                <label>Sürücü</label>
                <select name="driver" class="aho-form-select">
                    <option value="domainnameapi">DomainNameAPI</option>
                    <option value="manual">Manuel Registrar</option>
                </select>
                <div class="aho-admin-form-row aho-admin-form-row--2">
                    <label><input type="checkbox" name="is_active" value="1" checked> Aktif</label>
                    <label><input type="checkbox" name="is_default" value="1"> Varsayılan</label>
                    <label><input type="checkbox" name="test_mode" value="1" checked> Test modu</label>
                </div>
                <label>Sıralama</label>
                <input type="number" name="sort_order" class="aho-form-input" value="0">
                <h4>Config</h4>
                <div class="aho-registrar-config-row">
                    <input type="text" name="config_key[]" placeholder="reseller_id">
                    <input type="text" name="config_value[]" placeholder="Değer">
                    <label><input type="checkbox" name="config_encrypted[0]" value="1"> Gizli</label>
                </div>
                <div class="aho-registrar-config-row">
                    <input type="text" name="config_key[]" placeholder="api_key">
                    <input type="text" name="config_value[]" placeholder="API key">
                    <label><input type="checkbox" name="config_encrypted[1]" value="1" checked> Gizli</label>
                </div>
                <button class="aho-btn aho-btn--primary">Registrar Ekle</button>
            </form>
        </section>

        <section class="aho-card">
            <h3>Aktif Durum</h3>
            <div class="aho-registrar-summary">
                <div><span>Toplam</span><strong><?= count($registrars) ?></strong></div>
                <div><span>Aktif</span><strong class="is-success"><?= count(array_filter($registrars, fn($r) => !empty($r['is_active']))) ?></strong></div>
                <div><span>Varsayılan</span><strong><?= e(($registrars[0]['is_default'] ?? 0) ? ($registrars[0]['name'] ?? '-') : '-') ?></strong></div>
            </div>
            <p style="color:#64748b;font-weight:650">Ürün/domain işlemleri varsayılan aktif registrar üzerinden çalışır. Manuel registrar seçilirse işlemler admin onayına düşer.</p>
        </section>
    </div>

    <section class="aho-card">
        <div class="aho-modules-toolbar">
            <div><h3>Kayıtlı Registrarlar</h3><p>Bağlı domain sayısı ve config değerleriyle beraber.</p></div>
        </div>
        <div class="aho-table-wrap">
            <table class="aho-table aho-registrars-table">
                <thead><tr><th>Registrar</th><th>Sürücü</th><th>Domain</th><th>Durum</th><th>Config</th><th>İşlem</th></tr></thead>
                <tbody>
                <?php foreach ($registrars as $r): ?>
                    <tr>
                        <td><strong><?= e($r['name']) ?></strong><small><?= e($r['slug']) ?></small></td>
                        <td><code><?= e(str_replace('App\\Modules\\Registrar\\Drivers\\', '', (string)$r['driver_class'])) ?></code></td>
                        <td><?= (int)($r['domain_count'] ?? 0) ?></td>
                        <td>
                            <span class="aho-customer-badge <?= !empty($r['is_active']) ? 'is-active' : 'is-closed' ?>"><?= !empty($r['is_active']) ? 'Aktif' : 'Pasif' ?></span>
                            <?php if (!empty($r['is_default'])): ?><span class="aho-customer-verified">Varsayılan</span><?php endif; ?>
                            <?php if (!empty($r['test_mode'])): ?><span class="aho-badge">Test</span><?php endif; ?>
                        </td>
                        <td>
                            <?php foreach (($configs[(int)$r['id']] ?? []) as $cfg): ?>
                                <span class="aho-registrar-chip"><?= e($cfg['key']) ?><?= $cfg['encrypted'] ? ' 🔒' : '' ?></span>
                            <?php endforeach; ?>
                            <?php if (empty($configs[(int)$r['id']] ?? [])): ?><span style="color:#64748b">—</span><?php endif; ?>
                        </td>
                        <td>
                            <details class="aho-registrar-edit">
                                <summary class="aho-btn aho-btn--sm aho-btn--outline">Düzenle</summary>
                                <form method="post" action="/admin/registrarlar/<?= (int)$r['id'] ?>/guncelle">
                                    <?= csrf() ?>
                                    <input type="text" name="name" value="<?= e($r['name']) ?>" required>
                                    <input type="text" name="slug" value="<?= e($r['slug']) ?>" required>
                                    <select name="driver">
                                        <option value="domainnameapi" <?= str_contains((string)$r['driver_class'], 'DomainNameApi') ? 'selected' : '' ?>>DomainNameAPI</option>
                                        <option value="manual" <?= str_contains((string)$r['driver_class'], 'Manual') ? 'selected' : '' ?>>Manuel</option>
                                    </select>
                                    <label><input type="checkbox" name="is_active" value="1" <?= !empty($r['is_active']) ? 'checked' : '' ?>> Aktif</label>
                                    <label><input type="checkbox" name="is_default" value="1" <?= !empty($r['is_default']) ? 'checked' : '' ?>> Varsayılan</label>
                                    <label><input type="checkbox" name="test_mode" value="1" <?= !empty($r['test_mode']) ? 'checked' : '' ?>> Test modu</label>
                                    <input type="number" name="sort_order" value="<?= (int)$r['sort_order'] ?>">
                                    <?php $rows = $configs[(int)$r['id']] ?? [['key'=>'','value'=>'','encrypted'=>false], ['key'=>'','value'=>'','encrypted'=>false]]; ?>
                                    <?php foreach ($rows as $i => $cfg): ?>
                                        <div class="aho-registrar-config-row">
                                            <input type="text" name="config_key[]" value="<?= e($cfg['key'] ?? '') ?>" placeholder="config_key">
                                            <input type="text" name="config_value[]" value="<?= e($cfg['value'] ?? '') ?>" placeholder="Değer">
                                            <label><input type="checkbox" name="config_encrypted[<?= $i ?>]" value="1" <?= !empty($cfg['encrypted']) ? 'checked' : '' ?>> Gizli</label>
                                        </div>
                                    <?php endforeach; ?>
                                    <button class="aho-btn aho-btn--primary aho-btn--sm">Kaydet</button>
                                </form>
                                <form method="post" action="/admin/registrarlar/<?= (int)$r['id'] ?>/sil" onsubmit="return confirm('Registrar silinsin mi?')">
                                    <?= csrf() ?>
                                    <button class="aho-btn aho-btn--danger aho-btn--sm">Sil</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$registrars): ?><tr><td colspan="6" class="aho-empty-cell">Henüz registrar yok.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php $view->endSection(); ?>
