<?php
// Payment, credit and Shopier helper functions.
function ao_schema_ensure_v990() {
    static $done=false; if($done) return; $done=true;
    if(function_exists('ao_schema_ensure_v900')) ao_schema_ensure_v900();
    try { db()->exec("CREATE TABLE IF NOT EXISTS payment_gateway_transactions (id int(11) NOT NULL AUTO_INCREMENT, customer_id int(11) DEFAULT NULL, invoice_id int(11) DEFAULT NULL, topup_id int(11) DEFAULT NULL, gateway varchar(80) NOT NULL, gateway_order_id varchar(120) DEFAULT NULL, gateway_transaction_id varchar(160) DEFAULT NULL, amount decimal(12,2) DEFAULT 0.00, fee_amount decimal(12,2) DEFAULT 0.00, currency varchar(10) DEFAULT 'TRY', status varchar(40) DEFAULT 'pending', request_payload longtext DEFAULT NULL, response_payload longtext DEFAULT NULL, callback_payload longtext DEFAULT NULL, created_at timestamp NOT NULL DEFAULT current_timestamp(), updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(), PRIMARY KEY(id), KEY gateway(gateway), KEY status(status), KEY customer_id(customer_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS credit_topups (id int(11) NOT NULL AUTO_INCREMENT, customer_id int(11) NOT NULL, amount decimal(12,2) NOT NULL, fee_amount decimal(12,2) DEFAULT 0.00, total_amount decimal(12,2) NOT NULL, currency varchar(10) DEFAULT 'TRY', gateway varchar(80) DEFAULT 'manual', status varchar(40) DEFAULT 'pending', reference varchar(80) DEFAULT NULL, invoice_id int(11) DEFAULT NULL, payment_id int(11) DEFAULT NULL, notes text DEFAULT NULL, created_at timestamp NOT NULL DEFAULT current_timestamp(), updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(), PRIMARY KEY(id), KEY customer_id(customer_id), KEY status(status), KEY gateway(gateway)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS credit_transactions (id int(11) NOT NULL AUTO_INCREMENT, customer_id int(11) NOT NULL, type varchar(40) DEFAULT 'credit', amount decimal(14,2) DEFAULT 0.00, balance_after decimal(14,2) DEFAULT 0.00, currency varchar(10) DEFAULT 'TRY', description varchar(255) DEFAULT NULL, created_at timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY(id), KEY customer_id(customer_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS payments (id int(11) NOT NULL AUTO_INCREMENT, customer_id int(11) DEFAULT NULL, invoice_id int(11) DEFAULT NULL, type varchar(80) DEFAULT 'payment', method varchar(80) DEFAULT 'manual', gateway varchar(80) DEFAULT 'manual', amount decimal(14,2) DEFAULT 0.00, currency varchar(10) DEFAULT 'TRY', transaction_id varchar(160) DEFAULT NULL, status varchar(40) DEFAULT 'completed', notes text DEFAULT NULL, created_at timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY(id), KEY customer_id(customer_id), KEY invoice_id(invoice_id), KEY status(status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("ALTER TABLE credit_transactions ADD COLUMN balance_after decimal(14,2) DEFAULT 0.00 AFTER amount"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("ALTER TABLE payments ADD COLUMN type varchar(80) DEFAULT 'payment' AFTER customer_id"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("ALTER TABLE payments ADD COLUMN method varchar(80) DEFAULT 'manual' AFTER type"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("ALTER TABLE payments ADD COLUMN notes text NULL AFTER status"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("ALTER TABLE customers ADD COLUMN balance decimal(14,2) DEFAULT 0.00"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("ALTER TABLE customers ADD COLUMN credit_balance decimal(14,2) DEFAULT 0.00"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS shopier_settings (id int(11) NOT NULL AUTO_INCREMENT, setting_key varchar(120) NOT NULL UNIQUE, setting_value text DEFAULT NULL, updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("INSERT INTO payment_fee_rules(gateway,label,invoice_line_label,fee_percent,last_known_fee_percent,fee_fixed,last_known_fee_fixed,currency,payer_mode,rate_source,api_enabled,is_active) VALUES ('shopier','Shopier Kredi Kartı','Kart İşlem Komisyonu',4.990,4.990,0,0,'TRY','customer','manual',0,1) ON DUPLICATE KEY UPDATE label=VALUES(label),invoice_line_label=VALUES(invoice_line_label),payer_mode='customer',is_active=1"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("INSERT IGNORE INTO shopier_settings(setting_key,setting_value) VALUES ('auth_mode','pat'),('pat',''),('api_key',''),('api_secret',''),('website_index','1'),('test_mode','1'),('callback_secret',''),('commission_gateway','shopier')"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("INSERT IGNORE INTO admin_search_index(title,route,category,keywords,is_active) VALUES ('Shopier Ödeme Ayarları','admin/accounting/payment-fees','shopier ödeme kredi kartı sanal pos ödeme yöntemi callback api key api secret','Muhasebe',1),('Müşteri Kredi Merkezi','client/credit','kredi bakiye yükle müşteri ödeme shopier havale eft','Müşteri Paneli',1)"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("INSERT INTO settings(setting_key,setting_value) VALUES ('ahost_version','25.0.0-rc25') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_shopier_setting($key,$default='') { ao_schema_ensure_v990(); try{ $q=db()->prepare('SELECT setting_value FROM shopier_settings WHERE setting_key=? LIMIT 1'); $q->execute([$key]); $v=$q->fetchColumn(); return $v===false?$default:$v; }catch(Throwable $e){ return $default; } }
function ao_shopier_save_settings($data) {
    ao_schema_ensure_v990();
    $auth = in_array(($data['auth_mode'] ?? 'pat'), ['pat','legacy'], true) ? $data['auth_mode'] : 'pat';
    $keys = ['auth_mode','pat','api_key','api_secret','website_index','test_mode','callback_secret'];
    foreach($keys as $k){
        $v = $k==='auth_mode' ? $auth : trim((string)($data[$k] ?? ''));
        try{ db()->prepare('INSERT INTO shopier_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)')->execute([$k,$v]); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    }
    if(trim((string)($data['pat'] ?? ''))!=='') save_setting('shopier_pat', trim((string)$data['pat']));
    if(trim((string)($data['api_key'] ?? ''))!=='') save_setting('shopier_api_key', trim((string)$data['api_key']));
    if(trim((string)($data['api_secret'] ?? ''))!=='') save_setting('shopier_api_secret', trim((string)$data['api_secret']));
}
function ao_finance_schema_ensure() { static $done=false; if($done) return; $done=true;
    try { db()->exec("CREATE TABLE IF NOT EXISTS payment_card_tokens (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NOT NULL, gateway VARCHAR(80) DEFAULT 'shopier', token VARCHAR(160) NOT NULL, last4 VARCHAR(8) DEFAULT NULL, brand VARCHAR(80) DEFAULT NULL, is_active TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY customer_id(customer_id), KEY gateway(gateway)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS payment_3ds_sessions (id INT AUTO_INCREMENT PRIMARY KEY, payment_id INT NULL, session_key VARCHAR(120) DEFAULT NULL, gateway VARCHAR(80) DEFAULT 'shopier', status VARCHAR(40) DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, KEY payment_id(payment_id), KEY status(status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS recurring_payments (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NOT NULL, invoice_id INT NULL, method VARCHAR(80) DEFAULT 'card', gateway VARCHAR(80) DEFAULT 'shopier', amount DECIMAL(14,2) DEFAULT 0.00, currency VARCHAR(10) DEFAULT 'TRY', frequency VARCHAR(40) DEFAULT 'monthly', next_run_at DATE NULL, status VARCHAR(40) DEFAULT 'active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, KEY customer_id(customer_id), KEY invoice_id(invoice_id), KEY status(status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS bank_accounts (id INT AUTO_INCREMENT PRIMARY KEY, bank_name VARCHAR(160) NOT NULL, account_holder VARCHAR(190) NULL, iban VARCHAR(80) NULL, currency VARCHAR(10) DEFAULT 'TRY', is_active TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY currency(currency)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS accounting_expenses (id INT AUTO_INCREMENT PRIMARY KEY, description VARCHAR(255) NOT NULL, amount DECIMAL(14,2) DEFAULT 0.00, currency VARCHAR(10) DEFAULT 'TRY', category VARCHAR(80) DEFAULT 'general', expense_date DATE NULL, status VARCHAR(40) DEFAULT 'pending', notes TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY status(status), KEY expense_date(expense_date)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS accounting_logs (id INT AUTO_INCREMENT PRIMARY KEY, event_key VARCHAR(120) DEFAULT NULL, message TEXT NULL, details TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY event_key(event_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("ALTER TABLE invoices ADD COLUMN currency VARCHAR(10) DEFAULT 'TRY' AFTER total"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("ALTER TABLE invoices ADD COLUMN tax_details TEXT NULL AFTER tax"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("ALTER TABLE invoices ADD COLUMN payment_reference VARCHAR(120) NULL AFTER payment_terms"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("ALTER TABLE invoices ADD COLUMN payment_terms VARCHAR(120) NULL AFTER due_date"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("UPDATE invoices SET currency=COALESCE(NULLIF(currency,''),'TRY') WHERE currency IS NULL OR currency=''"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try {
        $q=db()->prepare('SELECT COUNT(*) FROM bank_accounts'); $q->execute(); if((int)$q->fetchColumn()===0){ db()->prepare('INSERT INTO bank_accounts(bank_name,account_holder,iban,currency,is_active) VALUES(?,?,?,?,1)')->execute(['Ziraat Bankası','Ahost One','TR12 0001 0000 0000 0000 0000 01','TRY']); db()->prepare('INSERT INTO bank_accounts(bank_name,account_holder,iban,currency,is_active) VALUES(?,?,?,?,1)')->execute(['İş Bankası','Ahost One','TR34 0006 1005 0000 0000 0000 02','TRY']); }
    } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_accounting_log($eventKey,$message,$details='') {
    try { ao_finance_schema_ensure(); db()->prepare('INSERT INTO accounting_logs(event_key,message,details) VALUES(?,?,?)')->execute([$eventKey,$message,$details]); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_store_card_token($customerId,$gateway,$token,$last4='',$brand='') {
    ao_finance_schema_ensure(); try{ db()->prepare('INSERT INTO payment_card_tokens(customer_id,gateway,token,last4,brand,is_active) VALUES(?,?,?,?,?,1)')->execute([(int)$customerId,$gateway,$token,$last4,$brand]); return true; }catch(Throwable $e){ return false; }
}
function ao_create_recurring_payment($customerId,$invoiceId,$method,$gateway,$amount,$currency='TRY',$frequency='monthly') {
    ao_finance_schema_ensure(); try{ $next=date('Y-m-d', strtotime('+1 month')); db()->prepare('INSERT INTO recurring_payments(customer_id,invoice_id,method,gateway,amount,currency,frequency,next_run_at,status) VALUES(?,?,?,?,?,?,?,?,"active")')->execute([(int)$customerId,(int)$invoiceId,$method,$gateway,(float)$amount,$currency,$frequency,$next]); return true; }catch(Throwable $e){ return false; }
}
function ao_create_3ds_session($paymentId,$gateway='shopier') {
    ao_finance_schema_ensure(); $sessionKey='3DS-'.time().'-'.random_int(1000,9999); try{ db()->prepare('INSERT INTO payment_3ds_sessions(payment_id,session_key,gateway,status) VALUES(?,?,?,"pending")')->execute([(int)$paymentId,$sessionKey,$gateway]); return $sessionKey; }catch(Throwable $e){ return ''; }
}
function ao_complete_3ds_session($paymentId,$txid='') {
    ao_finance_schema_ensure(); try{ db()->prepare('UPDATE payment_3ds_sessions SET status="completed" WHERE payment_id=?')->execute([(int)$paymentId]); db()->prepare('UPDATE payments SET status="completed", transaction_id=COALESCE(NULLIF(?,""),transaction_id) WHERE id=?')->execute([$txid,(int)$paymentId]); return true; }catch(Throwable $e){ return false; }
}
function ao_process_invoice_payment($invoiceId,$customerId,$amount,$method,$txid,$status='completed',$notes='') {
    ao_schema_ensure_v990(); ao_finance_schema_ensure(); $invoiceId=(int)$invoiceId; $customerId=(int)$customerId; $amount=(float)$amount; if($invoiceId<=0||$customerId<=0) throw new Exception('Fatura/müşteri bilgisi eksik.');
    $pdo=db(); $pdo->beginTransaction();
    try{
        $q=$pdo->prepare('SELECT * FROM invoices WHERE id=? AND customer_id=? LIMIT 1 FOR UPDATE'); $q->execute([$invoiceId,$customerId]); $invoice=$q->fetch(); if(!$invoice) throw new Exception('Fatura bulunamadı.');
        $invoiceTotal=(float)($invoice['total'] ?? 0);
        $alreadyPaidQ=$pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM payments WHERE invoice_id=? AND status IN ("completed","paid")');
        $alreadyPaidQ->execute([$invoiceId]);
        $alreadyPaid=(float)$alreadyPaidQ->fetchColumn();
        $due=max(0, $invoiceTotal - $alreadyPaid);
        if($due<=0) throw new Exception('Bu fatura için ödenecek kalan tutar bulunmuyor.');
        $amount=min(max($amount,0),$due); $txid=$txid ?: 'INV-'.strtoupper(substr(md5(microtime()),0,8));
        if($status==='completed' && $method==='balance'){
            $customerQ=$pdo->prepare('SELECT * FROM customers WHERE id=? LIMIT 1 FOR UPDATE'); $customerQ->execute([$customerId]); $customer=$customerQ->fetch(); if(!$customer) throw new Exception('Müşteri bulunamadı.');
            if((float)($customer['balance'] ?? 0) < $amount) throw new Exception('Bakiyeniz bu tutarı karşılamıyor.');
            $pdo->prepare('UPDATE customers SET balance=balance-? WHERE id=?')->execute([$amount,$customerId]);
        }
        $currency = trim((string)($invoice['currency'] ?? 'TRY')) ?: 'TRY';
        $pdo->prepare('INSERT INTO payments(invoice_id,customer_id,type,method,amount,currency,transaction_id,status,notes) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$invoiceId,$customerId,'invoice_payment',$method,$amount,$currency,$txid,$status,$notes]);
        $paidTotal=(float)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM payments WHERE invoice_id='.(int)$invoiceId.' AND status IN ("completed","paid")')->fetchColumn();
        $invoiceStatus = $paidTotal >= $invoiceTotal ? 'paid' : ($paidTotal > 0 ? 'partial' : ($status==='pending' ? 'pending' : 'unpaid'));
        $pdo->prepare('UPDATE invoices SET status=? WHERE id=?')->execute([$invoiceStatus,$invoiceId]);
        if($invoiceStatus==='paid' && !empty($invoice['order_id'])){ try{ ao_provision_order((int)$invoice['order_id']); } catch(Throwable $e){ ao_log_simple('payment','provision-error','error','Fatura sonrası provision hatası: '.$e->getMessage(),json_encode(['invoice_id'=>$invoiceId,'order_id'=>$invoice['order_id']],JSON_UNESCAPED_UNICODE)); } }
        ao_accounting_log('invoice_payment', 'Fatura ödendi: '.$invoice['invoice_number'], json_encode(['invoice_id'=>$invoiceId,'customer_id'=>$customerId,'amount'=>$amount,'method'=>$method,'status'=>$status], JSON_UNESCAPED_UNICODE));
        $pdo->commit();
        if($status==='completed' && $invoiceStatus!=='unpaid'){
            try{ $custQ=db()->prepare('SELECT * FROM customers WHERE id=? LIMIT 1'); $custQ->execute([$customerId]); $customer=$custQ->fetch(); $name=trim((($customer['first_name']??'').' '.($customer['last_name']??''))); ao_notify_event('invoice_paid',$customerId,['invoice_number'=>$invoice['invoice_number']??'','amount'=>number_format($amount,2,',','.'),'customer_name'=>$name]); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        } elseif($status==='pending'){
            try{ $custQ=db()->prepare('SELECT * FROM customers WHERE id=? LIMIT 1'); $custQ->execute([$customerId]); $customer=$custQ->fetch(); $name=trim((($customer['first_name']??'').' '.($customer['last_name']??''))); ao_notify_event('payment_pending',$customerId,['invoice_number'=>$invoice['invoice_number']??'','amount'=>number_format($amount,2,',','.'),'customer_name'=>$name]); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        }
        return ['invoice_status'=>$invoiceStatus,'payment_status'=>$status];
    }catch(Throwable $e){ $pdo->rollBack(); throw $e; }
}
function ao_credit_topup_create($customer,$amount,$gateway='manual',$invoiceId=0) {
    ao_schema_ensure_v990(); $amount=ao_money_round($amount); if($amount<=0) throw new Exception('Geçerli tutar girin.');
    $quote = $gateway==='manual' ? ['fee'=>0,'customer_total'=>$amount,'currency'=>'TRY','line_label'=>'Kart İşlem Komisyonu'] : ao_payment_fee_quote($amount,$gateway);
    $fee = ao_money_round($quote['fee'] ?? 0); $total=ao_money_round($amount+$fee); $ref='TOP'.date('YmdHis').random_int(100,999);
    db()->prepare('INSERT INTO credit_topups(customer_id,amount,fee_amount,total_amount,currency,gateway,status,reference,invoice_id,notes) VALUES(?,?,?,?,?,?,"pending",?,?,?)')->execute([(int)$customer['id'],$amount,$fee,$total,$quote['currency']??'TRY',$gateway,$ref,(int)$invoiceId,'Müşteri bakiye yükleme talebi']);
    return (int)db()->lastInsertId();
}
function ao_credit_topup_complete($topupId,$txid='manual') {
    ao_schema_ensure_v990();
    $topupId=(int)$topupId;
    $preQ=db()->prepare('SELECT * FROM credit_topups WHERE id=? LIMIT 1');
    $preQ->execute([$topupId]);
    $preTopup=$preQ->fetch();
    if(!$preTopup) throw new Exception('Bakiye yukleme kaydi bulunamadi.');
    if(!empty($preTopup['invoice_id'])){
      if($preTopup['status']==='paid') return $preTopup;
      $amount=(float)$preTopup['amount'];
      $txn='INV-'.strtoupper(substr(md5($txid.microtime()),0,8));
      ao_process_invoice_payment((int)$preTopup['invoice_id'],(int)$preTopup['customer_id'],$amount,$preTopup['gateway'],$txn,'completed','Fatura ödeme: '.$preTopup['reference']);
      db()->prepare('UPDATE credit_topups SET status="paid", notes=CONCAT(COALESCE(notes,""), ?) WHERE id=?')->execute(["\nTX: ".$txid,$topupId]);
      return $preTopup;
    }
    $pdo=db(); $pdo->beginTransaction();
    try{
      $q=$pdo->prepare('SELECT * FROM credit_topups WHERE id=? LIMIT 1 FOR UPDATE'); $q->execute([$topupId]); $t=$q->fetch(); if(!$t) throw new Exception('Bakiye yukleme kaydi bulunamadi.');
      if($t['status']==='paid'){ $pdo->commit(); return $t; }
      $pdo->prepare('UPDATE customers SET balance=COALESCE(balance,0)+?, credit_balance=COALESCE(credit_balance,0)+? WHERE id=?')->execute([(float)$t['amount'],(float)$t['amount'],(int)$t['customer_id']]);
      $s=$pdo->prepare('SELECT COALESCE(balance,credit_balance,0) FROM customers WHERE id=?'); $s->execute([(int)$t['customer_id']]); $bal=(float)$s->fetchColumn();
      $pdo->prepare('INSERT INTO credit_transactions(customer_id,type,amount,balance_after,description) VALUES(?,?,?,?,?)')->execute([(int)$t['customer_id'],'credit',(float)$t['amount'],$bal,'Bakiye yükleme: '.$t['gateway'].' / '.$t['reference']]);
      $pdo->prepare('INSERT INTO payments(customer_id,type,method,amount,currency,transaction_id,status,notes) VALUES(?,?,?,?,?,?,"completed",?)')->execute([(int)$t['customer_id'],'credit_topup',$t['gateway'],(float)$t['total_amount'],$t['currency'],$txid,'Bakiye yükleme tahsilatı']);
      $paymentId=(int)$pdo->lastInsertId();
      $pdo->prepare('UPDATE credit_topups SET status="paid", payment_id=?, notes=CONCAT(COALESCE(notes,""), ?) WHERE id=?')->execute([$paymentId,"\nTX: ".$txid,(int)$topupId]);
      $pdo->commit();
    }catch(Throwable $e){ $pdo->rollBack(); throw $e; }
    return $t;
}
function ao_shopier_payment_url($topupId) {
    // Shopier canlı entegrasyonu için API bilgileri girildiğinde bu merkez gerçek ödeme başlatma katmanına bağlanır.
    // Fresh install güvenli davranış: test modunda yerel onay ekranına yönlendirir.
    return url('payment/shopier/start?topup_id='.(int)$topupId);
}

