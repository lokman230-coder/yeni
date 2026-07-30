<?php
// v9.6.0 Production Test & Cleanup
function ao_v960_count($table, $where='1=1') { try { return (int)db()->query("SELECT COUNT(*) FROM $table WHERE $where")->fetchColumn(); } catch(Throwable $e) { return -1; } }
function ao_v960_production_summary() {
    $items = [];
    $add = function($name,$ok,$detail,$recommendation='') use (&$items){ $items[]=['name'=>$name,'ok'=>$ok,'detail'=>$detail,'recommendation'=>$recommendation]; };
    $add('Kurulum dosyaları', file_exists(dirname(__DIR__, 2).'/app/install.php') && file_exists(dirname(__DIR__, 2).'/database/fresh-install.sql'), 'app/install.php ve database/fresh-install.sql kontrol edildi.', 'Eksikse kurulum paketi tamamlanmalı.');
    $add('Tema sistemi', ao_v960_count('themes') > 0, ao_v960_count('themes').' tema kaydı bulundu.', 'Tema seçimi site/admin/customer alanlarında ayrı çalışmalı.');
    $add('Marketplace kategorileri', ao_v960_count('marketplace_categories') >= 5, ao_v960_count('marketplace_categories').' kategori bulundu.', 'Domain dışı hizmetler için kategori ekleyin.');
    $add('Öne çıkarma paketleri', ao_v960_count('marketplace_feature_packages') === 4, ao_v960_count('marketplace_feature_packages').' paket bulundu.', 'Paketler 7/15/30/60 gün olarak tekil olmalı.');
    $add('Admin arama indeksi', ao_v960_count('admin_search_index') >= 5, ao_v960_count('admin_search_index').' arama kaydı bulundu.', 'Kredi kartı ayarları gibi eş anlamlı aramalar desteklenmeli.');
    $add('Kart komisyon motoru', ao_v960_count('payment_fee_rules') >= 1, ao_v960_count('payment_fee_rules').' gateway komisyon kaydı bulundu.', 'Kart İşlem Komisyonu faturaya ayrı satır olarak eklenmeli.');
    $add('Bildirim şablonları', ao_v960_count('notification_templates') >= 3, ao_v960_count('notification_templates').' şablon bulundu.', 'EPP, fatura, ticket, hosting olayları için SMS/WhatsApp şablonları tamamlanmalı.');
    $add('Demo içerik', ao_v960_count('api_logs', "status='error'") < 10, ao_v960_count('api_logs', "status='error'").' hata logu bulundu.', 'Canlıya geçmeden eski hata/demo logları temizlenmeli.');
    return $items;
}
