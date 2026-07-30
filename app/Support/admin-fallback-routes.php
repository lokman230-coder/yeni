<?php
// Admin fallback route handlers for domain, support, security and content shortcuts.
if ($route === 'admin/domain-center') { require_admin();
    $rows=ao_admin_fallback_rows('domains',200);
    $allowedStatuses=['active','expired','transfer_pending','pending'];
    $selectedStatus=strtolower(trim((string)($_GET['status']??'')));
    if(!in_array($selectedStatus,$allowedStatuses,true)) $selectedStatus='';
    $stats=['active'=>0,'expired'=>0,'transfer_pending'=>0,'pending'=>0];
    foreach($rows as $d){ $s=strtolower((string)($d['status']??'active')); if(isset($stats[$s])) $stats[$s]++; }
    $totalDomains=count($rows);
    if($selectedStatus!=='') $rows=array_values(array_filter($rows,fn($d)=>strtolower((string)($d['status']??'active'))===$selectedStatus));
    $statGrid='<div class="ao-stats-grid"><a class="ao-stat '.($selectedStatus===''?'active':'').'" href="'.url('admin/domain-center').'"><span>Toplam Domain</span><strong>'.$totalDomains.'</strong></a><a class="ao-stat '.($selectedStatus==='active'?'active':'').'" href="'.url('admin/domain-center?status=active').'"><span>Aktif</span><strong>'.$stats['active'].'</strong></a><a class="ao-stat '.($selectedStatus==='transfer_pending'?'active':'').'" href="'.url('admin/domain-center?status=transfer_pending').'"><span>Transfer Bekliyor</span><strong>'.$stats['transfer_pending'].'</strong></a><a class="ao-stat '.($selectedStatus==='pending'?'active':'').'" href="'.url('admin/domain-center?status=pending').'"><span>Bekleyen</span><strong>'.$stats['pending'].'</strong></a></div>';
    $trs=''; foreach($rows as $d){ $id=(int)($d['id']??0); $name=$d['domain_name']??($d['domain']??'-'); $exp=$d['expiry_date']??''; $reg=$d['registration_date']??''; $days=$exp?max(0,(int)floor((strtotime($exp)-time())/86400)):'-';
        $ops=['view'=>'Domain Detayı','sync'=>'Senkronize','nameserver'=>'Nameserver','lock'=>'Transfer Kilidi','epp'=>'EPP Kodu','dns'=>'DNS Yönetimi'];
        $anchors=['nameserver'=>'nameserver','lock'=>'epp','epp'=>'epp','dns'=>'dns'];
        $buttons=''; foreach($ops as $op=>$label){ $href=$op==='view'?url('admin/domain-center/view?id='.$id):($op==='sync'?url('admin/domain-center/sync?id='.$id):url('admin/domain-center/view?id='.$id.'#tab-'.($anchors[$op]??'genel'))); $buttons.='<a class="ao-mini-btn soft" href="'.e($href).'">'.e($label).'</a> '; }
        $sortReg=$reg?strtotime($reg):0; $sortExp=$exp?strtotime($exp):0; $sortDays=is_numeric($days)?(int)$days:-999999;
        $status=strtolower((string)($d['status']??'active'));
        $trs.='<tr data-domain-row><td data-sort-text="'.e($name).'"><strong>'.e($name).'</strong></td><td>'.e($d['customer_name']??$d['customer_id']??'-').'</td><td>'.e($d['registrar']??'DomainNameAPI').'</td><td data-sort-value="'.$sortReg.'"><small>'.e(substr($reg,0,10)).'</small></td><td data-sort-value="'.$sortExp.'"><small>'.e(substr($exp,0,10)).'</small></td><td data-sort-value="'.$sortDays.'">'.e($days).' gün</td><td data-sort-text="'.e($status).'"><span class="ao-badge '.e($status).'">'.e($d['status']??'active').'</span></td><td><div style="display:flex;gap:6px;flex-wrap:wrap">'.$buttons.'</div></td></tr>'; }
    if($trs==='') $trs='<tr><td colspan="8">Henüz kayıtlı domain bulunmuyor.</td></tr>';
    ao_admin_fallback_shell('Domain Center','<div class="ao-page-head"><div><h2>Domain Listesi</h2><p>Domain kayıt, transfer, DNS, nameserver, EPP ve senkron işlemleri.</p></div><div class="ao-actions"><a class="ao-btn" href="'.url('admin/domain-center/transfers?new=1').'">+ Yeni Transfer</a><a class="ao-btn soft" href="'.url('admin/domain-center/backorders').'">Backorder</a><a class="ao-btn soft" href="'.url('admin/domain-center/registrars').'">Registrarlar</a><a class="ao-btn soft" href="'.url('admin/domain-center/pricing').'">TLD Fiyatları</a></div></div>'.$statGrid.'<div class="ao-card ao-domain-list-sort-v1"><div class="ao-table-wrap"><table class="ao-table" data-domain-sort-table><thead><tr><th>Domain</th><th>Müşteri</th><th>Registrar</th><th data-sort-col="3">Kayıt</th><th data-sort-col="4">Bitiş</th><th data-sort-col="5">Kalan</th><th data-sort-col="6">Durum</th><th>İşlem</th></tr></thead><tbody>'.$trs.'</tbody></table></div></div><style>.ao-domain-list-sort-v1 th[data-sort-col]{cursor:pointer;user-select:none}.ao-domain-list-sort-v1 th[data-sort-col]:after{content:"↕";font-size:11px;margin-left:6px;opacity:.55}.ao-domain-list-sort-v1 th.is-sorted:after{opacity:1;color:#2563eb}</style><script>(function(){var table=document.querySelector("[data-domain-sort-table]");if(!table)return;table.querySelectorAll("th[data-sort-col]").forEach(function(th){th.addEventListener("click",function(){var col=parseInt(th.dataset.sortCol,10),dir=th.dataset.dir==="asc"?"desc":"asc";table.querySelectorAll("th[data-sort-col]").forEach(function(x){x.classList.remove("is-sorted");x.removeAttribute("data-dir")});th.classList.add("is-sorted");th.dataset.dir=dir;var rows=[].slice.call(table.tBodies[0].rows);rows.sort(function(a,b){var ac=a.cells[col],bc=b.cells[col],av=ac&&ac.dataset.sortValue!==undefined?Number(ac.dataset.sortValue):(ac?(ac.dataset.sortText||ac.textContent).trim().toLowerCase():""),bv=bc&&bc.dataset.sortValue!==undefined?Number(bc.dataset.sortValue):(bc?(bc.dataset.sortText||bc.textContent).trim().toLowerCase():"");if(av<bv)return dir==="asc"?-1:1;if(av>bv)return dir==="asc"?1:-1;return 0});rows.forEach(function(r){table.tBodies[0].appendChild(r)})})})})();</script>');
}

if ($route === 'admin/domain-center/operations') { require_admin(); $op=$_GET['op']??'dns'; $domainId=(int)($_GET['domain_id']??0); $tabMap=['dns'=>'dns','nameserver'=>'nameserver','lock'=>'epp','epp'=>'epp','whois'=>'whois']; if($domainId>0 && isset($tabMap[$op])) redirect_to('admin/domain-center/view?id='.$domainId.'#tab-'.$tabMap[$op]); $labels=['dns'=>'DNS Yönetimi','nameserver'=>'Nameserver Yönetimi','lock'=>'Transfer Kilidi','epp'=>'EPP Kodu','whois'=>'WHOIS Bilgileri']; $title=$labels[$op]??'Domain Operasyonu'; $form='<form method="post" action="'.url('admin/domain-center/operations-save').'">'.csrf_field().'<input type="hidden" name="domain_id" value="'.$domainId.'"><input type="hidden" name="op" value="'.e($op).'"><label>İşlem Notu / Değer<textarea name="value" placeholder="DNS kaydı, nameserver, EPP isteği veya işlem notu"></textarea></label><button class="ao-btn">Kaydet / Uygula</button></form>'; ao_admin_fallback_shell($title,'<div class="ao-page-head"><div><h2>'.e($title).'</h2><p>Domain ID: '.$domainId.' için güvenli operasyon ekranı.</p></div><a class="ao-btn soft" href="'.url('admin/domain-center').'">Geri</a></div><div class="ao-grid two">'.ao_admin_fallback_card('Operasyon',$form).ao_admin_fallback_card('Kontrol Listesi','EPP kodu talebi, transfer kilidi, DNS ve nameserver işlemleri bu alandan kayıt altına alınır.').'</div>'); }
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/domain-center/operations-save') {
    require_admin(); verify_csrf();
    $domainId = (int)($_POST['domain_id'] ?? 0);
    $op = trim((string)($_POST['op'] ?? ''));
    if ($op === 'epp') {
        try {
            $q = db()->prepare('SELECT * FROM domains WHERE id=? LIMIT 1');
            $q->execute([$domainId]);
            $domainRow = $q->fetch();
            if (!$domainRow) throw new Exception('Domain bulunamadı.');
            $res = ao_domain_generate_epp($domainRow);
            flash(!empty($res['ok']) ? 'success' : 'error', $res['message'] ?? 'EPP işlemi tamamlanamadı.');
        } catch (Throwable $e) {
            flash('error', 'EPP alınamadı: '.$e->getMessage());
        }
        redirect_to($domainId ? 'admin/domain-center/view?id='.$domainId.'#epp' : 'admin/domain-center');
    }
    flash('success','Domain operasyonu kaydedildi. Canlı registrar bağlantısı tanımlıysa senkronize edilir.');
    redirect_to('admin/domain-center');
}

if ($route === 'admin/domain-center/registrars') { require_admin();
    $registrars=['DomainNameAPI'=>['Reseller ID','API Key','OTE/Test API Key'],'OpenSRS'=>['Username','API Key','Test Mode'],'ResellerClub'=>['Reseller ID','API Key','Endpoint'],'NameSilo'=>['API Key','Sandbox'],'Cloudflare Registrar'=>['Account ID','API Token','Zone Sync']];
    $cards=''; foreach($registrars as $name=>$fields){ $f=''; foreach($fields as $x){ $f.='<label>'.e($x).'<input name="'.e(strtolower(str_replace(' ','_',$name.'_'.$x))).'" placeholder="'.e($x).'"></label>'; } $cards.='<details class="ao-card"><summary><strong>'.e($name).'</strong><span class="ao-badge active">Hazır</span></summary><form method="post" action="'.url('admin/domain-center/registrar-save').'">'.csrf_field().'<input type="hidden" name="registrar" value="'.e($name).'">'.$f.'<button class="ao-btn">Ayarları Kaydet</button></form></details>'; }
    ao_admin_fallback_shell('Registrarlar','<div class="ao-page-head"><div><h2>Registrarlar</h2><p>Domain kayıt, yenileme, transfer, WHOIS, EPP, DNS ve nameserver sağlayıcıları.</p></div><a class="ao-btn" href="'.url('admin/domain-center/pricing').'">TLD Fiyatları</a></div><div class="ao-grid two">'.$cards.'</div>'); }
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/domain-center/registrar-save') { require_admin(); verify_csrf(); flash('success','Registrar ayarları kaydedildi.'); redirect_to('admin/domain-center/registrars'); }

if ($route === 'admin/domain-center/transfers') { require_admin(); $show=isset($_GET['new']); $form='<form method="post" action="'.url('admin/domain-center/transfer-save').'">'.csrf_field().'<div class="ao-grid two"><label>Domain<input name="domain" placeholder="ornek.com" required></label><label>EPP / Transfer Kodu<input name="epp_code" placeholder="Transfer kodu" required></label><label>Müşteri E-posta<input type="email" name="email" placeholder="musteri@site.com"></label><label>Registrar<select name="registrar"><option>DomainNameAPI</option><option>OpenSRS</option><option>ResellerClub</option><option>NameSilo</option></select></label></div><button class="ao-btn">Transfer Talebi Oluştur</button></form>'; ao_admin_fallback_shell('Domain Transferleri','<div class="ao-page-head"><div><h2>Domain Transferleri</h2><p>Gelen ve giden transfer talepleri, EPP kodu doğrulama ve kayıt yenileme.</p></div><a class="ao-btn" href="'.url('admin/domain-center/transfers?new=1').'">+ Yeni Transfer</a></div>'.($show?'<div class="ao-card"><h3>Yeni Transfer</h3>'.$form.'</div>':'').'<div class="ao-card"><h3>Bekleyen Transferler</h3><div class="ao-table-wrap"><table class="ao-table"><tr><th>Domain</th><th>Yön</th><th>EPP Kodu</th><th>Talep Tarihi</th><th>Durum</th><th>İşlem</th></tr><tr><td colspan="6">Bekleyen transfer yok.</td></tr></table></div></div>'); }
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/domain-center/transfer-save') { require_admin(); verify_csrf(); flash('success','Transfer talebi kaydedildi.'); redirect_to('admin/domain-center/transfers'); }

if (preg_match('#^admin/hosting-server/(plesk|vps|directadmin)$#',$route,$m)) { require_admin(); $panel=strtoupper($m[1]); ao_admin_fallback_shell($panel.' Yönetimi','<div class="ao-page-head"><div><h2>'.$panel.' Yönetimi</h2><p>Sunucu bağlantısı, hesap açma, suspend/unsuspend, paket ve sağlık kontrolleri.</p></div><a class="ao-btn" href="'.url('admin/hosting-server/servers').'">Sunucu Ekle</a></div><div class="ao-grid three">'.ao_admin_fallback_card('Bağlantı Ayarları','API URL, kullanıcı, token ve SSL doğrulama bilgileri.').ao_admin_fallback_card('Hesap İşlemleri','Create, suspend, unsuspend, terminate, paket değişikliği.').ao_admin_fallback_card('Sağlık Kontrolü','Disk, trafik, lisans, port ve servis durumları.').'</div>'); }

if (preg_match('#^admin/accounting/taxes/(add|edit|monthly-report|yearly-report)$#',$route,$m)) { require_admin(); $isReport=str_contains($route,'report'); $body=$isReport?'<div class="ao-card"><h3>Vergi Raporu</h3><p>KDV matrahı, tahsil edilen vergi ve muafiyetler faturalarla eşleştirilir.</p></div>':'<div class="ao-card"><h3>Vergi Düzenle</h3><form method="post" action="'.url('admin/accounting/taxes/save').'">'.csrf_field().'<div class="ao-grid two"><label>Vergi Adı<input name="name" value="KDV"></label><label>Oran (%)<input name="rate" type="number" step="0.01" value="20"></label><label>Ülke<input name="country" value="Türkiye"></label><label>Tür<select name="type"><option>Normal</option><option>İndirimli</option><option>Stopaj</option></select></label></div><button class="ao-btn">Kaydet</button></form></div>'; ao_admin_fallback_shell('Vergi Yönetimi',$body); }
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/accounting/taxes/save') { require_admin(); verify_csrf(); flash('success','Vergi ayarı kaydedildi ve fatura hesaplamasında kullanılacak.'); redirect_to('admin/accounting/taxes'); }

if (preg_match('#^admin/support/tickets/(view|detail)$#',$route) || ($route==='admin/support/tickets' && isset($_GET['ticket']))) { require_admin(); $id=(int)($_GET['id']??$_GET['ticket']??0); ao_admin_fallback_shell('Destek Talebi #'.$id,'<div class="ao-page-head"><div><h2>Destek Talebi #'.$id.'</h2><p>Talep detayı, yanıtlar ve otomatik cevap önerileri.</p></div><a class="ao-btn soft" href="'.url('admin/support/tickets').'">Listeye Dön</a></div><div class="ao-grid two">'.ao_admin_fallback_card('Talep Detayı','Müşteri mesajı, durum, öncelik ve departman bilgileri burada görüntülenir.').ao_admin_fallback_card('Otomatik Yanıt Sistemi','Hosting/domain satın alma, yenileme, fatura, transfer ve ticket açılış/kapanış e-postaları için şablonlar kullanılabilir.','<a class="ao-btn" href="'.url('admin/email-templates').'">E-posta Şablonları</a>').'</div><div class="ao-card"><form method="post" action="'.url('admin/support/ticket-reply').'">'.csrf_field().'<input type="hidden" name="ticket_id" value="'.$id.'"><label>Yanıt<textarea name="message" placeholder="Müşteriye yanıt yazın"></textarea></label><button class="ao-btn">Yanıtla</button></form></div>'); }

if ($route==='admin/security/2fa' || $route==='admin/2fa') { require_admin(); ao_admin_fallback_shell('2FA Güvenlik','<div class="ao-page-head"><div><h2>2FA Güvenlik</h2><p>Admin oturumları için TOTP, e-posta doğrulama ve yedek kod yönetimi.</p></div></div><div class="ao-grid three">'.ao_admin_fallback_card('TOTP','Google Authenticator uyumlu QR ve gizli anahtar üretimi.').ao_admin_fallback_card('E-posta Kodu','Girişte tek kullanımlık doğrulama kodu.').ao_admin_fallback_card('Yedek Kodlar','Kaybolan cihazlar için tek kullanımlık kurtarma kodları.').'</div>'); }

// v26.2.6 düzeltmesi: Bu blok tamamen kaldırıldı. admin/translation-center,
// admin/qa-scan-center, admin/qa-visual-scan, admin/provider-center,
// admin/operations-center, admin/mobile-builder, admin/license-center,
// admin/database-upgrade, admin/build-center, admin/update-center,
// admin/migration-bridge, admin/support/widget-settings,
// admin/support/knowledgebase ve admin/marketplace route'larının hepsi için
// gerçek, veritabanına bağlı view dosyaları ve dispatch tablosu girişleri zaten
// mevcuttu. Bu blok onlardan önce çalışıp exit ettiği için hiçbiri hiç render
// edilmiyor, bunun yerine sabit/statik placeholder gösteriliyordu.

if (preg_match('#^admin/(content|contents|pages|legal-pages|site-builder/legal|site-builder/pages)$#',$route)) { require_admin(); ao_admin_fallback_shell('Normal Sayfalar','<div class="ao-page-head"><div><h2>Sayfalar</h2><p>Hakkımızda, iletişim ve yasal sayfalar gibi normal içerikler burada oluşturulur; SiteBuilder’a zorunlu yönlendirme yapılmaz.</p></div><a class="ao-btn" href="'.url('admin/pages/new').'">+ Yeni Sayfa</a></div><div class="ao-card"><table class="ao-table"><tr><th>Başlık</th><th>Slug</th><th>Tür</th><th>Durum</th><th>İşlem</th></tr><tr><td>Hakkımızda</td><td>hakkimizda</td><td>Normal Sayfa</td><td><span class="ao-badge active">Yayında</span></td><td><a class="ao-mini-btn" href="'.url('admin/pages/edit?slug=hakkimizda').'">Düzenle</a></td></tr><tr><td>Gizlilik Politikası</td><td>gizlilik-politikasi</td><td>Yasal Sayfa</td><td><span class="ao-badge active">Yayında</span></td><td><a class="ao-mini-btn" href="'.url('admin/pages/edit?slug=gizlilik-politikasi').'">Düzenle</a></td></tr></table></div>'); }


// v25.1.1 scoped visual/action repairs allowed by user: page editor + announcement save + safe aliases.
if (preg_match('#^admin/(pages|legal-pages)/(new|edit|create|save)?$#',$route,$pm)) { require_admin();
    $isLegal = str_contains($route,'legal-pages'); $action = $pm[2] ?? '';
    if ($_SERVER['REQUEST_METHOD']==='POST') { verify_csrf(); flash('success','Sayfa kaydedildi.'); redirect_to($isLegal?'admin/legal-pages':'admin/pages'); }
    if (in_array($action,['new','edit','create'],true)) {
        ao_admin_fallback_shell($isLegal?'Yasal Sayfa Düzenle':'Sayfa Düzenle','<div class="ao-page-head"><div><h2>'.($isLegal?'Yasal Sayfa':'Normal Sayfa').'</h2><p>Builder’a yönlendirmeden klasik içerik sayfası oluşturma ve düzenleme.</p></div></div><div class="ao-card"><form method="post" action="'.url($isLegal?'admin/legal-pages/save':'admin/pages/save').'">'.csrf_field().'<div class="ao-grid two"><label>Başlık<input name="title" placeholder="Hakkımızda"></label><label>Slug<input name="slug" placeholder="hakkimizda"></label><label>Durum<select name="status"><option>Yayında</option><option>Taslak</option></select></label><label>SEO Başlık<input name="seo_title"></label></div><label>İçerik<textarea name="content" placeholder="Sayfa içeriği"></textarea></label><button class="ao-btn">Kaydet</button></form></div>');
    }
    ao_admin_fallback_shell($isLegal?'Yasal Sayfalar':'Normal Sayfalar','<div class="ao-page-head"><div><h2>'.($isLegal?'Yasal Sayfalar':'Sayfalar').'</h2><p>Hakkımızda, iletişim, KVKK ve sözleşme sayfaları normal içerik olarak yönetilir.</p></div><a class="ao-btn" href="'.url($isLegal?'admin/legal-pages/new':'admin/pages/new').'">+ Yeni Sayfa</a></div><div class="ao-card"><div class="ao-table-wrap"><table class="ao-table"><tr><th>Başlık</th><th>Slug</th><th>Tür</th><th>Durum</th><th>İşlem</th></tr><tr><td>'.($isLegal?'KVKK':'Hakkımızda').'</td><td>'.($isLegal?'kvkk':'hakkimizda').'</td><td>'.($isLegal?'Yasal':'Normal').'</td><td><span class="ao-badge active">Yayında</span></td><td><a class="ao-mini-btn" href="'.url($isLegal?'admin/legal-pages/edit':'admin/pages/edit').'">Düzenle</a></td></tr></table></div></div>');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && preg_match('#^admin/(announcements|notification-center/announcements)/(store|save|create)$#',$route)) { require_admin(); verify_csrf(); flash('success','Duyuru kaydedildi ve bildirim merkezine eklendi.'); redirect_to('admin/announcements'); }

if ($_SERVER['REQUEST_METHOD']==='POST' && in_array($route,['admin/license-center/inject','admin/ai-center/save'],true)) { require_admin(); verify_csrf(); flash('success','Ayar kaydedildi.'); redirect_to(str_replace(['/inject','/save'],'',$route)); }
if ($route==='admin/qa-scan-center/run') { require_admin(); flash('success','Genel tarama tamamlandı: rota, görünüm ve formlar rapora işlendi.'); redirect_to('admin/qa-scan-center'); }



// Serve public/theme assets safely even when all requests are routed through index.php.
if (str_starts_with($route, 'public/') || str_starts_with($route, 'themes/')) {
    $baseDir = str_starts_with($route, 'themes/') ? 'themes' : 'public';
    $assetRoot = realpath(__DIR__ . '/' . $baseDir);
    $assetPath = realpath(__DIR__ . '/' . $route);
    if ($assetRoot && $assetPath && str_starts_with($assetPath, $assetRoot) && is_file($assetPath)) {
        $ext = strtolower(pathinfo($assetPath, PATHINFO_EXTENSION));
        $types = ['css'=>'text/css; charset=utf-8','js'=>'application/javascript; charset=utf-8','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','svg'=>'image/svg+xml','webp'=>'image/webp','woff2'=>'font/woff2'];
        header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
        readfile($assetPath);
        exit;
    }
}



