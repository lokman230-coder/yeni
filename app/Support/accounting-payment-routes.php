<?php
// Accounting invoice delivery and Shopier payment routes.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/accounting/invoice-email-send') {
    require_admin(); verify_csrf();
    $id=(int)($_POST['invoice_id']??0);
    try{
        $q=db()->prepare('SELECT i.*,c.first_name,c.last_name,c.email,c.id customer_id FROM invoices i LEFT JOIN customers c ON c.id=i.customer_id WHERE i.id=? LIMIT 1');
        $q->execute([$id]); $inv=$q->fetch();
        if(!$inv || empty($inv['email'])) throw new Exception('Fatura veya müşteri e-postası bulunamadı.');
        $subject = ($inv['invoice_number'] ?? 'Fatura') . ' numaralı faturanız';
        $body = "Merhaba ".trim(($inv['first_name']??'').' '.($inv['last_name']??'')).",\n\n";
        $body .= ($inv['invoice_number'] ?? 'Faturanız')." numaralı faturanız oluşturuldu.\n";
        $body .= "Durum: ".($inv['status'] ?? '-')."\n";
        $body .= "Son ödeme: ".($inv['due_date'] ?? '-')."\n";
        $body .= "Toplam: ".number_format((float)($inv['total'] ?? 0),2,',','.')." TL\n\n";
        $body .= "Faturayı müşteri panelinizden görüntüleyebilirsiniz.";
        $res = function_exists('ao_send_email_notification') ? ao_send_email_notification($inv['email'],$subject,$body,'invoice_send') : ['ok'=>@mail($inv['email'],$subject,$body),'message'=>'mail()'];
        try{
            db()->exec("CREATE TABLE IF NOT EXISTS invoice_email_logs (id int(11) NOT NULL AUTO_INCREMENT, invoice_id int(11) NOT NULL, customer_id int(11) DEFAULT NULL, admin_id int(11) DEFAULT NULL, recipient_email varchar(190) NOT NULL, subject varchar(255) NOT NULL, status varchar(40) NOT NULL DEFAULT 'pending', message text DEFAULT NULL, created_at timestamp DEFAULT current_timestamp(), PRIMARY KEY(id), KEY invoice_id(invoice_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $admin=current_admin();
            db()->prepare('INSERT INTO invoice_email_logs(invoice_id,customer_id,admin_id,recipient_email,subject,status,message) VALUES(?,?,?,?,?,?,?)')->execute([$id,(int)($inv['customer_id']??0),(int)($admin['id']??0),$inv['email'],$subject,!empty($res['ok'])?'sent':'error',$res['message']??'']);
        }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        flash(!empty($res['ok'])?'success':'error', !empty($res['ok']) ? 'Fatura maili müşteriye gönderildi.' : 'Fatura maili gönderilemedi: '.($res['message']??''));
    }catch(Throwable $e){ flash('error','Fatura maili gönderilemedi: '.$e->getMessage()); }
    redirect_to('admin/accounting/invoices?view='.$id);
}

if ($route === 'admin/accounting/invoice-pdf') {
    require_admin();
    $id=(int)($_GET['id']??0);
    $lines=[];
    try{
        $q=db()->prepare('SELECT i.*,c.first_name,c.last_name,c.email,c.company_name FROM invoices i LEFT JOIN customers c ON c.id=i.customer_id WHERE i.id=? LIMIT 1'); $q->execute([$id]); $inv=$q->fetch();
        if(!$inv) throw new Exception('Fatura bulunamadı.');
        $lines[]='Fatura No: '.$inv['invoice_number'];
        $lines[]='Müşteri: '.trim(($inv['first_name']??'').' '.($inv['last_name']??'')).' / '.($inv['email']??'');
        $lines[]='Durum: '.$inv['status'];
        $lines[]='Son Ödeme: '.($inv['due_date']??'-');
        $lines[]='Toplam: '.number_format((float)$inv['total'],2,',','.').' TL';
        $lines[]='';
        $it=db()->prepare('SELECT * FROM invoice_items WHERE invoice_id=?'); $it->execute([$id]);
        foreach($it->fetchAll() as $row) $lines[]='- '.$row['description'].' | '.number_format((float)$row['amount'],2,',','.').' TL';
        $disposition = (($_GET['mode'] ?? '') === 'view') ? 'inline' : 'attachment';
        header('Content-Type: application/pdf'); header('Content-Disposition: '.$disposition.'; filename="'.$inv['invoice_number'].'.pdf"');
        echo ao_build_simple_pdf('Ahost One Fatura', $lines); exit;
    }catch(Throwable $e){ flash('error','PDF oluşturulamadı: '.$e->getMessage()); redirect_to('admin/accounting/invoices'); }
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/accounting/shopier-save') {
    require_admin(); verify_csrf();
    ao_shopier_save_settings($_POST);
    flash('success','Shopier ayarları kaydedildi.');
    redirect_to('admin/accounting/payment-fees');
}

if ($route === 'payment/shopier/start') {
    $topupId=(int)($_GET['topup_id'] ?? 0);
    try{
        ao_schema_ensure_v990();
        $q=db()->prepare('SELECT t.*, c.first_name, c.last_name, c.email FROM credit_topups t LEFT JOIN customers c ON c.id=t.customer_id WHERE t.id=? LIMIT 1'); $q->execute([$topupId]); $t=$q->fetch();
        if(!$t) throw new Exception('Ödeme kaydı bulunamadı.');
        $isTest=(int)ao_shopier_setting('test_mode','0')===1;
        if($isTest){
            echo '<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Shopier Test Ödeme</title><style>body{font-family:Arial;background:#f8fafc;padding:40px}.card{max-width:520px;margin:auto;background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:24px;box-shadow:0 20px 60px #0001}.btn{display:inline-block;background:#2563eb;color:white;padding:12px 18px;border-radius:12px;text-decoration:none;font-weight:800}.muted{color:#64748b}</style></head><body><div class="card"><h2>Shopier Test Ödeme</h2><p class="muted">Shopier test modu açık olduğu için canlı tahsilat yerine güvenli test onayı gösteriliyor.</p><p><strong>Tutar:</strong> '.number_format((float)$t['total_amount'],2,',','.').' '.e($t['currency']).'</p><p><strong>Referans:</strong> '.e($t['reference']).'</p><a class="btn" href="'.e(url('payment/shopier/callback?topup_id='.$topupId.'&status=success&tx=SHOPIER-TEST-'.time())).'">Test Ödemeyi Başarılı Tamamla</a></div></body></html>'; exit;
        }
        // Canlı modda API bilgileri eksikse kullanıcıyı güvenli şekilde durdur.
        if(ao_shopier_setting('auth_mode','pat')==='pat'){ if(ao_shopier_setting('pat','')==='') throw new Exception('Shopier PAT eksik.'); } else { if(ao_shopier_setting('api_key')==='' || ao_shopier_setting('api_secret')==='') throw new Exception('Shopier API bilgileri eksik.'); }
        try{ db()->prepare('INSERT INTO payment_gateway_transactions(customer_id,topup_id,gateway,gateway_order_id,amount,fee_amount,currency,status,request_payload) VALUES(?,?,?,?,?,?,?,"pending",?)')->execute([(int)$t['customer_id'],$topupId,'shopier',$t['reference'],(float)$t['total_amount'],(float)$t['fee_amount'],$t['currency'],json_encode($t,JSON_UNESCAPED_UNICODE)]); }catch(Throwable $x){}
        flash('error','Shopier canlı ödeme başlatma için sağlayıcı form/imza bilgileri tamamlanmalı. Test modu kapalı fakat canlı adaptör yapılandırılmamış.');
        redirect_to(!empty($t['invoice_id']) ? 'client/invoices/view?id='.(int)$t['invoice_id'] : 'client/credit');
    }catch(Throwable $e){
        flash('error','Shopier ödeme başlatılamadı: '.$e->getMessage());
        $returnInvoiceId=0;
        if($topupId>0){ try{ $rq=db()->prepare('SELECT invoice_id FROM credit_topups WHERE id=? LIMIT 1'); $rq->execute([$topupId]); $returnInvoiceId=(int)$rq->fetchColumn(); }catch(Throwable $x){} }
        redirect_to($returnInvoiceId>0 ? 'client/invoices/view?id='.$returnInvoiceId : 'client/credit');
    }
}
if ($route === 'payment/shopier/callback') {
    $topupId=(int)($_GET['topup_id'] ?? $_POST['topup_id'] ?? $_POST['platform_order_id'] ?? $_GET['platform_order_id'] ?? 0);
    $reference=(string)($_GET['reference'] ?? $_POST['reference'] ?? $_GET['order_id'] ?? $_POST['order_id'] ?? $_GET['platform_order_id'] ?? $_POST['platform_order_id'] ?? '');
    $status=mb_strtolower((string)($_GET['status'] ?? $_POST['status'] ?? $_GET['payment_status'] ?? $_POST['payment_status'] ?? ''));
    $tx=(string)($_GET['tx'] ?? $_POST['tx'] ?? $_GET['transaction_id'] ?? $_POST['transaction_id'] ?? ('SHOPIER-'.time()));
    $returnInvoiceId=0;
    try{
        ao_schema_ensure_v990();
        if($topupId<=0 && $reference!==''){
            $q=db()->prepare('SELECT id FROM credit_topups WHERE reference=? ORDER BY id DESC LIMIT 1');
            $q->execute([$reference]); $topupId=(int)$q->fetchColumn();
        }
        if($topupId>0){ try{ $rq=db()->prepare('SELECT invoice_id FROM credit_topups WHERE id=? LIMIT 1'); $rq->execute([$topupId]); $returnInvoiceId=(int)$rq->fetchColumn(); }catch(Throwable $x){} }
        try{ db()->prepare('INSERT INTO module_shopier_callbacks(platform_order_id,status,amount,payload_json,processed_at) VALUES(?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE status=VALUES(status),payload_json=VALUES(payload_json),processed_at=NOW()')->execute([$reference ?: (string)$topupId, $status ?: 'received', (float)($_POST['amount'] ?? $_GET['amount'] ?? 0), json_encode(['get'=>$_GET,'post'=>$_POST],JSON_UNESCAPED_UNICODE)]); }catch(Throwable $x){}
        $okStatus = in_array($status, ['success','paid','approved','completed','1','ok'], true);
        if($okStatus){
            if($topupId<=0) throw new Exception('Ödeme kaydı eşleştirilemedi. Referans: '.($reference ?: '-'));
            ao_credit_topup_complete($topupId,$tx);
            try{ db()->prepare('UPDATE payment_gateway_transactions SET status="paid", gateway_transaction_id=?, callback_payload=? WHERE (topup_id=? OR gateway_order_id=?) AND gateway="shopier"')->execute([$tx,json_encode(['get'=>$_GET,'post'=>$_POST],JSON_UNESCAPED_UNICODE),$topupId,$reference]); }catch(Throwable $x){}
            flash('success',$returnInvoiceId>0 ? 'Shopier ödeme tamamlandı, fatura ödendi.' : 'Shopier ödeme tamamlandı, bakiye hesabınıza eklendi.');
        } else {
            flash('error','Shopier ödeme başarısız veya iptal edildi.');
            if($topupId>0) try{ db()->prepare('UPDATE credit_topups SET status="failed" WHERE id=? AND status<>"paid"')->execute([$topupId]); }catch(Throwable $x){}
        }
    }catch(Throwable $e){ flash('error','Shopier callback işlenemedi: '.$e->getMessage()); }
    redirect_to($returnInvoiceId>0 ? 'client/invoices/view?id='.$returnInvoiceId : 'client/credit');
}

