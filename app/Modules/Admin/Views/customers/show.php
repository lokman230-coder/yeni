<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
$success = flash('success');
$error = flash('error');
$fullName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
$displayName = $fullName ?: ($customer['email'] ?? 'Musteri');
$summary = $summary ?? [];
$domains = $domains ?? [];
$credits = $credits ?? [];
$activity = $activity ?? [];
$balance = (float)($customer['balance'] ?? 0);
$status = strtolower((string)($customer['status'] ?? 'pending'));
?>
<div class="aho-admin-page aho-customer-profile">
    <div class="aho-customer-hero">
        <div class="aho-customer-hero__main">
            <div class="aho-customer-hero__avatar"><?= e(mb_strtoupper(mb_substr($displayName, 0, 1))) ?></div>
            <div>
                <h1><?= e($displayName) ?></h1>
                <p><?= e($customer['email'] ?? '') ?> · #<?= (int)$customer['id'] ?></p>
                <div class="aho-customer-hero__badges">
                    <span class="aho-customer-badge is-<?= e($status) ?>"><?= e($customer['status'] ?? 'pending') ?></span>
                    <?php if (!empty($customer['email_verified_at'])): ?><span class="aho-customer-verified">E-posta doğrulandı</span><?php endif; ?>
                </div>
            </div>
        </div>
        <div class="aho-customer-hero__actions">
            <a href="/admin/musteriler" class="aho-btn aho-btn--ghost">← Liste</a>
            <a href="/admin/musteriler/<?= (int)$customer['id'] ?>/duzenle" class="aho-btn aho-btn--outline">Düzenle</a>
            <form method="post" action="/admin/musteriler/<?= (int)$customer['id'] ?>/adina-giris" onsubmit="return confirm('Bu müşterinin paneline geçmek istiyor musun? Aktivite loglanacak.')">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <button class="aho-btn aho-btn--warning">Adına Giriş Yap</button>
            </form>
        </div>
    </div>

    <?php if ($success): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

    <div class="aho-customer-kpis">
        <div><span>Bakiye</span><strong class="<?= $balance < 0 ? 'is-danger' : 'is-success' ?>"><?= number_format($balance, 2) ?> TRY</strong></div>
        <div><span>Ödenmemiş</span><strong><?= number_format((float)($summary['unpaid_total'] ?? 0), 2) ?> TRY</strong><small><?= (int)($summary['unpaid_count'] ?? 0) ?> fatura</small></div>
        <div><span>Hosting</span><strong><?= (int)($summary['hosting'] ?? count($hosting ?? [])) ?></strong></div>
        <div><span>Domain</span><strong><?= (int)($summary['domains'] ?? count($domains)) ?></strong></div>
        <div><span>Destek</span><strong><?= (int)($summary['tickets'] ?? count($tickets ?? [])) ?></strong></div>
    </div>

    <div class="aho-customer-tabs" role="tablist">
        <?php foreach (['ozet'=>'Özet','profil'=>'Profil','hosting'=>'Hosting','domain'=>'Domainler','fatura'=>'Faturalar','siparis'=>'Siparişler','destek'=>'Destek','bakiye'=>'Bakiye','aktivite'=>'Aktivite'] as $key => $label): ?>
            <button type="button" data-customer-tab="<?= e($key) ?>" class="<?= $key === 'ozet' ? 'is-active' : '' ?>"><?= e($label) ?></button>
        <?php endforeach; ?>
    </div>

    <section class="aho-customer-panel is-active" data-customer-panel="ozet">
        <div class="aho-customer-grid">
            <div class="aho-card">
                <h3>Hesap Özeti</h3>
                <div class="aho-detail-list">
                    <p><span>Telefon</span><strong><?= e($customer['phone'] ?? '—') ?></strong></p>
                    <p><span>Firma</span><strong><?= e($customer['company'] ?? '—') ?></strong></p>
                    <p><span>Kayıt</span><strong><?= e(substr((string)($customer['created_at'] ?? ''), 0, 16) ?: '—') ?></strong></p>
                    <p><span>Son giriş</span><strong><?= e(substr((string)($customer['last_login_at'] ?? ''), 0, 16) ?: '—') ?></strong></p>
                </div>
                <button type="button" onclick="ahoOpenCreditModal()" class="aho-btn aho-btn--primary" style="width:100%;margin-top:14px">Bakiye Ekle / Çıkar</button>
            </div>
            <div class="aho-card">
                <h3>Son Hareketler</h3>
                <div class="aho-timeline">
                    <?php foreach (array_slice($activity, 0, 8) as $log): ?>
                        <div><strong><?= e($log['action'] ?? 'log') ?></strong><span><?= e($log['summary'] ?? '') ?></span><small><?= e(substr((string)($log['created_at'] ?? ''), 0, 16)) ?></small></div>
                    <?php endforeach; ?>
                    <?php if (!$activity): ?><p class="aho-empty-inline">Aktivite kaydı yok.</p><?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="aho-customer-panel" data-customer-panel="profil">
        <div class="aho-card">
            <h3>Profil ve Notlar</h3>
            <div class="aho-detail-list aho-detail-list--grid">
                <p><span>Ad Soyad</span><strong><?= e($displayName) ?></strong></p>
                <p><span>E-posta</span><strong><?= e($customer['email'] ?? '—') ?></strong></p>
                <p><span>Telefon</span><strong><?= e($customer['phone'] ?? '—') ?></strong></p>
                <p><span>Firma</span><strong><?= e($customer['company'] ?? '—') ?></strong></p>
                <p><span>Durum</span><strong><?= e($customer['status'] ?? 'pending') ?></strong></p>
                <p><span>E-posta doğrulama</span><strong><?= !empty($customer['email_verified_at']) ? 'Var' : 'Yok' ?></strong></p>
            </div>
            <?php if (!empty($customer['admin_notes'])): ?>
                <div class="aho-admin-note"><?= nl2br(e($customer['admin_notes'])) ?></div>
            <?php endif; ?>
        </div>
    </section>

    <section class="aho-customer-panel" data-customer-panel="hosting">
        <div class="aho-card">
            <h3>Hosting Hizmetleri (<?= count($hosting ?? []) ?>)</h3>
            <div class="aho-table-wrap"><table class="aho-table aho-customer-mini-table">
                <thead><tr><th>#</th><th>Domain</th><th>Paket</th><th>Sunucu</th><th>Kullanıcı</th><th>Şifre</th><th>Durum</th><th>Vade</th></tr></thead>
                <tbody>
                <?php foreach (($hosting ?? []) as $h): ?>
                    <tr>
                        <td>#<?= (int)$h['id'] ?></td><td><?= e($h['domain'] ?? '-') ?></td><td><?= e($h['product_name'] ?? $h['package'] ?? '-') ?></td><td><?= e($h['server_name'] ?? $h['server_hostname'] ?? '-') ?></td>
                        <td><code><?= e($h['username'] ?? '-') ?></code></td>
                        <td><?php if (!empty($h['password_encrypted'])): ?><code id="aho-host-pwd-<?= (int)$h['id'] ?>">********</code> <button type="button" class="aho-btn aho-btn--sm aho-btn--outline" onclick="ahoAdminRevealHostingPassword(<?= (int)$customer['id'] ?>, <?= (int)$h['id'] ?>, this)">Göster</button><?php else: ?>—<?php endif; ?></td>
                        <td><span class="aho-badge"><?= e($h['status'] ?? '-') ?></span></td><td><?= e(substr((string)($h['next_due_date'] ?? ''), 0, 10) ?: '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($hosting)): ?><tr><td colspan="8" class="aho-empty-cell">Hosting hizmeti yok.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </div>
    </section>

    <section class="aho-customer-panel" data-customer-panel="domain">
        <div class="aho-card">
            <h3>Domainler (<?= count($domains) ?>)</h3>
            <div class="aho-table-wrap"><table class="aho-table aho-customer-mini-table">
                <thead><tr><th>#</th><th>Domain</th><th>Registrar</th><th>Durum</th><th>Kayıt</th><th>Bitiş</th><th>Yenileme</th></tr></thead>
                <tbody>
                <?php foreach ($domains as $d): ?>
                    <tr><td>#<?= (int)$d['id'] ?></td><td><?= e($d['domain_name'] ?? '-') ?></td><td><?= e($d['registrar_name'] ?? '-') ?></td><td><span class="aho-badge"><?= e($d['status'] ?? '-') ?></span></td><td><?= e($d['registration_date'] ?? '-') ?></td><td><?= e($d['expiry_date'] ?? '-') ?></td><td><?= !empty($d['auto_renew']) ? 'Açık' : 'Kapalı' ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$domains): ?><tr><td colspan="7" class="aho-empty-cell">Domain yok.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </div>
    </section>

    <?php
    $tables = [
        'fatura' => ['Faturalar', $invoices ?? [], ['#','Toplam','Durum','Vade'], fn($r) => ['#'.(int)$r['id'], number_format((float)$r['total'], 2).' '.($r['currency'] ?? 'TRY'), $r['status'] ?? '-', substr((string)($r['due_date'] ?? ''), 0, 10)]],
        'siparis' => ['Siparişler', $orders ?? [], ['#','Toplam','Durum','Tarih'], fn($r) => ['#'.(int)$r['id'], number_format((float)$r['total'], 2).' '.($r['currency'] ?? 'TRY'), $r['status'] ?? '-', substr((string)($r['created_at'] ?? ''), 0, 16)]],
        'destek' => ['Destek Talepleri', $tickets ?? [], ['#','Konu','Durum','Tarih'], fn($r) => ['#'.(int)$r['id'], $r['subject'] ?? '-', $r['status'] ?? '-', substr((string)($r['created_at'] ?? ''), 0, 16)]],
    ];
    foreach ($tables as $panel => [$title, $rows, $heads, $map]): ?>
        <section class="aho-customer-panel" data-customer-panel="<?= e($panel) ?>">
            <div class="aho-card"><h3><?= e($title) ?> (<?= count($rows) ?>)</h3><div class="aho-table-wrap"><table class="aho-table aho-customer-mini-table">
                <thead><tr><?php foreach ($heads as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?><tr><?php foreach ($map($row) as $cell): ?><td><?= e((string)$cell) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
                <?php if (!$rows): ?><tr><td colspan="<?= count($heads) ?>" class="aho-empty-cell">Kayıt yok.</td></tr><?php endif; ?>
                </tbody>
            </table></div></div>
        </section>
    <?php endforeach; ?>

    <section class="aho-customer-panel" data-customer-panel="bakiye">
        <div class="aho-card"><h3>Bakiye Hareketleri (<?= count($credits) ?>)</h3><div class="aho-table-wrap"><table class="aho-table aho-customer-mini-table">
            <thead><tr><th>Tarih</th><th>Tutar</th><th>Bakiye</th><th>Kaynak</th><th>Açıklama</th></tr></thead>
            <tbody>
            <?php foreach ($credits as $cr): $isCredit = (float)$cr['amount'] >= 0; ?>
                <tr><td><?= e(substr((string)$cr['created_at'], 0, 16)) ?></td><td class="<?= $isCredit ? 'is-success' : 'is-danger' ?>"><?= ($isCredit ? '+' : '') . number_format((float)$cr['amount'], 2) ?> <?= e($cr['currency'] ?? 'TRY') ?></td><td><?= number_format((float)$cr['balance_after'], 2) ?></td><td><?= e($cr['source'] ?? '-') ?></td><td><?= e($cr['description'] ?? '—') ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$credits): ?><tr><td colspan="5" class="aho-empty-cell">Bakiye hareketi yok.</td></tr><?php endif; ?>
            </tbody>
        </table></div></div>
    </section>

    <section class="aho-customer-panel" data-customer-panel="aktivite">
        <div class="aho-card"><h3>Aktivite</h3><div class="aho-timeline aho-timeline--wide">
            <?php foreach ($activity as $log): ?><div><strong><?= e($log['action'] ?? 'log') ?></strong><span><?= e($log['summary'] ?? '') ?></span><small><?= e(($log['admin_email'] ?? '-') . ' · ' . substr((string)($log['created_at'] ?? ''), 0, 16)) ?></small></div><?php endforeach; ?>
            <?php if (!$activity): ?><p class="aho-empty-inline">Aktivite kaydı yok.</p><?php endif; ?>
        </div></div>
    </section>

    <div id="ahoCreditModal" class="aho-credit-modal" hidden>
        <div class="aho-credit-modal__box">
            <h3>Bakiye Hareketi</h3>
            <form method="post" action="/admin/musteriler/<?= (int)$customer['id'] ?>/bakiye-ekle">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <div class="aho-admin-form-row aho-admin-form-row--2"><label><input type="radio" name="direction" value="add" checked> Yükle (+)</label><label><input type="radio" name="direction" value="deduct"> Düş (-)</label></div>
                <label>Tutar (TRY) *</label><input type="number" step="0.01" name="amount" required class="aho-form-input" placeholder="100.00">
                <label>Kaynak</label><select name="source" class="aho-form-select"><option value="admin_manual">Admin manuel</option><option value="payment">Ödeme</option><option value="promo">Kampanya/hediye</option><option value="refund">İade</option></select>
                <label>Açıklama</label><textarea name="description" rows="2" class="aho-form-textarea"></textarea>
                <div class="aho-admin-form-actions"><button type="button" onclick="ahoCloseCreditModal()" class="aho-btn aho-btn--ghost">İptal</button><button class="aho-btn aho-btn--primary">Kaydet</button></div>
            </form>
        </div>
    </div>
</div>
<script>
function ahoOpenCreditModal(){ document.getElementById('ahoCreditModal').hidden = false; }
function ahoCloseCreditModal(){ document.getElementById('ahoCreditModal').hidden = true; }
document.querySelectorAll('[data-customer-tab]').forEach(function(btn){
    btn.addEventListener('click', function(){
        var key = btn.dataset.customerTab;
        localStorage.setItem('ahoCustomerDetailTab', key);
        document.querySelectorAll('[data-customer-tab]').forEach(function(b){ b.classList.toggle('is-active', b === btn); });
        document.querySelectorAll('[data-customer-panel]').forEach(function(p){ p.classList.toggle('is-active', p.dataset.customerPanel === key); });
    });
});
(function(){ var key = localStorage.getItem('ahoCustomerDetailTab'); var btn = key && document.querySelector('[data-customer-tab="'+key+'"]'); if (btn) btn.click(); })();
async function ahoAdminRevealHostingPassword(customerId, hostingId, btn) {
    btn.disabled = true;
    try {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const res = await fetch('/admin/musteriler/' + customerId + '/hosting/' + hostingId + '/sifre-goster', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: '_csrf=' + encodeURIComponent(csrf)
        });
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || 'Şifre alınamadı');
        document.getElementById('aho-host-pwd-' + hostingId).textContent = data.password;
        btn.textContent = 'Gizle';
        btn.onclick = function () {
            document.getElementById('aho-host-pwd-' + hostingId).textContent = '********';
            btn.textContent = 'Göster';
            btn.onclick = function () { ahoAdminRevealHostingPassword(customerId, hostingId, btn); };
        };
    } catch (e) {
        alert(e.message);
    } finally {
        btn.disabled = false;
    }
}
</script>
<?php $view->endSection(); ?>
