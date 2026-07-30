<?php
$c=current_customer(); $id=(int)($_GET['id']??0); $flash=get_flash(); $d=null; $dns=[]; $ns=[]; $contact=[];
if (!function_exists('ao_customer_domain_name')) {
    function ao_customer_domain_name(array $row): string { return trim((string)($row['domain_name'] ?? ($row['domain'] ?? ''))); }
}
try{
    $cols=[]; foreach(db()->query('SHOW COLUMNS FROM domains')->fetchAll(PDO::FETCH_ASSOC) as $col){ $cols[$col['Field']]=true; }
    $where=['id=?']; $params=[$id]; $ownerParts=[];
    foreach(['customer_id','client_id','userid','user_id','owner_id'] as $ownerCol){ if(!empty($cols[$ownerCol])){ $ownerParts[]="`{$ownerCol}`=?"; $params[]=(int)$c['id']; } }
    if($ownerParts){ $where[]='('.implode(' OR ',$ownerParts).')'; }
    $q=db()->prepare('SELECT * FROM domains WHERE '.implode(' AND ',$where).' LIMIT 1'); $q->execute($params); $d=$q->fetch();
    if(!$d && (!empty($cols['domain_name']) || !empty($cols['domain']))){
        $serviceDomains=[]; $sd=db()->prepare('SELECT DISTINCT domain FROM services WHERE customer_id=? AND domain IS NOT NULL AND domain<>""'); $sd->execute([(int)$c['id']]); $serviceDomains=array_values(array_filter(array_map(fn($r)=>trim((string)($r['domain']??'')), $sd->fetchAll() ?: [])));
        $domainCol=!empty($cols['domain_name']) ? 'domain_name' : 'domain';
        if($serviceDomains){ $q=db()->prepare('SELECT * FROM domains WHERE id=? AND `'.$domainCol.'` IN ('.implode(',',array_fill(0,count($serviceDomains),'?')).') LIMIT 1'); $q->execute([$id,...$serviceDomains]); $d=$q->fetch(); }
    }
    if($d){ $q=db()->prepare('SELECT * FROM domain_dns_records WHERE domain_id=? ORDER BY record_type,host'); $q->execute([$id]); $dns=$q->fetchAll(); $q=db()->prepare('SELECT * FROM domain_nameservers WHERE domain_id=?'); $q->execute([$id]); $ns=$q->fetch() ?: []; $q=db()->prepare('SELECT * FROM domain_contacts WHERE domain_id=? LIMIT 1'); $q->execute([$id]); $contact=$q->fetch() ?: []; }
}catch(Throwable $e){}
$domainName = $d ? ao_customer_domain_name($d) : '';
?>
<?php if(!$d): ?><div class="customer-panel-card"><h2>Domain bulunamadı</h2><a class="cp-btn" href="<?= url('client/domains') ?>">Domainlerime Dön</a></div><?php return; endif; ?>

<?php if(!empty($_SESSION['last_epp_popup']) && ($_SESSION['last_epp_popup']['domain'] ?? '') === $domainName): $eppPopup=$_SESSION['last_epp_popup']; unset($_SESSION['last_epp_popup']); ?>
<div class="ao-domain-modal is-open"><div class="ao-domain-modal-card"><button type="button" onclick="this.closest('.ao-domain-modal').remove()" class="ao-modal-close">×</button><h3>EPP Kodunuz</h3><p><strong><?= e($eppPopup['domain']) ?></strong></p><code class="ao-epp-code"><?= e($eppPopup['epp']) ?></code><p class="ao-muted">Bu kod SMS olarak da gönderildi.</p></div></div>
<?php endif; ?>

<?php if($flash): ?><div class="cp-alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
<div class="customer-panel-card premium-detail-hero ao-domain-detail-hero" data-domain-widget>
 <div>
  <span class="u2-kicker">Domain Detayı</span>
  <h2><?= e($domainName) ?></h2>
  <p>Registrar: <?= e($d['registrar'] ?? ($d['registrar_name'] ?? (!empty($d['registrar_id']) ? ('Registrar #'.(int)$d['registrar_id']) : 'DomainNameAPI'))) ?> · Durum: <?= e(ao_status_tr($d['status'] ?? 'active')) ?> · Bitiş: <?= e($d['expiry_date']) ?></p>
 </div>
 <div class="ao-domain-action-grid">
  <input type="hidden" data-domain-input value="<?= e($domainName) ?>">
  <button class="ao-domain-action" data-domain-tool="whois"><b>🔎</b><span>WHOIS</span><small>Registrar bilgisi</small></button>
  <button class="ao-domain-action" data-domain-tool="dns"><b>🌐</b><span>DNS</span><small>Kayıtları görüntüle</small></button>
  <button class="ao-domain-action" data-domain-tool="ssl"><b>🔐</b><span>SSL</span><small>Sertifika kontrolü</small></button>
  <button class="ao-domain-action" data-domain-tool="valuation"><b>💎</b><span>Değerleme</span><small>Tahmini değer</small></button>
  <a class="ao-domain-action" href="#tab-nameserver" data-open-tab="nameserver"><b>🧭</b><span>Nameserver</span><small>NS yönetimi</small></a>
  <a class="ao-domain-action" href="#tab-epp" data-open-tab="epp"><b>🧾</b><span>EPP / Kilit</span><small>Kod ve transfer kilidi</small></a>
  <a class="ao-domain-action" href="#tab-dns" data-open-tab="dns"><b>⚙</b><span>DNS Yönetimi</span><small>Yeni kayıt ekle</small></a>
  <a class="ao-domain-action muted" href="<?= url('client/domains') ?>"><b>←</b><span>Listeye Dön</span><small>Domainlerim</small></a>
 </div>
</div>
<div class="customer-panel-card cp-tabs" data-cp-tabs>
 <div class="cp-tab-buttons"><button class="active" data-tab="genel">Genel</button><button data-tab="dns">DNS</button><button data-tab="nameserver">Nameserver</button><button data-tab="whois">WHOIS</button><button data-tab="epp">EPP / Kilit</button><button data-tab="islem">İşlemler</button></div>
 <section id="tab-genel" class="cp-tab-panel active"><h3>Domain Özeti</h3><p><strong>Kayıt:</strong> <?= e($d['registration_date']) ?></p><p><strong>Bitiş:</strong> <?= e($d['expiry_date']) ?></p><p><strong>Oto Yenileme:</strong> <?= $d['auto_renew']?'Açık':'Kapalı' ?></p><p><strong>Kilit:</strong> <?= $d['lock_status']?'Kilitli':'Açık' ?></p></section>
 <section id="tab-dns" class="cp-tab-panel"><h3>DNS Yönetimi</h3><table class="table-like"><tr><th>Tip</th><th>Host</th><th>Değer</th><th>TTL</th><th></th></tr><?php foreach($dns as $r): ?><tr><td><?= e($r['record_type']) ?></td><td><?= e($r['host']) ?></td><td><?= e($r['record_value']) ?></td><td><?= e($r['ttl']) ?></td><td><a class="cp-btn small" href="<?= url('client/domains/dns-delete?id='.(int)$r['id'].'&domain_id='.(int)$d['id']) ?>">Sil</a></td></tr><?php endforeach; if(!$dns): ?><tr><td colspan="5">DNS kaydı yok.</td></tr><?php endif; ?></table><form method="post" action="<?= url('client/domains/dns-save') ?>" class="cp-form"><input type="hidden" name="domain_id" value="<?= (int)$d['id'] ?>"><label>Tip<select name="record_type"><option>A</option><option>AAAA</option><option>CNAME</option><option>MX</option><option>TXT</option><option>SRV</option><option>CAA</option></select></label><label>Host<input name="host" value="@"></label><label>Değer<input name="record_value"></label><label>TTL<input name="ttl" type="number" value="3600"></label><button class="cp-btn">DNS Ekle</button></form></section>
 <section id="tab-nameserver" class="cp-tab-panel"><h3>Nameserver Yönetimi</h3><form method="post" action="<?= url('client/domains/ns-save') ?>" class="cp-form"><input type="hidden" name="domain_id" value="<?= (int)$d['id'] ?>"><label>NS1<input name="ns1" value="<?= e($ns['ns1']??'') ?>"></label><label>NS2<input name="ns2" value="<?= e($ns['ns2']??'') ?>"></label><label>NS3<input name="ns3" value="<?= e($ns['ns3']??'') ?>"></label><label>NS4<input name="ns4" value="<?= e($ns['ns4']??'') ?>"></label><button class="cp-btn">Nameserver Güncelle</button></form></section>
 <section id="tab-whois" class="cp-tab-panel"><h3>WHOIS Bilgileri</h3><p><strong>Ad Soyad:</strong> <?= e($contact['full_name']??'-') ?></p><p><strong>Firma:</strong> <?= e($contact['company']??'-') ?></p><p><strong>E-posta:</strong> <?= e($contact['email']??$c['email']) ?></p><p><strong>Telefon:</strong> <?= e($contact['phone']??$c['phone']) ?></p></section>
 <section id="tab-epp" class="cp-tab-panel"><h3>EPP / Lock</h3><p><strong>EPP Kodu:</strong> <code><?= e(($d['epp_code'] ?? ($d['auth_code'] ?? '')) ?: 'Henüz üretilmedi') ?></code></p><p><strong>Registrar Lock:</strong> <?= $d['lock_status']?'Kilitli':'Açık' ?></p><form method="post" action="<?= url('client/domains/epp-request') ?>" class="cp-form"><input type="hidden" name="domain_id" value="<?= (int)$d['id'] ?>"><button class="cp-btn">EPP Kodu İste + SMS Gönder</button><small>EPP kodu popup olarak gösterilir ve kayıtlı telefonunuza SMS gönderilir.</small></form></section>
 <section id="tab-islem" class="cp-tab-panel"><h3>Domain İşlemleri</h3><p class="ao-muted">Yenileme artık domaini doğrudan uzatmaz; önce sipariş/fatura oluşturur. Ödeme/provisioning tamamlanınca registrar yenilemesi çalışır.</p><div class="button-row"><?php foreach(['renew'=>'1 Yıl Yenile','transfer'=>'Transfer Talebi','toggle-lock'=>'Kilit Aç/Kapat','toggle-autorenew'=>'Oto Yenileme Aç/Kapat'] as $act=>$label): ?><form method="post" action="<?= url('client/domains/action') ?>"><input type="hidden" name="domain_id" value="<?= (int)$d['id'] ?>"><input type="hidden" name="domain_action" value="<?= e($act) ?>"><button class="cp-btn secondary"><?= e($label) ?></button></form><?php endforeach; ?></div></section>
</div>
<script>
document.querySelectorAll('[data-cp-tabs]').forEach(shell=>{
  const clean=v=>String(v||'').replace(/^#?tab-?/,'').replace(/[^a-z0-9_-]/gi,'')||'genel';
  const active=()=>clean(shell.querySelector('[data-tab].active')?.dataset.tab || location.hash || 'genel');
  const open=(t,write=false)=>{
    t=clean(t);
    let found=false;
    shell.querySelectorAll('[data-tab]').forEach(b=>{const ok=b.dataset.tab===t;b.classList.toggle('active',ok);if(ok)found=true;});
    if(!found && t!=='genel') return open('genel',write);
    shell.querySelectorAll('.cp-tab-panel').forEach(p=>p.classList.toggle('active',p.id==='tab-'+t));
    if(write && history.replaceState) history.replaceState(null,'','#tab-'+t);
  };
  shell.querySelectorAll('[data-tab]').forEach(btn=>btn.addEventListener('click',()=>open(btn.dataset.tab,true)));
  document.querySelectorAll('[data-open-tab]').forEach(a=>a.addEventListener('click',e=>{e.preventDefault();open(a.dataset.openTab,true);shell.scrollIntoView({behavior:'smooth',block:'start'});}));
  shell.addEventListener('submit',e=>{const form=e.target;if(!form||!form.appendChild)return;let input=form.querySelector('input[name="return_tab"]');if(!input){input=document.createElement('input');input.type='hidden';input.name='return_tab';form.appendChild(input);}input.value=active();},true);
  open(clean(location.hash),false);
  window.addEventListener('hashchange',()=>open(clean(location.hash),false));
});
</script>
