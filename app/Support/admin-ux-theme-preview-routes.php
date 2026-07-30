<?php
// v9.4.0 Admin UX + Theme Preview + İletiMerkezi Pro
function ao_schema_ensure_v940() {
    static $done=false; if($done) return; $done=true;
    ao_schema_ensure_v930();
    try { db()->exec("ALTER TABLE themes ADD COLUMN preview_url varchar(255) DEFAULT NULL AFTER preview_image"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("ALTER TABLE themes ADD COLUMN custom_css longtext DEFAULT NULL AFTER font_family"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS sms_balance_checks (id int(11) NOT NULL AUTO_INCREMENT, provider varchar(80) NOT NULL, balance_text varchar(190) DEFAULT NULL, raw_response longtext DEFAULT NULL, status varchar(40) DEFAULT 'unknown', created_at timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY(id), KEY provider(provider)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS theme_apply_logs (id int(11) NOT NULL AUTO_INCREMENT, theme_id int(11) DEFAULT NULL, area varchar(40) DEFAULT 'site', admin_id int(11) DEFAULT NULL, action varchar(80) DEFAULT 'apply', created_at timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY(id), KEY area(area)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    $adminThemes=[
      ['ahost-prism','Ahost One Prism','admin','#ff675d','#19b7a2']
    ];
    foreach($adminThemes as $t){ try{ db()->prepare("INSERT IGNORE INTO themes(slug,name,area,description,primary_color,secondary_color,is_active,status) VALUES(?,?,?,?,?,?,1, 'installed')")->execute([$t[0],$t[1],$t[2],$t[1].' admin panel teması.',$t[3],$t[4]]); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
    $clientThemes=[
      ['ahost-prism','Ahost One Prism','client','#ff675d','#19b7a2']
    ];
    foreach($clientThemes as $t){ try{ db()->prepare("INSERT IGNORE INTO themes(slug,name,area,description,primary_color,secondary_color,is_active,status) VALUES(?,?,?,?,?,?,1, 'installed')")->execute([$t[0],$t[1],$t[2],$t[1].' müşteri paneli teması.',$t[3],$t[4]]); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
    // İletiMerkezi Registrar modülünden alınan temel hazır şablonlar.
    $tpls=[
      ['domain_epp_code','Domain EPP Kodu','Sayın {customer_name}, {domain} transfer kodunuz: {epp_code}.'],
      ['hosting_created','Hosting Aktif','Sayın {customer_name}, {domain} hosting hizmetiniz aktif. Kullanıcı: {username} Şifre: {password}'],
      ['domain_renewal_notice','Domain Yenileme Hatırlatma','Sayın {customer_name}, {domain} alan adınız {expiry_date} tarihinde sona erecektir.'],
      ['domain_registered','Domain Kayıt Başarılı','Sayın {customer_name}, {domain} alan adınız başarıyla kayıt edildi.'],
      ['domain_renewed','Domain Yenileme Başarılı','Sayın {customer_name}, {domain} alan adınız başarıyla yenilendi.'],
      ['invoice_created','Fatura Oluşturuldu','Sayın {customer_name}, {invoice_number} numaralı faturanız oluşturuldu. Tutar: {total} TL'],
      ['ticket_opened','Ticket Açıldı','Sayın {customer_name}, {ticket_subject} destek talebiniz oluşturuldu.'],
      ['ticket_replied','Ticket Cevaplandı','Sayın {customer_name}, {ticket_subject} destek talebiniz cevaplandı.']
    ];
    foreach($tpls as $t){ try{ db()->prepare("INSERT IGNORE INTO notification_templates(event_key,title,sms_body,whatsapp_body,email_subject,email_body,is_active) VALUES(?,?,?,?,?,?,1)")->execute([$t[0],$t[1],$t[2],$t[2],$t[1],$t[2]]); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }    try{ save_setting('ahost_version','25.0.0-rc25'); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
ao_schema_ensure_v940();
