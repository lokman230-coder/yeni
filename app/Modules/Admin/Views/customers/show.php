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
$notes = $notes ?? [];
$contacts = $contacts ?? [];
$billableItems = $billableItems ?? [];
$quotes = $quotes ?? [];
$payments = $payments ?? [];
$emailLogs = $emailLogs ?? [];
$balance = (float)($customer['balance'] ?? 0);
$status = strtolower((string)($customer['status'] ?? 'pending'));
$quoteStatusLabels = ['draft' => 'Taslak', 'sent' => 'Gönderildi', 'accepted' => 'Kabul Edildi', 'declined' => 'Reddedildi', 'expired' => 'Süresi Doldu'];
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
            <a href="/admin/musteriler/<?= (int)$customer['id'] ?>/teklif-olustur" class="aho-btn aho-btn--outline">+ Teklif Oluştur</a>
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
        <?php foreach ([
            'ozet'=>'Özet','profil'=>'Profil','kullanicilar'=>'Kullanıcılar','iletisim'=>'İletişim Bilgisi',
            'hosting'=>'Ürün/Hizmetler','domain'=>'Alan Adları','faturalandirilabilir'=>'Faturalandırılabilir Ürünler',
            'fatura'=>'Faturalar','siparis'=>'Siparişler','teklif'=>'Teklifler','muhasebe'=>'Muhasebe Geçmişi',
            'bakiye'=>'Bakiye Hareketleri','destek'=>'Destek Talepleri','eposta'=>'İletilen e-postalar',
            'notlar'=>'Notlar ('.count($notes).')','aktivite'=>'Günlük Kayıtları',
        ] as $key => $label): ?>
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
                    <p><span>Müşteri Grubu</span><strong><?= e($customer['is_individual'] ?? 1 ? 'Bireysel' : 'Kurumsal') ?></strong></p>
                    <p><span>Kayıt</span><strong><?= e(substr((string)($customer['created_at'] ?? ''), 0, 16) ?: '—') ?></strong></p>
                    <p><span>Son giriş</span><strong><?= e(substr((string)($customer['last_login_at'] ?? ''), 0, 16) ?: '—') ?></strong></p>
                </div>
                <button type="button" onclick="ahoOpenCreditModal()" class="aho-btn aho-btn--primary" style="width:100%;margin-top:14px">Bakiye Ekle / Çıkar</button>
            </div>
            <div class="aho-card">
                <h3>Faturalar / Faturalandırma</h3>
                <div class="aho-detail-list">
                    <p><span>Toplam Fatura</span><strong><?= (int)($summary['invoices'] ?? count($invoices ?? [])) ?></strong></p>
                    <p><span>Ödenmemiş</span><strong class="is-danger"><?= (int)($summary['unpaid_count'] ?? 0) ?> (<?= number_format((float)($summary['unpaid_total'] ?? 0), 2) ?> TRY)</strong></p>
                    <p><span>Bekleyen Kalem</span><strong><?= count(array_filter($billableItems, fn($b) => ($b['status'] ?? '') === 'pending')) ?></strong></p>
                    <p><span>Açık Teklif</span><strong><?= count(array_filter($quotes, fn($q) => in_array($q['status'] ?? '', ['draft','sent'], true))) ?></strong></p>
                </div>
            </div>
            <div class="aho-card">
                <h3>Ürün/Hizmetler</h3>
                <div class="aho-detail-list">
                    <p><span>Aktif Hosting</span><strong><?= count(array_filter($hosting ?? [], fn($h) => ($h['status'] ?? '') === 'active')) ?> / <?= count($hosting ?? []) ?></strong></p>
                    <p><span>Alan Adı</span><strong><?= count($domains) ?></strong></p>
                    <p><span>Destek Talebi</span><strong><?= count($tickets ?? []) ?></strong></p>
                </div>
            </div>
            <div class="aho-card">
                <h3>Yönetici Notu</h3>
                <form method="post" action="/admin/musteriler/<?= (int)$customer['id'] ?>/not-ekle">
                    <?= csrf() ?>
                    <textarea name="note" rows="3" class="aho-form-textarea" placeholder="Sadece admin ekibi görür..."></textarea>
                    <button class="aho-btn aho-btn--primary aho-btn--sm" style="margin-top:8px">Gönder</button>
                </form>
            </div>
            <div class="aho-card" style="grid-column:1/-1">
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

    <?php
    // ---- Profil + İletişim Bilgisi: tek form, iki sekmeye bölünmüş ----
    $countries = ['TR' => 'TR - Turkey'];
    ?>
    <form method="post" action="/admin/musteriler/<?= (int)$customer['id'] ?>/guncelle">
        <?= csrf() ?>
        <section class="aho-customer-panel" data-customer-panel="profil">
            <div class="aho-card">
                <h3>Profil</h3>
                <div class="aho-admin-form-row aho-admin-form-row--2">
                    <div><label>Ad</label><input type="text" name="first_name" class="aho-form-input" value="<?= e($customer['first_name'] ?? '') ?>"></div>
                    <div><label>Soyad</label><input type="text" name="last_name" class="aho-form-input" value="<?= e($customer['last_name'] ?? '') ?>"></div>
                    <div><label>E-posta *</label><input type="email" name="email" required class="aho-form-input" value="<?= e($customer['email'] ?? '') ?>"></div>
                    <div><label>Telefon</label><input type="tel" name="phone" class="aho-form-input" value="<?= e($customer['phone'] ?? '') ?>"></div>
                    <div><label>Firma</label><input type="text" name="company" class="aho-form-input" value="<?= e($customer['company'] ?? '') ?>"></div>
                    <div>
                        <label>Müşteri Tipi</label>
                        <select name="is_individual" class="aho-form-select">
                            <option value="1" <?= ($customer['is_individual'] ?? 1) == 1 ? 'selected' : '' ?>>Bireysel</option>
                            <option value="0" <?= ($customer['is_individual'] ?? 1) == 0 ? 'selected' : '' ?>>Kurumsal</option>
                        </select>
                    </div>
                    <div>
                        <label>Durum</label>
                        <select name="status" class="aho-form-select">
                            <?php foreach (['active'=>'Aktif','pending'=>'Beklemede','suspended'=>'Askıda','closed'=>'Kapalı'] as $k=>$v): ?>
                                <option value="<?= $k ?>" <?= ($customer['status'] ?? 'active') === $k ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Dil</label>
                        <select name="preferred_language" class="aho-form-select">
                            <option value="tr" <?= ($customer['preferred_language'] ?? 'tr') === 'tr' ? 'selected' : '' ?>>Türkçe</option>
                            <option value="en" <?= ($customer['preferred_language'] ?? 'tr') === 'en' ? 'selected' : '' ?>>English</option>
                            <option value="de" <?= ($customer['preferred_language'] ?? 'tr') === 'de' ? 'selected' : '' ?>>Deutsch</option>
                        </select>
                    </div>
                    <div>
                        <label>Para Birimi</label>
                        <select name="preferred_currency" class="aho-form-select">
                            <?php foreach (['TRY','USD','EUR','GBP'] as $c): ?>
                                <option value="<?= $c ?>" <?= ($customer['preferred_currency'] ?? 'TRY') === $c ? 'selected' : '' ?>><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label>Yeni Şifre (boşsa değişmez)</label><input type="password" name="password" class="aho-form-input"></div>
                    <div style="display:flex;align-items:center;gap:8px;padding-top:22px">
                        <label><input type="checkbox" name="email_verified" value="1" <?= !empty($customer['email_verified_at']) ? 'checked' : '' ?>> E-posta doğrulanmış</label>
                    </div>
                </div>
                <button type="submit" class="aho-btn aho-btn--primary" style="margin-top:14px">💾 Profili Kaydet</button>
            </div>
        </section>

        <section class="aho-customer-panel" data-customer-panel="iletisim">
            <div class="aho-card">
                <h3>İletişim Bilgisi</h3>
                <div class="aho-admin-form-row aho-admin-form-row--2">
                    <div style="grid-column:1/-1"><label>Adres</label><textarea name="address" rows="2" class="aho-form-textarea"><?= e($customer['address'] ?? '') ?></textarea></div>
                    <div><label>Şehir</label><input type="text" name="city" class="aho-form-input" value="<?= e($customer['city'] ?? '') ?>"></div>
                    <div><label>Posta Kodu</label><input type="text" name="postcode" class="aho-form-input" value="<?= e($customer['postcode'] ?? '') ?>"></div>
                    <div><label>Ülke</label><input type="text" name="country" maxlength="2" class="aho-form-input" value="<?= e($customer['country'] ?? 'TR') ?>" placeholder="TR"></div>
                    <div><label>Vergi No / TCKN</label><input type="text" name="tax_id" class="aho-form-input" value="<?= e($customer['tax_id'] ?? '') ?>"></div>
                    <div><label>Vergi Dairesi</label><input type="text" name="tax_office" class="aho-form-input" value="<?= e($customer['tax_office'] ?? '') ?>"></div>
                </div>
                <button type="submit" class="aho-btn aho-btn--primary" style="margin-top:14px">💾 İletişim Bilgisini Kaydet</button>
            </div>
        </section>
    </form>

    <section class="aho-customer-panel" data-customer-panel="kullanicilar">
        <div class="aho-card">
            <h3>+ Yeni Kullanıcı Ekle</h3>
            <form method="post" action="/admin/musteriler/<?= (int)$customer['id'] ?>/kullanici-ekle" class="aho-admin-form-row aho-admin-form-row--3">
                <?= csrf() ?>
                <div><label>Ad *</label><input type="text" name="first_name" required class="aho-form-input"></div>
                <div><label>Soyad</label><input type="text" name="last_name" class="aho-form-input"></div>
                <div><label>E-posta *</label><input type="email" name="email" required class="aho-form-input"></div>
                <div><label>Telefon</label><input type="tel" name="phone" class="aho-form-input"></div>
                <div><label>Rol / Yetki Etiketi</label><input type="text" name="role_label" class="aho-form-input" placeholder="Muhasebe, Teknik..."></div>
                <div><label>Şifre (opsiyonel — panel girişi)</label><input type="password" name="password" class="aho-form-input"></div>
                <div style="grid-column:1/-1"><button class="aho-btn aho-btn--primary">+ Ekle</button></div>
            </form>
        </div>
        <div class="aho-card" style="margin-top:16px">
            <h3>Kullanıcılar (<?= count($contacts) ?>)</h3>
            <div class="aho-table-wrap"><table class="aho-table aho-customer-mini-table">
                <thead><tr><th>Ad Soyad</th><th>E-posta</th><th>Telefon</th><th>Rol</th><th>Durum</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($contacts as $c): ?>
                    <tr>
                        <td><?= e(trim($c['first_name'] . ' ' . ($c['last_name'] ?? ''))) ?></td>
                        <td><?= e($c['email']) ?></td>
                        <td><?= e($c['phone'] ?? '—') ?></td>
                        <td><?= e($c['role_label'] ?? '—') ?></td>
                        <td><span class="aho-badge"><?= $c['is_active'] ? 'Aktif' : 'Pasif' ?></span></td>
                        <td>
                            <form method="post" action="/admin/musteriler/<?= (int)$customer['id'] ?>/kullanici/<?= (int)$c['id'] ?>/sil" onsubmit="return confirm('Silinsin mi?')" style="display:inline">
                                <?= csrf() ?><button class="aho-btn aho-btn--sm aho-btn--danger">Sil</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$contacts): ?><tr><td colspan="6" class="aho-empty-cell">Kullanıcı yok.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </div>
    </section>

    <section class="aho-customer-panel" data-customer-panel="hosting">
        <div class="aho-card">
            <h3>Ürün/Hizmetler (<?= count($hosting ?? []) ?>)</h3>
            <p style="font-size:12px;color:var(--aho-color-ink-500);margin-top:-4px">💡 Panel şifresi aynı zamanda FTP giriş şifresidir (cPanel/DirectAdmin/Plesk varsayılanı).</p>
            <div class="aho-table-wrap"><table class="aho-table aho-customer-mini-table">
                <thead><tr><th>Kimlik</th><th>Ürün/Hizmet</th><th>Tutar</th><th>Fatura Dönemi</th><th>Kayıt Tarihi</th><th>Sonraki Ödeme</th><th>Kullanıcı</th><th>Şifre (FTP/Panel)</th><th>Durum</th><th>İşlemler</th></tr></thead>
                <tbody>
                <?php
                $periodLabels = ['onetime'=>'Tek Seferlik','monthly'=>'Aylık','quarterly'=>'3 Aylık','semiannually'=>'6 Aylık','annually'=>'Yıllık','biennially'=>'2 Yıllık','triennially'=>'3 Yıllık'];
                $statusStyle = ['active'=>['Aktif','#059669','#d1fae5'],'pending'=>['Kuruluyor','#d97706','#fef3c7'],'suspended'=>['Askıda','#dc2626','#fee2e2'],'terminated'=>['Kapatıldı','#6b7280','#f3f4f6']];
                foreach (($hosting ?? []) as $h):
                    $panelPort = match ($h['server_panel'] ?? null) { 'cpanel' => 2083, 'da' => 2222, 'plesk' => 8443, default => null };
                    $panelUrl = ($h['server_hostname'] && $panelPort) ? 'https://' . $h['server_hostname'] . ':' . $panelPort : null;
                    $ftpUrl = $h['server_hostname'] ? 'ftp://' . rawurlencode((string)($h['username'] ?? '')) . '@' . $h['server_hostname'] : null;
                    $st = $statusStyle[$h['status'] ?? ''] ?? [$h['status'] ?? '-', '#6b7280', '#f3f4f6'];
                    $due = !empty($h['next_due_date']) ? strtotime($h['next_due_date']) : null;
                    $daysLeft = $due ? (int) floor(($due - strtotime('today')) / 86400) : null;
                    $dueColor = $daysLeft === null ? '#6b7280' : ($daysLeft < 0 ? '#dc2626' : ($daysLeft <= 7 ? '#d97706' : '#059669'));
                ?>
                    <tr>
                        <td>#<?= (int)$h['id'] ?></td>
                        <td><a href="/admin/hosting-hesaplari/<?= (int)$h['id'] ?>/duzenle" style="text-decoration:none"><strong><?= e($h['product_name'] ?? $h['package'] ?? '-') ?></strong></a><br><small style="color:var(--aho-color-ink-500)"><?= e($h['domain'] ?? '-') ?></small></td>
                        <td><?= $h['order_amount'] !== null ? number_format((float)$h['order_amount'], 2) . ' TRY' : '—' ?></td>
                        <td><?= e($periodLabels[$h['billing_period'] ?? ''] ?? '—') ?></td>
                        <td><?= e($h['created_at'] ? date('d.m.Y', strtotime($h['created_at'])) : '-') ?></td>
                        <td>
                            <?php if ($due): ?>
                                <span style="color:<?= $dueColor ?>;font-weight:600"><?= date('d.m.Y', $due) ?></span>
                                <br><small style="color:<?= $dueColor ?>"><?= $daysLeft < 0 ? abs($daysLeft) . ' gün gecikti' : $daysLeft . ' gün kaldı' ?></small>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                        <td><code><?= e($h['username'] ?? '-') ?></code></td>
                        <td><?php if (!empty($h['password_encrypted'])): ?><code id="aho-host-pwd-<?= (int)$h['id'] ?>">********</code> <button type="button" class="aho-btn aho-btn--sm aho-btn--outline" onclick="ahoAdminRevealHostingPassword(<?= (int)$customer['id'] ?>, <?= (int)$h['id'] ?>, this)">Göster</button><?php else: ?>—<?php endif; ?></td>
                        <td><span style="padding:4px 10px;font-size:12px;border-radius:12px;color:<?= $st[1] ?>;background:<?= $st[2] ?>;white-space:nowrap"><?= e($st[0]) ?></span></td>
                        <td style="white-space:nowrap;display:flex;gap:4px;flex-wrap:wrap">
                            <a href="/admin/hosting-hesaplari/<?= (int)$h['id'] ?>/duzenle" class="aho-btn aho-btn--sm aho-btn--outline" title="Detay / Düzenle">✏️</a>
                            <?php if ($panelUrl): ?><a href="<?= e($panelUrl) ?>" target="_blank" class="aho-btn aho-btn--sm aho-btn--primary" title="Kontrol paneline git">🔗 Panel</a><?php endif; ?>
                            <?php if ($ftpUrl): ?><a href="<?= e($ftpUrl) ?>" class="aho-btn aho-btn--sm aho-btn--outline" title="FTP istemcisinde aç">📁 FTP</a><?php endif; ?>
                            <?php if (($h['status'] ?? '') === 'active'): ?>
                                <form method="post" action="/admin/musteriler/<?= (int)$customer['id'] ?>/hosting/<?= (int)$h['id'] ?>/askiya-al" onsubmit="return confirm('Hesap askıya alınsın mı?')" style="display:inline">
                                    <?= csrf() ?><button class="aho-btn aho-btn--sm aho-btn--outline" title="Askıya al">⏸</button>
                                </form>
                            <?php elseif (($h['status'] ?? '') === 'suspended'): ?>
                                <form method="post" action="/admin/musteriler/<?= (int)$customer['id'] ?>/hosting/<?= (int)$h['id'] ?>/aktif-et" onsubmit="return confirm('Hesap tekrar aktif edilsin mi?')" style="display:inline">
                                    <?= csrf() ?><button class="aho-btn aho-btn--sm aho-btn--success" title="Aktif et">▶</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="/admin/musteriler/<?= (int)$customer['id'] ?>/hosting/<?= (int)$h['id'] ?>/sifre-sifirla" onsubmit="return confirm('Yeni rastgele şifre üretilip sunucuda değiştirilecek. Devam?')" style="display:inline">
                                <?= csrf() ?><button class="aho-btn aho-btn--sm aho-btn--ghost" title="Şifreyi sıfırla">🔑</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($hosting)): ?><tr><td colspan="10" class="aho-empty-cell">Hosting hizmeti yok.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
            <?php if (array_filter($hosting ?? [], fn($h) => empty($h['product_id']) || empty($h['server_id']))): ?>
                <p style="margin-top:10px"><a href="/admin/hosting-hesaplari?q=<?= urlencode($customer['email'] ?? '') ?>" class="aho-btn aho-btn--sm aho-btn--outline">⚠ Bu müşterinin paket/sunucusu eksik hesapları düzelt</a></p>
            <?php endif; ?>
        </div>
    </section>

    <section class="aho-customer-panel" data-customer-panel="domain">
        <div class="aho-card">
            <h3>Alan Adları (<?= count($domains) ?>)
                <form method="post" action="/admin/musteriler/<?= (int)$customer['id'] ?>/alan-adlarini-senkronize-et" style="float:right" onsubmit="return confirm('Hosting hesaplarındaki domain adları Alan Adları listesine eklenecek. Devam?')">
                    <?= csrf() ?><button class="aho-btn aho-btn--sm aho-btn--outline">🔄 Hosting'ten Domainleri Aktar</button>
                </form>
            </h3>
            <div class="aho-table-wrap"><table class="aho-table aho-customer-mini-table">
                <thead><tr><th>#</th><th>Domain</th><th>Registrar</th><th>Durum</th><th>Kayıt</th><th>Bitiş</th><th>Yenileme</th></tr></thead>
                <tbody>
                <?php
                $domainStatusStyle = ['active'=>['Aktif','#059669','#d1fae5'],'pending'=>['Beklemede','#d97706','#fef3c7'],'pending_transfer'=>['Transfer Bekliyor','#d97706','#fef3c7'],'expired'=>['Süresi Doldu','#dc2626','#fee2e2'],'cancelled'=>['İptal','#6b7280','#f3f4f6'],'suspended'=>['Askıda','#dc2626','#fee2e2']];
                foreach ($domains as $d):
                    $ds = $domainStatusStyle[$d['status'] ?? ''] ?? [$d['status'] ?? '-', '#6b7280', '#f3f4f6'];
                    $exp = !empty($d['expiry_date']) ? strtotime($d['expiry_date']) : null;
                    $expDaysLeft = $exp ? (int) floor(($exp - strtotime('today')) / 86400) : null;
                    $expColor = $expDaysLeft === null ? '#111' : ($expDaysLeft < 0 ? '#dc2626' : ($expDaysLeft <= 30 ? '#d97706' : '#111'));
                ?>
                    <tr>
                        <td>#<?= (int)$d['id'] ?></td>
                        <td><strong><?= e($d['domain_name'] ?? '-') ?></strong></td>
                        <td><?= e($d['registrar_name'] ?? '-') ?></td>
                        <td><span style="padding:4px 10px;font-size:12px;border-radius:12px;color:<?= $ds[1] ?>;background:<?= $ds[2] ?>;white-space:nowrap"><?= e($ds[0]) ?></span></td>
                        <td><?= !empty($d['registration_date']) ? date('d.m.Y', strtotime($d['registration_date'])) : '-' ?></td>
                        <td style="color:<?= $expColor ?>;font-weight:<?= $expDaysLeft !== null && $expDaysLeft <= 30 ? '600' : '400' ?>">
                            <?= $exp ? date('d.m.Y', $exp) : '-' ?>
                            <?php if ($expDaysLeft !== null): ?><br><small><?= $expDaysLeft < 0 ? abs($expDaysLeft) . ' gün geçti' : $expDaysLeft . ' gün kaldı' ?></small><?php endif; ?>
                        </td>
                        <td><?= !empty($d['auto_renew']) ? '✅ Açık' : '⭕ Kapalı' ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$domains): ?><tr><td colspan="7" class="aho-empty-cell">Domain yok.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </div>
    </section>

    <section class="aho-customer-panel" data-customer-panel="faturalandirilabilir">
        <div class="aho-card">
            <h3>+ Yeni Kalem Ekle</h3>
            <form method="post" action="/admin/musteriler/<?= (int)$customer['id'] ?>/faturalandirilabilir-ekle" class="aho-admin-form-row aho-admin-form-row--3">
                <?= csrf() ?>
                <div style="grid-column:1/-1"><label>Açıklama *</label><input type="text" name="description" required class="aho-form-input" placeholder="Örn: Ek yedekleme hizmeti"></div>
                <div><label>Adet</label><input type="number" name="quantity" value="1" min="1" class="aho-form-input"></div>
                <div><label>Birim Fiyat *</label><input type="number" step="0.01" name="unit_price" required class="aho-form-input"></div>
                <div><label>KDV %</label><input type="number" step="0.01" name="tax_rate" value="0" class="aho-form-input"></div>
                <div style="grid-column:1/-1"><button class="aho-btn aho-btn--primary">+ Ekle</button></div>
            </form>
        </div>
        <div class="aho-card" style="margin-top:16px">
            <h3>Kalemler (<?= count($billableItems) ?>)</h3>
            <form method="post" action="/admin/musteriler/<?= (int)$customer['id'] ?>/faturalandirilabilir/faturaya-cevir">
                <?= csrf() ?>
                <div class="aho-table-wrap"><table class="aho-table aho-customer-mini-table">
                    <thead><tr><th></th><th>Açıklama</th><th>Adet</th><th>Birim</th><th>Tutar</th><th>Durum</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($billableItems as $b): $lineTotal = (float)$b['unit_price'] * (int)$b['quantity']; ?>
                        <tr>
                            <td><?php if ($b['status'] === 'pending'): ?><input type="checkbox" name="item_ids[]" value="<?= (int)$b['id'] ?>"><?php endif; ?></td>
                            <td><?= e($b['description']) ?></td>
                            <td><?= (int)$b['quantity'] ?></td>
                            <td><?= number_format((float)$b['unit_price'], 2) ?> <?= e($b['currency']) ?></td>
                            <td><strong><?= number_format($lineTotal, 2) ?> <?= e($b['currency']) ?></strong></td>
                            <td><span class="aho-badge"><?= e(['pending'=>'Bekliyor','invoiced'=>'Faturalandı','cancelled'=>'İptal'][$b['status']] ?? $b['status']) ?></span></td>
                            <td>
                                <?php if ($b['status'] === 'pending'): ?>
                                <form method="post" action="/admin/musteriler/<?= (int)$customer['id'] ?>/faturalandirilabilir/<?= (int)$b['id'] ?>/sil" onsubmit="return confirm('Silinsin mi?')" style="display:inline">
                                    <?= csrf() ?><button class="aho-btn aho-btn--sm aho-btn--danger">Sil</button>
                                </form>
                                <?php elseif (!empty($b['invoice_id'])): ?>
                                    <a href="/admin/faturalar/<?= (int)$b['invoice_id'] ?>" class="aho-btn aho-btn--sm aho-btn--outline">Fatura #<?= (int)$b['invoice_id'] ?></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$billableItems): ?><tr><td colspan="7" class="aho-empty-cell">Kalem yok.</td></tr><?php endif; ?>
                    </tbody>
                </table></div>
                <?php if (array_filter($billableItems, fn($b) => $b['status'] === 'pending')): ?>
                    <button class="aho-btn aho-btn--primary" style="margin-top:10px" onclick="return confirm('Seçilen kalemler tek faturada birleştirilecek. Devam?')">Seçilenleri Faturaya Çevir</button>
                <?php endif; ?>
            </form>
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

    <section class="aho-customer-panel" data-customer-panel="teklif">
        <div class="aho-card">
            <h3>Teklifler (<?= count($quotes) ?>) <a href="/admin/musteriler/<?= (int)$customer['id'] ?>/teklif-olustur" class="aho-btn aho-btn--sm aho-btn--primary" style="float:right">+ Yeni Teklif</a></h3>
            <div class="aho-table-wrap"><table class="aho-table aho-customer-mini-table">
                <thead><tr><th>No</th><th>Konu</th><th>Toplam</th><th>Durum</th><th>Tarih</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($quotes as $q): ?>
                    <tr>
                        <td><?= e($q['quote_number']) ?></td>
                        <td><?= e($q['subject']) ?></td>
                        <td><?= number_format((float)$q['total'], 2) ?> <?= e($q['currency']) ?></td>
                        <td><span class="aho-badge"><?= e($quoteStatusLabels[$q['status']] ?? $q['status']) ?></span></td>
                        <td><?= e(substr((string)$q['created_at'], 0, 16)) ?></td>
                        <td><a href="/admin/teklifler/<?= (int)$q['id'] ?>" class="aho-btn aho-btn--sm aho-btn--outline">Aç</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$quotes): ?><tr><td colspan="6" class="aho-empty-cell">Teklif yok.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </div>
    </section>

    <section class="aho-customer-panel" data-customer-panel="muhasebe">
        <div class="aho-card"><h3>Muhasebe Geçmişi (<?= count($payments) ?>)</h3><div class="aho-table-wrap"><table class="aho-table aho-customer-mini-table">
            <thead><tr><th>Tarih</th><th>Yöntem</th><th>Tutar</th><th>Durum</th><th>İşlem No</th></tr></thead>
            <tbody>
            <?php foreach ($payments as $p): ?>
                <tr>
                    <td><?= e(substr((string)$p['created_at'], 0, 16)) ?></td>
                    <td><?= e($p['method']) ?></td>
                    <td><?= number_format((float)$p['amount'], 2) ?> <?= e($p['currency']) ?></td>
                    <td><span class="aho-badge"><?= e($p['status']) ?></span></td>
                    <td><code style="font-size:11px"><?= e($p['gateway_transaction_id'] ?: '—') ?></code></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$payments): ?><tr><td colspan="5" class="aho-empty-cell">Ödeme kaydı yok.</td></tr><?php endif; ?>
            </tbody>
        </table></div></div>
    </section>

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

    <section class="aho-customer-panel" data-customer-panel="eposta">
        <div class="aho-card"><h3>İletilen e-postalar (<?= count($emailLogs) ?>)</h3><div class="aho-table-wrap"><table class="aho-table aho-customer-mini-table">
            <thead><tr><th>Tarih</th><th>Konu</th><th>Durum</th></tr></thead>
            <tbody>
            <?php foreach ($emailLogs as $m): ?>
                <tr><td><?= e(substr((string)($m['sent_at'] ?? $m['created_at'] ?? ''), 0, 16)) ?></td><td><?= e($m['subject'] ?? '—') ?></td><td><span class="aho-badge"><?= e($m['status'] ?? '-') ?></span></td></tr>
            <?php endforeach; ?>
            <?php if (!$emailLogs): ?><tr><td colspan="3" class="aho-empty-cell">Hiç e-posta gönderilmedi.</td></tr><?php endif; ?>
            </tbody>
        </table></div></div>
    </section>

    <section class="aho-customer-panel" data-customer-panel="notlar">
        <div class="aho-card">
            <h3>+ Not Ekle</h3>
            <form method="post" action="/admin/musteriler/<?= (int)$customer['id'] ?>/not-ekle">
                <?= csrf() ?>
                <textarea name="note" rows="3" class="aho-form-textarea" required placeholder="Sadece admin ekibi görür..."></textarea>
                <label style="display:inline-flex;align-items:center;gap:6px;margin-top:8px"><input type="checkbox" name="is_sticky" value="1"> Sabitle (üstte göster)</label>
                <div><button class="aho-btn aho-btn--primary" style="margin-top:8px">Kaydet</button></div>
            </form>
        </div>
        <div class="aho-card" style="margin-top:16px">
            <h3>Notlar (<?= count($notes) ?>)</h3>
            <div class="aho-timeline aho-timeline--wide">
                <?php foreach ($notes as $n): ?>
                    <div>
                        <strong><?= $n['is_sticky'] ? '📌 ' : '' ?><?= e($n['admin_email'] ?: 'Admin') ?></strong>
                        <span><?= nl2br(e($n['note'])) ?></span>
                        <small><?= e(substr((string)$n['created_at'], 0, 16)) ?>
                            <form method="post" action="/admin/musteriler/<?= (int)$customer['id'] ?>/not/<?= (int)$n['id'] ?>/sil" onsubmit="return confirm('Silinsin mi?')" style="display:inline">
                                <?= csrf() ?><button class="aho-btn aho-btn--sm aho-btn--ghost" style="padding:0 6px">Sil</button>
                            </form>
                        </small>
                    </div>
                <?php endforeach; ?>
                <?php if (!$notes): ?><p class="aho-empty-inline">Not yok.</p><?php endif; ?>
            </div>
        </div>
    </section>

    <section class="aho-customer-panel" data-customer-panel="aktivite">
        <div class="aho-card"><h3>Günlük Kayıtları</h3><div class="aho-timeline aho-timeline--wide">
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
(function(){
    var hash = (location.hash || '').replace('#', '');
    var key = hash || localStorage.getItem('ahoCustomerDetailTab');
    var btn = key && document.querySelector('[data-customer-tab="'+key+'"]');
    if (btn) btn.click();
})();
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
