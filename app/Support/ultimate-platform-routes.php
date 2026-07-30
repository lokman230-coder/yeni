<?php
// v20.0.0 Ultimate Platform - v19 Enterprise Core + v20 SaaS/AI additions
function ao_v20_ensure_schema(){
    static $done=false; if($done) return; $done=true;
    $file=__DIR__.'/database/migrations/v20_0_0_ultimate_platform.sql';
    if(is_file($file)){
        try{ db()->exec(file_get_contents($file)); }catch(Throwable $e){ try{ error_log('Ahost v20 schema: '.$e->getMessage()); }catch(Throwable $x){} }
    }
}
ao_v20_ensure_schema();


if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/menu-manager/save') {
    require_admin(); verify_csrf();
    $type = in_array(($_POST['type'] ?? 'admin'), ['admin','site','mobile','footer','topbar','corporate'], true) ? $_POST['type'] : 'admin';
    $json = $_POST['items_json'] ?? '[]';
    $items = json_decode($json, true);
    if (!is_array($items)) $items = [];
    if (function_exists('ao_save_menu_v222')) {
        ao_save_menu_v222($type, $items);
        // Backward compatibility for old screens.
        save_admin_pref('menu_builder_'.$type, json_encode(ao_normalize_menu_items_v222($items), JSON_UNESCAPED_UNICODE));
    } else {
        $clean=[]; foreach($items as $it){ $label=trim((string)($it['label']??'')); $url=trim((string)($it['url']??'')); if($label!=='') $clean[]=['label'=>$label,'url'=>$url]; }
        save_admin_pref('menu_builder_'.$type, json_encode($clean, JSON_UNESCAPED_UNICODE));
    }
    if ($type === 'topbar') {
        $previousLanguages = (string)admin_setting('enabled_languages', 'tr,en');
        foreach(['topbar_currency_enabled','language_menu_enabled','topbar_cart_enabled','topbar_login_enabled','topbar_social_enabled','topbar_phone_enabled'] as $k) {
            save_setting($k, isset($_POST[$k]) ? '1' : '0');
        }
        foreach(['company_phone','enabled_languages','usd_try_rate','currency_margin_percent','topbar_login_text','topbar_login_panel_title','topbar_login_submit_text','topbar_follow_text'] as $k) {
            if (isset($_POST[$k])) save_setting($k, trim((string)$_POST[$k]));
        }
        if (isset($_POST['enabled_languages'])) ao_generate_new_language_packs_v2700($previousLanguages, (string)$_POST['enabled_languages']);
    }
    flash('success', ucfirst($type).' menüsü kaydedildi ve ön yüz menü cache sorunu giderildi.');
    redirect_to('admin/menu-manager?type='.$type);
}

