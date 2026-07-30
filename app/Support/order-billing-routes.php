<?php
// Order, billing, payment and API integration routes.
function ao_log($type, $action, $description='') {
    try { $q=db()->prepare('INSERT INTO activity_logs(type,action,description,ip_address) VALUES(?,?,?,?)'); $q->execute([$type,$action,$description,$_SERVER['REMOTE_ADDR']??'']); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_api_log($provider, $action, $status, $message='', $payload='') {
    try { $q=db()->prepare('INSERT INTO api_logs(provider,action,status,message,payload) VALUES(?,?,?,?,?)'); $q->execute([$provider,$action,$status,$message,$payload]); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_generate_number($prefix, $table, $field) {
    return $prefix . '-' . date('Y') . '-' . str_pad((string)random_int(1000,9999), 4, '0', STR_PAD_LEFT);
}
function ao_billing_cycle_next_due($cycle){
    $cycle=strtolower(trim((string)$cycle));
    $map=[
        'monthly'=>'+1 month',
        'quarterly'=>'+3 months',
        'semiannually'=>'+6 months',
        'annually'=>'+1 year',
        'biennially'=>'+2 years',
        'triennially'=>'+3 years',
    ];
    if(empty($map[$cycle])) return null;
    return date('Y-m-d', strtotime($map[$cycle]));
}
function ao_order_product_price_for_cycle($productId,$cycle,$fallback=0.0){
    $productId=(int)$productId;
    $cycle=trim((string)$cycle);
    $cycles=$cycle==='onetime' ? ['onetime','one_time'] : ($cycle==='one_time' ? ['one_time','onetime'] : [$cycle]);
    try{
        $in=implode(',',array_fill(0,count($cycles),'?'));
        $q=db()->prepare("SELECT price,price_try,price_usd,currency FROM product_pricing WHERE product_id=? AND (is_active=1 OR is_active IS NULL) AND cycle IN ($in) ORDER BY FIELD(cycle,".implode(',',array_fill(0,count($cycles),'?'))."), id LIMIT 1");
        $q->execute(array_merge([$productId],$cycles,$cycles));
        $row=$q->fetch(PDO::FETCH_ASSOC);
        if($row){
            $try=(float)($row['price_try'] ?? 0);
            $price=(float)($row['price'] ?? 0);
            $usd=(float)($row['price_usd'] ?? 0);
            $cur=strtoupper((string)($row['currency'] ?? 'TRY'));
            if($try<=0 && $usd>0 && function_exists('ao_v23_price_try')) $try=(float)ao_v23_price_try($usd,'USD');
            if($try<=0 && $price>0) $try=$cur!=='TRY' && function_exists('ao_v23_price_try') ? (float)ao_v23_price_try($price,$cur) : $price;
            if($try>0) return $try;
        }
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return (float)$fallback;
}
function ao_simulate_api($provider, $action, $data=[]) {
    $isProduction = admin_setting('production_mode','0') === '1';
    if ($isProduction) {
        $message = strtoupper($provider).' '.$action.' simülasyon akışı production modunda engellendi. Canlı entegrasyon bilgileri tamamlanmadan otomatik başarılı sonuç döndürülmez.';
        ao_api_log($provider, $action, 'error', $message, json_encode($data, JSON_UNESCAPED_UNICODE));
        return ['success'=>false,'message'=>$message,'data'=>$data,'simulated'=>true];
    }
    $mode = 'sandbox';
    $ok = true;
    $message = strtoupper($provider).' '.$action.' '.$mode.' simülasyon akışı başarılı. Production modunda gerçek API entegrasyonu zorunludur.';
    ao_api_log($provider, $action, $ok?'success':'error', $message, json_encode($data, JSON_UNESCAPED_UNICODE));
    return ['success'=>$ok,'message'=>$message,'data'=>$data,'simulated'=>true];
}
function ao_create_invoice_for_order($orderId) {
    $q=db()->prepare('SELECT o.*, c.id customer_id FROM orders o LEFT JOIN customers c ON c.id=o.customer_id WHERE o.id=? LIMIT 1'); $q->execute([$orderId]); $o=$q->fetch();
    if(!$o) return 0;
    $chk=db()->prepare('SELECT id FROM invoices WHERE order_id=? LIMIT 1'); $chk->execute([$orderId]); $existing=$chk->fetchColumn(); if($existing) return (int)$existing;
    $baseSubtotal=(float)$o['total'];
    $paymentMethod=trim((string)($o['payment_method'] ?? 'manual')) ?: 'manual';
    $feeQuote = ($paymentMethod && $paymentMethod !== 'manual') ? ao_payment_fee_quote($baseSubtotal,$paymentMethod) : ['fee'=>0,'line_label'=>'Kart İşlem Komisyonu'];
    $cardFee = (float)($feeQuote['fee'] ?? 0);
    $subtotal=$baseSubtotal+$cardFee; $tax=round($subtotal*0.20,2); $total=$subtotal+$tax; $no=ao_generate_number('INV','invoices','invoice_number');
    $ins=db()->prepare('INSERT INTO invoices(customer_id,order_id,invoice_number,status,subtotal,tax,total,due_date) VALUES(?,?,?,"unpaid",?,?,?,DATE_ADD(CURDATE(), INTERVAL 7 DAY))');
    $ins->execute([$o['customer_id'],$orderId,$no,$subtotal,$tax,$total]); $invoiceId=(int)db()->lastInsertId();
    $items=db()->prepare('SELECT * FROM order_items WHERE order_id=?'); $items->execute([$orderId]); $rows=$items->fetchAll();
    if(!$rows){ db()->prepare('INSERT INTO invoice_items(invoice_id,description,amount) VALUES(?,?,?)')->execute([$invoiceId,'Sipariş '.$o['order_number'],$baseSubtotal]); }
    foreach($rows as $it){ db()->prepare('INSERT INTO invoice_items(invoice_id,description,amount) VALUES(?,?,?)')->execute([$invoiceId,$it['item_name'].' - '.$it['domain'],(float)$it['price']]); }
    if($cardFee>0){ db()->prepare('INSERT INTO invoice_items(invoice_id,description,amount) VALUES(?,?,?)')->execute([$invoiceId,($feeQuote['line_label'] ?? 'Kart İşlem Komisyonu').' - '.strtoupper($paymentMethod),$cardFee]); }
    ao_log('billing','invoice.created','Siparişten fatura oluşturuldu: '.$no);
    return $invoiceId;
}
function ao_provision_order($orderId) {
    $q=db()->prepare('SELECT * FROM orders WHERE id=? LIMIT 1'); $q->execute([$orderId]); $o=$q->fetch(); if(!$o) return;
    $items=db()->prepare('SELECT oi.*, p.type, p.module_name, p.whm_package, p.default_server_id FROM order_items oi LEFT JOIN products p ON p.id=oi.product_id WHERE oi.order_id=?'); $items->execute([$orderId]);
    foreach($items->fetchAll() as $it){
        if(in_array($it['type'], ['hosting','server','service'], true)){
            $billing=$it['billing_cycle']?:'monthly';
            $nextDue=ao_billing_cycle_next_due($billing);
            $svc=db()->prepare('INSERT INTO services(customer_id,product_id,domain,status,billing_cycle,next_due_date) VALUES(?,?,?,?,?,?)');
            $svc->execute([$o['customer_id'],$it['product_id'],$it['domain'],'active',$billing,$nextDue]); $serviceId=(int)db()->lastInsertId();
            if($it['type']==='hosting'){
                $user=preg_replace('/[^a-z0-9]/','', strtolower(substr($it['domain'] ?: 'site'.$serviceId,0,8))) ?: 'site'.$serviceId;
                $srv=null; if(!empty($it['default_server_id'])){ $sq=db()->prepare('SELECT * FROM server_nodes WHERE id=? LIMIT 1'); $sq->execute([(int)$it['default_server_id']]); $srv=$sq->fetch(); }
                if(!$srv){ try{ $srv=db()->query('SELECT * FROM server_nodes ORDER BY FIELD(status,"ready","active","inactive"), id LIMIT 1')->fetch(); }catch(Throwable $e){ $srv=null; } }
                $host=$srv['hostname'] ?? ''; $serverName=$host ?: ($srv['name'] ?? 'TR-WHM-AUTO'); $serverIp=$srv['ip_address'] ?? '185.00.00.20'; $serverId=(int)($srv['id'] ?? 0);
                db()->prepare('INSERT INTO hosting_accounts(service_id,server_id,server_name,server_ip,username,whm_username,panel_password,package_name,disk_mb,disk_used_mb,bandwidth_mb,bandwidth_used_mb,mail_limit,mail_used,mysql_limit,mysql_used,cpanel_url,directadmin_url,webmail_url,whm_url,vps_panel_url,ns1,ns2) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
                  ->execute([$serviceId,$serverId?:null,$serverName,$serverIp,$user,$user,bin2hex(random_bytes(4)),$it['whm_package']?:'starter',10240,0,102400,0,50,0,20,0,$host?'https://'.$host.':2083':'','',$host?'https://'.$host.':2096':'',$host?'https://'.$host.':2087':'','','','']);
                ao_simulate_api('whm','create-account',['service_id'=>$serviceId,'domain'=>$it['domain'],'package'=>$it['whm_package']?:'starter']);
            }
        }
        if($it['type']==='domain' && $it['domain']){
            $dom=db()->prepare('INSERT INTO domains(customer_id,domain_name,registrar,status,registration_date,expiry_date,auto_renew,lock_status,epp_code) VALUES(?,?,?,?,CURDATE(),DATE_ADD(CURDATE(), INTERVAL 1 YEAR),1,1,?)');
            $dom->execute([$o['customer_id'],$it['domain'],'DomainNameAPI','active','EPP-'.strtoupper(substr(md5($it['domain'].time()),0,10))]); $domainId=(int)db()->lastInsertId();
            db()->prepare('INSERT INTO domain_nameservers(domain_id,ns1,ns2) VALUES(?,?,?)')->execute([$domainId,'ns1.ahostone.test','ns2.ahostone.test']);
            ao_simulate_api('domainnameapi','register-domain',['domain'=>$it['domain'],'domain_id'=>$domainId]);
        }
    }
    db()->prepare('UPDATE orders SET status="active", fraud_score=0, provision_status="completed" WHERE id=?')->execute([$orderId]);
    ao_log('orders','order.provisioned','Sipariş aktifleştirildi: '.$orderId);
}

if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/orders/create') {
    $customerId=(int)($_POST['customer_id']??0); $productId=(int)($_POST['product_id']??0); $domain=trim($_POST['domain']??''); $cycle=trim($_POST['billing_cycle']??'monthly'); $payment=trim($_POST['payment_method']??'manual');
    try{
        $p=db()->prepare('SELECT p.* FROM products p WHERE p.id=? LIMIT 1'); $p->execute([$productId]); $prod=$p->fetch();
        if(!$customerId || !$prod) throw new Exception('Müşteri ve ürün zorunludur.');
        $price = ao_order_product_price_for_cycle($productId,$cycle,(float)($prod['price'] ?? 0)); if($price<=0) $price=(float)($_POST['price']??0);
        $fraud = (stripos($domain,'fraud')!==false || $price>10000) ? 85 : 12; $status = $fraud>70 ? 'fraud' : 'pending';
        $no=ao_generate_number('AO','orders','order_number');
        db()->prepare('INSERT INTO orders(customer_id,order_number,status,total,payment_method,fraud_score,provision_status,notes) VALUES(?,?,?,?,?,?,"pending",?)')->execute([$customerId,$no,$status,$price,$payment,$fraud,trim($_POST['notes']??'')]);
        $orderId=(int)db()->lastInsertId();
        db()->prepare('INSERT INTO order_items(order_id,product_id,item_type,item_name,domain,billing_cycle,price,setup_fee,module_name) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$orderId,$productId,$prod['type'],$prod['name'],$domain,$cycle,$price,0,$prod['module_name']]);
        ao_create_invoice_for_order($orderId); ao_log('orders','order.created','Manuel sipariş oluşturuldu: '.$no);
        try { ao_notify_event('order_created',$customerId,['order_number'=>$no,'domain'=>$domain,'amount'=>number_format($price,2,',','.'),'customer_name'=>'']); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        flash('success','Sipariş oluşturuldu ve fatura hazırlandı.');
        redirect_to('admin/orders?view='.$orderId);
    }catch(Throwable $e){ flash('error','Sipariş oluşturulamadı: '.$e->getMessage()); redirect_to('admin/orders/new'); }
}
if (($route==='admin/orders/approve' || $route==='admin/orders/cancel' || $route==='admin/orders/fraud-clear')) {
    $id=(int)($_GET['id']??0);
    try{
        if($route==='admin/orders/approve'){ ao_create_invoice_for_order($id); ao_provision_order($id); flash('success','Sipariş onaylandı, fatura ve servis/domain akışı işlendi.'); }
        elseif($route==='admin/orders/fraud-clear'){ db()->prepare('UPDATE orders SET status="pending", fraud_score=20 WHERE id=?')->execute([$id]); ao_log('orders','fraud.cleared','Fraud inceleme temizlendi: '.$id); flash('success','Fraud inceleme temizlendi.'); }
        else { db()->prepare('UPDATE orders SET status="cancelled", provision_status="cancelled" WHERE id=?')->execute([$id]); ao_log('orders','order.cancelled','Sipariş iptal edildi: '.$id); flash('success','Sipariş iptal edildi.'); }
    }catch(Throwable $e){ flash('error','Sipariş işlemi tamamlanamadı.'); }
    redirect_to('admin/orders');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/accounting/invoice-create') {
    $customerId=(int)($_POST['customer_id']??0); $desc=trim($_POST['description']??'Manuel Fatura'); $amount=(float)($_POST['amount']??0); $taxRate=(float)($_POST['tax_rate']??20);
    try{ if(!$customerId || $amount<=0) throw new Exception('Müşteri ve tutar zorunludur.'); $tax=round($amount*$taxRate/100,2); $total=$amount+$tax; $no=ao_generate_number('INV','invoices','invoice_number');
        db()->prepare('INSERT INTO invoices(customer_id,invoice_number,status,subtotal,tax,total,due_date) VALUES(?,?,"unpaid",?,?,?,DATE_ADD(CURDATE(), INTERVAL 7 DAY))')->execute([$customerId,$no,$amount,$tax,$total]); $iid=(int)db()->lastInsertId();
        db()->prepare('INSERT INTO invoice_items(invoice_id,description,amount) VALUES(?,?,?)')->execute([$iid,$desc,$amount]); ao_log('billing','invoice.manual','Manuel fatura oluşturuldu: '.$no); flash('success','Fatura oluşturuldu.'); redirect_to('admin/accounting/invoices?view='.$iid);
    }catch(Throwable $e){ flash('error','Fatura oluşturulamadı: '.$e->getMessage()); redirect_to('admin/accounting/invoices'); }
}

if ($route==='admin/accounting/invoice-status') {
    require_admin(); verify_csrf();
    $id=(int)($_GET['id']??0); $status=$_GET['status']??'unpaid';
    $allowed=['draft','pending','unpaid','partial','paid','cancelled','refunded']; if(!in_array($status,$allowed,true)) $status='unpaid';
    try{ db()->prepare('UPDATE invoices SET status=? WHERE id=?')->execute([$status,$id]); ao_log('billing','invoice.status','Fatura durumu güncellendi: '.$id.' => '.$status); flash('success','Fatura durumu güncellendi.'); }
    catch(Throwable $e){ flash('error','Fatura durumu güncellenemedi.'); }
    redirect_to('admin/accounting/invoices?view='.$id);
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/accounting/invoice-update') {
    require_admin(); verify_csrf();
    $id=(int)($_POST['invoice_id']??0); $status=$_POST['status']??'unpaid'; $due=$_POST['due_date']??null; $tax=(float)($_POST['tax']??0);
    $allowed=['draft','pending','unpaid','partial','paid','cancelled','refunded']; if(!in_array($status,$allowed,true)) $status='unpaid';
    try{
        $sumq=db()->prepare('SELECT COALESCE(SUM(amount),0) FROM invoice_items WHERE invoice_id=?'); $sumq->execute([$id]); $subtotal=(float)$sumq->fetchColumn(); $total=$subtotal+$tax;
        db()->prepare('UPDATE invoices SET status=?, due_date=?, subtotal=?, tax=?, total=? WHERE id=?')->execute([$status,$due,$subtotal,$tax,$total,$id]);
        ao_log('billing','invoice.update','Fatura güncellendi: '.$id); flash('success','Fatura güncellendi.');
    }catch(Throwable $e){ flash('error','Fatura güncellenemedi: '.$e->getMessage()); }
    redirect_to('admin/accounting/invoices?view='.$id);
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/accounting/payment-approve') {
    require_admin(); verify_csrf();
    $paymentId=(int)($_POST['payment_id']??0); $note=trim($_POST['note']??'');
    try{
        $q=db()->prepare('SELECT p.*, i.order_id, i.invoice_number FROM payments p LEFT JOIN invoices i ON i.id=p.invoice_id WHERE p.id=? LIMIT 1'); $q->execute([$paymentId]); $p=$q->fetch(); if(!$p) throw new Exception('Ödeme kaydı bulunamadı.');
        if($p['status']!=='pending') throw new Exception('Bu ödeme zaten işlenmiş.');
        $invoiceId=(int)$p['invoice_id'];
        $pdo=db(); $pdo->beginTransaction();
        try{
            $pdo->prepare('UPDATE payments SET status="completed", notes=CONCAT(COALESCE(notes,""), ?) WHERE id=?')->execute([$note?"\nAdmin onay: ".$note:'', $paymentId]);
            $paidTotal=(float)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM payments WHERE invoice_id='.(int)$invoiceId.' AND status IN ("completed","paid")')->fetchColumn();
            $invoiceQ=$pdo->prepare('SELECT * FROM invoices WHERE id=? LIMIT 1 FOR UPDATE'); $invoiceQ->execute([$invoiceId]); $invoice=$invoiceQ->fetch();
            $invoiceStatus = $paidTotal >= ((float)($invoice['total'] ?? 0)) ? 'paid' : 'partial';
            $pdo->prepare('UPDATE invoices SET status=? WHERE id=?')->execute([$invoiceStatus,$invoiceId]);
            if($invoiceStatus==='paid' && !empty($invoice['order_id'])){ try{ ao_provision_order((int)$invoice['order_id']); } catch(Throwable $e){ ao_log_simple('payment','provision-error','error','Onay sonrası provision hatası: '.$e->getMessage(),json_encode(['invoice_id'=>$invoiceId,'order_id'=>$invoice['order_id']],JSON_UNESCAPED_UNICODE)); } }
            $pdo->commit();
            $customerId=(int)$p['customer_id'];
            try{ $custQ=db()->prepare('SELECT * FROM customers WHERE id=? LIMIT 1'); $custQ->execute([$customerId]); $customer=$custQ->fetch(); $name=trim((($customer['first_name']??'').' '.($customer['last_name']??''))); ao_notify_event('invoice_paid',$customerId,['invoice_number'=>$p['invoice_number']??'','amount'=>number_format((float)$p['amount'],2,',','.'),'customer_name'=>$name]); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
            flash('success','Ödeme onaylandı ve fatura işleme alındı.');
        }catch(Throwable $e){ $pdo->rollBack(); throw $e; }
    }catch(Throwable $e){ flash('error','Ödeme onaylanamadı: '.$e->getMessage()); }
    redirect_to('admin/accounting/invoices?view='.(int)($invoiceId ?? 0));
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/accounting/payment-reject') {
    require_admin(); verify_csrf();
    $paymentId=(int)($_POST['payment_id']??0); $note=trim($_POST['note']??'');
    try{
        $q=db()->prepare('SELECT p.*, i.invoice_number FROM payments p LEFT JOIN invoices i ON i.id=p.invoice_id WHERE p.id=? LIMIT 1'); $q->execute([$paymentId]); $p=$q->fetch(); if(!$p) throw new Exception('Ödeme kaydı bulunamadı.');
        db()->prepare('UPDATE payments SET status="rejected", notes=CONCAT(COALESCE(notes,""), ?) WHERE id=?')->execute([$note?"\nAdmin reddi: ".$note:'', $paymentId]);
        if(!empty($p['invoice_id'])) db()->prepare('UPDATE invoices SET status="pending" WHERE id=? AND status="pending"')->execute([(int)$p['invoice_id']]);
        flash('success','Ödeme reddedildi.');
    }catch(Throwable $e){ flash('error','Ödeme reddedilemedi: '.$e->getMessage()); }
    redirect_to('admin/accounting/invoices?view='.(int)($p['invoice_id'] ?? 0));
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/accounting/expense-save') {
    require_admin(); verify_csrf(); ao_finance_schema_ensure();
    $desc=trim($_POST['description']??''); $amount=(float)($_POST['amount']??0); $category=trim($_POST['category']??'general'); $date=trim($_POST['expense_date']??date('Y-m-d'));
    try{ if($desc===''||$amount<=0) throw new Exception('Açıklama ve tutar zorunlu.'); db()->prepare('INSERT INTO accounting_expenses(description,amount,currency,category,expense_date,status,notes) VALUES(?,?,?,?,?,"pending",?)')->execute([$desc,$amount,'TRY',$category,$date,trim($_POST['notes']??'')]); ao_accounting_log('expense_added','Gider kaydı eklendi',json_encode(['description'=>$desc,'amount'=>$amount],JSON_UNESCAPED_UNICODE)); flash('success','Gider kaydı eklendi.'); }catch(Throwable $e){ flash('error','Gider kaydı eklenemedi: '.$e->getMessage()); }
    redirect_to('admin/accounting');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/accounting/bank-save') {
    require_admin(); verify_csrf(); ao_finance_schema_ensure();
    $name=trim($_POST['bank_name']??''); $holder=trim($_POST['account_holder']??''); $iban=trim($_POST['iban']??''); $currency=trim($_POST['currency']??'TRY');
    try{ if($name==='') throw new Exception('Banka adı zorunlu.'); db()->prepare('INSERT INTO bank_accounts(bank_name,account_holder,iban,currency,is_active) VALUES(?,?,?,?,1)')->execute([$name,$holder,$iban,$currency]); flash('success','Banka hesabı eklendi.'); }catch(Throwable $e){ flash('error','Banka hesabı eklenemedi: '.$e->getMessage()); }
    redirect_to('admin/accounting');
}
if ($route==='admin/accounting/reports') {
    require_admin(); ao_finance_schema_ensure();
    $income=(float)db()->query("SELECT COALESCE(SUM(total),0) FROM invoices WHERE status='paid'")->fetchColumn();
    $pending=(float)db()->query("SELECT COALESCE(SUM(total),0) FROM invoices WHERE status IN ('unpaid','pending','overdue')")->fetchColumn();
    $expenses=(float)db()->query('SELECT COALESCE(SUM(amount),0) FROM accounting_expenses')->fetchColumn();
    $payments=db()->query('SELECT * FROM payments ORDER BY id DESC LIMIT 50')->fetchAll();
    $logs=db()->query('SELECT * FROM accounting_logs ORDER BY id DESC LIMIT 50')->fetchAll();
    view('accounting/reports', ['pageTitle'=>'Finans Raporları','income'=>$income,'pending'=>$pending,'expenses'=>$expenses,'payments'=>$payments,'logs'=>$logs]); exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/accounting/invoice-item-add') {
    require_admin(); verify_csrf();
    $id=(int)($_POST['invoice_id']??0); $desc=trim($_POST['description']??''); $amount=(float)($_POST['amount']??0);
    try{ if($id<=0||$desc===''||$amount<=0) throw new Exception('Kalem bilgisi eksik.'); db()->prepare('INSERT INTO invoice_items(invoice_id,description,amount) VALUES(?,?,?)')->execute([$id,$desc,$amount]); ao_recalculate_invoice_total_v2465($id); flash('success','Fatura kalemi eklendi.'); }
    catch(Throwable $e){ flash('error','Kalem eklenemedi: '.$e->getMessage()); }
    redirect_to('admin/accounting/invoices?view='.$id);
}
if ($route==='admin/accounting/invoice-item-delete') {
    require_admin(); verify_csrf();
    $itemId=(int)($_GET['id']??0); $invoiceId=(int)($_GET['invoice_id']??0);
    try{ db()->prepare('DELETE FROM invoice_items WHERE id=? AND invoice_id=?')->execute([$itemId,$invoiceId]); ao_recalculate_invoice_total_v2465($invoiceId); flash('success','Fatura kalemi silindi.'); }
    catch(Throwable $e){ flash('error','Kalem silinemedi.'); }
    redirect_to('admin/accounting/invoices?view='.$invoiceId);
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/accounting/invoice-merge') {
    require_admin(); verify_csrf();
    $target=(int)($_POST['target_invoice_id']??0); $ids=array_filter(array_map('intval', preg_split('/[^0-9]+/', $_POST['source_invoice_ids']??'')));
    try{ foreach($ids as $sid){ if($sid>0 && $sid!==$target){ db()->prepare('UPDATE invoice_items SET invoice_id=? WHERE invoice_id=?')->execute([$target,$sid]); db()->prepare('UPDATE payments SET invoice_id=? WHERE invoice_id=?')->execute([$target,$sid]); db()->prepare('UPDATE invoices SET status="cancelled" WHERE id=?')->execute([$sid]); }} ao_recalculate_invoice_total_v2465($target); ao_log('billing','invoice.merge','Fatura birleştirildi: '.$target); flash('success','Faturalar birleştirildi.'); }
    catch(Throwable $e){ flash('error','Fatura birleştirilemedi: '.$e->getMessage()); }
    redirect_to('admin/accounting/invoices?view='.$target);
}

if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/accounting/payment') {
    $invoiceId=(int)($_POST['invoice_id']??0); $amount=(float)($_POST['amount']??0); $method=trim($_POST['method']??'manual'); $type=trim($_POST['type']??'payment');
    try{ $invq=db()->prepare('SELECT * FROM invoices WHERE id=? LIMIT 1'); $invq->execute([$invoiceId]); $inv=$invq->fetch(); if(!$inv) throw new Exception('Fatura bulunamadı.');
        if($amount<=0) $amount=(float)$inv['total']; $status=$type==='refund'?'refunded':'paid';
        db()->prepare('INSERT INTO payments(invoice_id,customer_id,type,method,amount,currency,transaction_id,status,notes) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$invoiceId,$inv['customer_id'],$type,$method,$amount,'TRY','TX-'.strtoupper(substr(md5(microtime()),0,10)),'completed',trim($_POST['notes']??'')]);
        db()->prepare('UPDATE invoices SET status=? WHERE id=?')->execute([$status,$invoiceId]); ao_log('billing','invoice.'.$status,'Fatura ödeme/iade işlemi: '.$invoiceId); flash('success','Muhasebe işlemi kaydedildi.');
    }catch(Throwable $e){ flash('error','Muhasebe işlemi başarısız: '.$e->getMessage()); }
    redirect_to('admin/accounting/invoices');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/api-integrations/save') {
    try{
        $id=(int)($_POST['id']??0); $name=trim($_POST['name']??''); $provider=trim($_POST['provider']??''); $endpoint=trim($_POST['endpoint']??''); $username=trim($_POST['username']??''); $secret=trim($_POST['secret']??''); $status=trim($_POST['status']??'inactive'); $test=(int)($_POST['test_mode']??1);
        if(!$name || !$provider) throw new Exception('Ad ve sağlayıcı zorunludur.');
        if($id>0) db()->prepare('UPDATE api_integrations SET name=?,provider=?,endpoint=?,username=?,secret=?,status=?,test_mode=? WHERE id=?')->execute([$name,$provider,$endpoint,$username,$secret,$status,$test,$id]);
        else db()->prepare('INSERT INTO api_integrations(name,provider,endpoint,username,secret,status,test_mode) VALUES(?,?,?,?,?,?,?)')->execute([$name,$provider,$endpoint,$username,$secret,$status,$test]);
        flash('success','API entegrasyonu kaydedildi.');
    }catch(Throwable $e){ flash('error','API kaydedilemedi: '.$e->getMessage()); }
    redirect_to('admin/api-integrations');
}
if ($route==='admin/api-integrations/test') {
    $id=(int)($_GET['id']??0); try{ $q=db()->prepare('SELECT * FROM api_integrations WHERE id=?'); $q->execute([$id]); $api=$q->fetch(); if($api){ $res=ao_simulate_api($api['provider'],'connection-test',['endpoint'=>$api['endpoint'],'test_mode'=>$api['test_mode']]); flash('success',$res['message']); } }catch(Throwable $e){ flash('error','API testi başarısız.'); }
    redirect_to('admin/api-integrations');
}




