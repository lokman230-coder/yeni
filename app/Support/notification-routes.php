<?php
// Notification schema compatibility and notification admin routes.
try{
  $cols=[]; foreach(db()->query("SHOW COLUMNS FROM notification_channels")->fetchAll() as $c){$cols[$c['Field']]=true;}
  if(!empty($cols['channel'])) @db()->exec("ALTER TABLE notification_channels MODIFY channel VARCHAR(40) NULL");
  if(empty($cols['channel_type']) && !empty($cols['channel'])) @db()->exec("ALTER TABLE notification_channels ADD COLUMN channel_type VARCHAR(40) NULL AFTER id");
  if(empty($cols['name'])) @db()->exec("ALTER TABLE notification_channels ADD COLUMN name VARCHAR(190) NULL AFTER provider");
  if(empty($cols['status'])) @db()->exec("ALTER TABLE notification_channels ADD COLUMN status VARCHAR(40) DEFAULT 'inactive'");
  if(empty($cols['test_mode'])) @db()->exec("ALTER TABLE notification_channels ADD COLUMN test_mode TINYINT(1) DEFAULT 1");
  if(empty($cols['priority'])) @db()->exec("ALTER TABLE notification_channels ADD COLUMN priority INT DEFAULT 10");
  if(empty($cols['sender_name'])) @db()->exec("ALTER TABLE notification_channels ADD COLUMN sender_name VARCHAR(190) NULL");
  @db()->exec("UPDATE notification_channels SET channel_type=COALESCE(channel_type,channel), channel=COALESCE(channel,channel_type), status=CASE WHEN COALESCE(is_active,0)=1 THEN 'active' ELSE COALESCE(status,'inactive') END");
}catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
try{
  $cols=[]; foreach(db()->query("SHOW COLUMNS FROM notification_logs")->fetchAll() as $c){$cols[$c['Field']]=true;}
  if(!empty($cols['channel'])) @db()->exec("ALTER TABLE notification_logs MODIFY channel VARCHAR(40) NULL");
  if(empty($cols['channel_type']) && !empty($cols['channel'])) @db()->exec("ALTER TABLE notification_logs ADD COLUMN channel_type VARCHAR(40) NULL AFTER customer_id");
  if(empty($cols['provider'])) @db()->exec("ALTER TABLE notification_logs ADD COLUMN provider VARCHAR(120) NULL AFTER channel_type");
  if(empty($cols['response_code'])) @db()->exec("ALTER TABLE notification_logs ADD COLUMN response_code VARCHAR(20) NULL");
  if(empty($cols['response_body'])) @db()->exec("ALTER TABLE notification_logs ADD COLUMN response_body LONGTEXT NULL");
  if(empty($cols['event_key'])) @db()->exec("ALTER TABLE notification_logs ADD COLUMN event_key VARCHAR(120) NULL");
  if(empty($cols['payload_json'])) @db()->exec("ALTER TABLE notification_logs ADD COLUMN payload_json LONGTEXT NULL");
  if(empty($cols['sent_at'])) @db()->exec("ALTER TABLE notification_logs ADD COLUMN sent_at DATETIME NULL");
  @db()->exec("UPDATE notification_logs SET channel_type=COALESCE(channel_type,channel), channel=COALESCE(channel,channel_type), response_body=COALESCE(response_body,provider_response)");
}catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
try{
  $cols=[]; foreach(db()->query("SHOW COLUMNS FROM notification_templates")->fetchAll() as $c){$cols[$c['Field']]=true;}
  if(empty($cols['event_key']) && !empty($cols['template_key'])) @db()->exec("ALTER TABLE notification_templates ADD COLUMN event_key VARCHAR(120) NULL AFTER id");
  if(empty($cols['title'])) @db()->exec("ALTER TABLE notification_templates ADD COLUMN title VARCHAR(190) NULL");
  if(empty($cols['sms_body'])) @db()->exec("ALTER TABLE notification_templates ADD COLUMN sms_body TEXT NULL");
  if(empty($cols['whatsapp_body'])) @db()->exec("ALTER TABLE notification_templates ADD COLUMN whatsapp_body TEXT NULL");
  if(empty($cols['email_subject'])) @db()->exec("ALTER TABLE notification_templates ADD COLUMN email_subject VARCHAR(190) NULL");
  if(empty($cols['email_body'])) @db()->exec("ALTER TABLE notification_templates ADD COLUMN email_body TEXT NULL");
  @db()->exec("UPDATE notification_templates SET event_key=COALESCE(event_key,template_key), title=COALESCE(title,subject), email_subject=COALESCE(email_subject,subject), email_body=COALESCE(email_body,body)");
}catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }

// v26.2.3: SMS/WhatsApp/Mail gönderim fonksiyonları (ao_send_sms, ao_send_whatsapp,
// ao_send_email_notification, ao_notify_event ve yardımcıları) artık app/bootstrap.php
// içinde tanımlı. Önceden sadece burada (index.php) tanımlıydı, bu yüzden cron/
// altındaki komut satırı betikleri (bootstrap.php'yi yükleyip index.php'yi YÜKLEMEYEN)
// bu fonksiyonları hiç göremiyordu — örn. dunning modülünün e-posta/SMS gönderimi cron'da
// sessizce "fonksiyon yok" diyerek atlanıyordu. Taşıma sonrası davranış birebir aynı;
// sadece tanımlandığı dosya değişti.
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/notifications/save-channel') {

    verify_csrf();
    try{
        $id=(int)($_POST['id']??0); $type=trim($_POST['channel_type']??'sms'); $provider=trim($_POST['provider']??'generic'); $name=trim($_POST['name']??''); $status=trim($_POST['status']??'inactive'); $test=(int)($_POST['test_mode']??1); $priority=(int)($_POST['priority']??10); $sender=trim($_POST['sender_name']??'');
        $cfg=[]; foreach($_POST as $k=>$v) if(str_starts_with($k,'cfg_')) $cfg[substr($k,4)]=trim((string)$v);
        if(!$name) throw new Exception('Kanal adı zorunludur.'); $json=json_encode($cfg, JSON_UNESCAPED_UNICODE);
        if($id>0) db()->prepare('UPDATE notification_channels SET channel_type=?,provider=?,name=?,status=?,test_mode=?,priority=?,sender_name=?,config_json=? WHERE id=?')->execute([$type,$provider,$name,$status,$test,$priority,$sender,$json,$id]);
        else db()->prepare('INSERT INTO notification_channels(channel_type,provider,name,status,test_mode,priority,sender_name,config_json) VALUES(?,?,?,?,?,?,?,?)')->execute([$type,$provider,$name,$status,$test,$priority,$sender,$json]);
        flash('success','Bildirim kanalı kaydedildi.');
    }catch(Throwable $e){ flash('error','Bildirim kanalı kaydedilemedi: '.$e->getMessage()); }
    redirect_to('admin/notifications');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/notifications/test') {
    verify_csrf(); $type=trim($_POST['channel_type']??'sms'); $to=trim($_POST['recipient']??''); $msg=trim($_POST['message']??'Ahost One test mesajı'); $provider=trim($_POST['provider']??'') ?: null;
    $res=$type==='whatsapp' ? ao_send_whatsapp($to,$msg,'manual_test',$provider) : ($type==='email' ? ao_send_email_notification($to,'Ahost One Test',$msg,'manual_test') : ao_send_sms($to,$msg,'manual_test',$provider));
    flash($res['ok']?'success':'error',$res['message']); redirect_to('admin/notifications');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/notifications/save-template') {
    verify_csrf();
    $id=(int)($_POST['id']??0); $event=trim($_POST['event_key']??''); $title=trim($_POST['title']??'');
    if($id>0) db()->prepare('UPDATE notification_templates SET event_key=?,title=?,sms_body=?,whatsapp_body=?,email_subject=?,email_body=?,is_active=? WHERE id=?')->execute([$event,$title,$_POST['sms_body']??'',$_POST['whatsapp_body']??'',$_POST['email_subject']??'',$_POST['email_body']??'',(int)($_POST['is_active']??1),$id]);
    else db()->prepare('INSERT INTO notification_templates(event_key,title,sms_body,whatsapp_body,email_subject,email_body,is_active) VALUES(?,?,?,?,?,?,?)')->execute([$event,$title,$_POST['sms_body']??'',$_POST['whatsapp_body']??'',$_POST['email_subject']??'',$_POST['email_body']??'',(int)($_POST['is_active']??1)]);
    flash('success','Bildirim şablonu kaydedildi.'); redirect_to('admin/notifications');
}

