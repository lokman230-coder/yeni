<?php
$c=current_customer(); $rows=[]; $flash=get_flash();
if (!function_exists('ao_customer_domain_rows')) {
    function ao_customer_domain_rows(int $customerId): array {
        if($customerId<=0) return [];
        try{
            $cols=[]; foreach(db()->query('SHOW COLUMNS FROM domains')->fetchAll(PDO::FETCH_ASSOC) as $col){ $cols[$col['Field']]=true; }
            $where=[]; $params=[];
            foreach(['customer_id','client_id','userid','user_id','owner_id'] as $ownerCol){ if(!empty($cols[$ownerCol])){ $where[]="d.`{$ownerCol}`=?"; $params[]=$customerId; } }
            $serviceDomains=[]; try{ $sd=db()->prepare('SELECT DISTINCT domain FROM services WHERE customer_id=? AND domain IS NOT NULL AND domain<>""'); $sd->execute([$customerId]); $serviceDomains=array_values(array_filter(array_map(fn($r)=>trim((string)($r['domain']??'')), $sd->fetchAll() ?: []))); }catch(Throwable $e){}
            $domainCol=!empty($cols['domain_name'])?'domain_name':(!empty($cols['domain'])?'domain':'');
            if($serviceDomains && $domainCol!==''){ $where[]='d.`'.$domainCol.'` IN ('.implode(',',array_fill(0,count($serviceDomains),'?')).')'; array_push($params,...$serviceDomains); }
            if(!$where) return [];
            $st=db()->prepare('SELECT d.* FROM domains d WHERE ('.implode(' OR ',$where).') ORDER BY d.id DESC'); $st->execute($params);
            return $st->fetchAll() ?: [];
        }catch(Throwable $e){ return []; }
    }
    function ao_customer_domain_row_name(array $row): string { return trim((string)($row['domain_name'] ?? ($row['domain'] ?? '-'))) ?: '-'; }
}
try{$rows=ao_customer_domain_rows((int)($c['id']??0));}catch(Throwable $e){}
?>
<?php if($flash): ?><div class="cp-alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
<div class="customer-panel-card"><h2>Domainlerim</h2><p>Domain yenileme, nameserver, DNS, kilit, EPP ve transfer işlemlerinizi buradan yönetin.</p><div class="button-row"><a class="cp-btn" href="<?= url('domain') ?>">Yeni Domain Sorgula</a></div></div>
<div class="customer-panel-card"><table class="table-like"><tr><th>Domain</th><th>Registrar</th><th>Durum</th><th>Bitiş Tarihi</th><th>Kilit</th><th>Oto Yenileme</th><th>İşlem</th></tr><?php foreach($rows as $r): ?><tr><td><strong><?= e(ao_customer_domain_row_name($r)) ?></strong></td><td><?= e($r['registrar'] ?? ($r['registrar_name'] ?? (!empty($r['registrar_id']) ? ('Registrar #'.(int)$r['registrar_id']) : 'DomainNameAPI'))) ?></td><td><?= e($r['status'] ?? '-') ?></td><td><?= e($r['expiry_date'] ?? ($r['next_due_date'] ?? '-')) ?></td><td><?= !empty($r['lock_status'])?'Kilitli':'Açık' ?></td><td><?= !empty($r['auto_renew'])?'Açık':'Kapalı' ?></td><td><a class="cp-btn small" href="<?= url('client/domains/view?id='.(int)$r['id']) ?>">Yönet</a></td></tr><?php endforeach; if(!$rows): ?><tr><td colspan="7">Domain kaydı yok.</td></tr><?php endif; ?></table></div>
