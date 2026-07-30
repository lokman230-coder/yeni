<?php
// v9.3.0 Theme / Marketplace / Domain Intelligence routes
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/theme-center/delete') {
    require_admin(); verify_csrf(); ao_schema_ensure_v930();
    $id = (int)($_POST['theme_id'] ?? 0);
    try {
        $q = db()->prepare('SELECT * FROM themes WHERE id=? LIMIT 1');
        $q->execute([$id]);
        $theme = $q->fetch();
        if (!$theme) throw new Exception('Tema kaydı bulunamadı.');
        $slug = ao_theme_safe_slug($theme['slug'] ?? '');
        if ($slug === '') throw new Exception('Tema slug bilgisi geçersiz.');
        $active = db()->prepare('SELECT COUNT(*) FROM themes WHERE slug=? AND is_active=1');
        $active->execute([$slug]);
        if ((int)$active->fetchColumn() > 0) {
            throw new Exception('Aktif tema silinemez. Önce başka bir temayı aktif edin.');
        }
        $themeRoots = [__DIR__ . '/public/themes', __DIR__ . '/themes'];
        foreach ($themeRoots as $themesRoot) {
            foreach (ao_theme_slug_candidates($slug) as $candidateSlug) {
                $deleteDirs = [
                    $themesRoot.'/'.$candidateSlug,
                    $themesRoot.'/site/'.$candidateSlug,
                    $themesRoot.'/admin/'.$candidateSlug,
                    $themesRoot.'/client/'.$candidateSlug,
                    $themesRoot.'/customer/'.$candidateSlug,
                ];
                foreach (array_unique($deleteDirs) as $dir) {
                    ao_theme_delete_directory_safe($dir, $themesRoot);
                }
            }
        }
        $del = db()->prepare('DELETE FROM themes WHERE slug=?');
        $del->execute([$slug]);
        if (function_exists('ao_clear_theme_cache')) ao_clear_theme_cache();
        flash('success','Tema dosyalardan ve veritabanından silindi: '.$slug);
    } catch(Throwable $e) {
        flash('error','Tema silinemedi: '.$e->getMessage());
    }
    redirect_to('admin/theme-center/themes');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/theme-center/apply') {
    require_admin(); verify_csrf(); ao_schema_ensure_v930();
    $id=(int)($_POST['theme_id']??0); $area=trim($_POST['area']??'site') ?: 'site'; $scope=trim((string)($_POST['apply_scope'] ?? 'area'));
    try{
        $q=db()->prepare('SELECT * FROM themes WHERE id=? LIMIT 1'); $q->execute([$id]); $theme=$q->fetch();
        if(!$theme) throw new Exception('Tema kaydı bulunamadı.');
        if($scope === 'all'){
            $slug=(string)$theme['slug'];
            foreach(['site','admin','client'] as $targetArea){
                $find=db()->prepare('SELECT id FROM themes WHERE slug=? AND area=? LIMIT 1'); $find->execute([$slug,$targetArea]); $targetId=(int)$find->fetchColumn();
                if($targetId>0){
                    db()->prepare('UPDATE themes SET is_active=0 WHERE area=?')->execute([$targetArea]);
                    db()->prepare('UPDATE themes SET is_active=1 WHERE id=? AND area=?')->execute([$targetId,$targetArea]);
                }
            }
            ao_clear_theme_cache(); flash('success','Tema site, admin paneli ve müşteri alanı için birlikte aktif edildi.');
        } else {
            $area = ($area === 'customer') ? 'client' : $area;
            db()->prepare('UPDATE themes SET is_active=0 WHERE area=?')->execute([$area]);
            db()->prepare('UPDATE themes SET is_active=1 WHERE id=? AND area=?')->execute([$id,$area]);
            ao_clear_theme_cache(); flash('success','Tema aktif edildi ve ilgili panelde uygulanacak.');
        }
    }catch(Throwable $e){ flash('error','Tema aktif edilemedi: '.$e->getMessage()); }
    redirect_to('admin/theme-center/themes');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/theme-center/reset-style') {
    require_admin(); verify_csrf(); ao_schema_ensure_v930();
    try {
        ao_schema_ensure_v188();
        $id = (int)($_POST['theme_id'] ?? 0);
        if ($id <= 0) throw new Exception('Tema bulunamadı.');
        $defaults = [
            'primary_color'=>'#2563eb',
            'secondary_color'=>'#0f172a',
            'font_family'=>'Inter, Arial, sans-serif',
            'radius'=>'24px',
            'button_radius'=>'16px',
            'button_style'=>'gradient',
            'background_color'=>'#f8fbff',
            'background_gradient'=>'linear-gradient(135deg,#fff,#eef4ff)',
            'header_mode'=>'sticky',
            'mobile_bottom_nav'=>1,
            'layout_width'=>'boxed',
            'container_width'=>'1240px',
            'section_spacing'=>'34px',
            'header_background'=>'#ffffff',
            'header_text_color'=>'#0f172a',
            'header_radius'=>'0px',
            'header_shadow'=>'soft',
            'header_blur'=>1,
            'topbar_enabled'=>1,
            'footer_background'=>'#0f172a',
            'footer_text_color'=>'#e5eef8',
            'footer_radius'=>'0px',
            'card_shadow'=>'soft',
            'content_density'=>'comfortable',
            'currency_display_mode'=>'symbol',
            'language_display_mode'=>'flag_code',
            'language_switch_mode'=>'refresh',
        ];
        $q = db()->prepare('UPDATE themes SET primary_color=?, secondary_color=?, font_family=?, radius=?, button_radius=?, button_style=?, background_color=?, background_gradient=?, header_mode=?, mobile_bottom_nav=?, layout_width=?, container_width=?, section_spacing=?, header_background=?, header_text_color=?, header_radius=?, header_shadow=?, header_blur=?, topbar_enabled=?, footer_background=?, footer_text_color=?, footer_radius=?, card_shadow=?, content_density=?, currency_display_mode=?, language_display_mode=?, language_switch_mode=? WHERE id=?');
        $q->execute([$defaults['primary_color'],$defaults['secondary_color'],$defaults['font_family'],$defaults['radius'],$defaults['button_radius'],$defaults['button_style'],$defaults['background_color'],$defaults['background_gradient'],$defaults['header_mode'],$defaults['mobile_bottom_nav'],$defaults['layout_width'],$defaults['container_width'],$defaults['section_spacing'],$defaults['header_background'],$defaults['header_text_color'],$defaults['header_radius'],$defaults['header_shadow'],$defaults['header_blur'],$defaults['topbar_enabled'],$defaults['footer_background'],$defaults['footer_text_color'],$defaults['footer_radius'],$defaults['card_shadow'],$defaults['content_density'],$defaults['currency_display_mode'],$defaults['language_display_mode'],$defaults['language_switch_mode'],$id]);
        if(function_exists('ao_clear_theme_cache')) ao_clear_theme_cache();
        flash('success','Tema görünüm ayarları varsayılana döndürüldü.');
    } catch(Throwable $e) { flash('error','Tema varsayılana döndürülemedi: '.$e->getMessage()); }
    redirect_to('admin/theme-center/editor?id='.(int)($_POST['theme_id'] ?? 0));
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/theme-center/save-style') {
    require_admin(); verify_csrf(); ao_schema_ensure_v930();
    try{ ao_schema_ensure_v188(); $id=(int)($_POST['theme_id']??0); db()->prepare('UPDATE themes SET primary_color=?, secondary_color=?, font_family=?, radius=?, button_radius=?, button_style=?, background_color=?, background_gradient=?, header_mode=?, mobile_bottom_nav=?, layout_width=?, container_width=?, section_spacing=?, header_background=?, header_text_color=?, header_radius=?, header_shadow=?, header_blur=?, topbar_enabled=?, footer_background=?, footer_text_color=?, footer_radius=?, card_shadow=?, content_density=?, currency_display_mode=?, language_display_mode=?, language_switch_mode=? WHERE id=?')->execute([trim($_POST['primary_color']??'#2563eb'),trim($_POST['secondary_color']??'#0f172a'),trim($_POST['font_family']??'Inter, Arial, sans-serif'),trim($_POST['radius']??'24px'),trim($_POST['button_radius']??'16px'),trim($_POST['button_style']??'gradient'),trim($_POST['background_color']??'#f8fbff'),trim($_POST['background_gradient']??''),trim($_POST['header_mode']??'sticky'),!empty($_POST['mobile_bottom_nav'])?1:0,trim($_POST['layout_width']??'boxed'),trim($_POST['container_width']??'1240px'),trim($_POST['section_spacing']??'34px'),trim($_POST['header_background']??'#ffffff'),trim($_POST['header_text_color']??'#0f172a'),trim($_POST['header_radius']??'0px'),trim($_POST['header_shadow']??'soft'),!empty($_POST['header_blur'])?1:0,!empty($_POST['topbar_enabled'])?1:0,trim($_POST['footer_background']??'#0f172a'),trim($_POST['footer_text_color']??'#e5eef8'),trim($_POST['footer_radius']??'0px'),trim($_POST['card_shadow']??'soft'),trim($_POST['content_density']??'comfortable'),trim($_POST['currency_display_mode']??'symbol'),trim($_POST['language_display_mode']??'flag_code'),trim($_POST['language_switch_mode']??'refresh'),$id]); ao_clear_theme_cache(); flash('success','Tema stili kaydedildi ve cache temizlendi.'); }catch(Throwable $e){ flash('error','Tema kaydedilemedi: '.$e->getMessage()); }
    redirect_to('admin/theme-center/themes');
}

if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/theme-center/quick-actions/save') {
    require_admin(); verify_csrf();
    $rows=[];
    $labels=$_POST['label'] ?? [];
    $titles=$_POST['title'] ?? [];
    $hrefs=$_POST['href'] ?? [];
    $classes=$_POST['class'] ?? [];
    $sorts=$_POST['sort_order'] ?? [];
    $enabled=$_POST['enabled'] ?? [];
    $delete=$_POST['delete'] ?? [];
    $count=max(count((array)$labels), count((array)$titles), count((array)$hrefs));
    for($i=0; $i<$count; $i++){
        if(!empty($delete[$i])) continue;
        $label=trim((string)($labels[$i] ?? ''));
        $title=trim((string)($titles[$i] ?? ''));
        $href=trim((string)($hrefs[$i] ?? ''));
        $class=preg_replace('/[^a-z0-9_-]+/i','', (string)($classes[$i] ?? 'custom')) ?: 'custom';
        if($label==='' && $title==='' && $href==='') continue;
        if($label==='' || $title==='' || $href==='') continue;
        $rows[]=[
            'label'=>mb_substr($label,0,32,'UTF-8'),
            'title'=>mb_substr($title,0,80,'UTF-8'),
            'href'=>mb_substr($href,0,255,'UTF-8'),
            'class'=>mb_substr($class,0,32,'UTF-8'),
            'enabled'=>isset($enabled[$i]) ? 1 : 0,
            'sort_order'=>(int)($sorts[$i] ?? 100),
        ];
    }
    usort($rows, fn($a,$b)=>($a['sort_order'] <=> $b['sort_order']) ?: strcmp($a['label'],$b['label']));
    save_setting('abp_quick_actions_json', json_encode(array_values($rows), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    flash('success','Hızlı erişim butonları kaydedildi.');
    redirect_to('admin/theme-center/quick-actions');
}

if ($route==='admin/theme-center/quick-actions') {
    require_admin();
    view('theme-center/quick-actions', ['pageTitle'=>'Hızlı Erişim Butonları']);
    exit;
}

// Ahost One v25.0.23 Prism Theme Builder - renk önizleme/kaydetme
if (!function_exists('ao_prism_builder_defaults_v2636')) {
    function ao_prism_builder_defaults_v2636(): array {
        return [
            'prism_primary_color'=>'#ff675d',
            'prism_secondary_color'=>'#082f49',
            'prism_accent_color'=>'#ff8a5c',
            'prism_surface_color'=>'#fff8ef',
            'prism_heading_color'=>'#082f49',
            'prism_text_color'=>'#18243a',
            'prism_muted_color'=>'#64748b',
            'prism_button_background_color'=>'#ff675d',
            'prism_button_text_color'=>'#ffffff',
            'prism_card_border_color'=>'#f0d8ca',
            'prism_font_family'=>'Inter, Arial, sans-serif',
            'prism_heading_weight'=>'720',
            'prism_body_weight'=>'420',
            'prism_button_weight'=>'650',
            'prism_site_background_color'=>'#fff8ef',
            'prism_header_background_color'=>'#ffffff',
            'prism_hero_background_color'=>'#fff8ef',
            'prism_domain_background_color'=>'#ffffff',
            'prism_card_background_color'=>'#ffffff',
            'prism_footer_background_color'=>'#102033',
            'prism_button_radius'=>'16px',
            'prism_card_radius'=>'26px',
            'prism_site_background_image'=>'',
            'prism_header_background_image'=>'',
            'prism_hero_background_image'=>'',
            'prism_domain_background_image'=>'',
            'prism_card_background_image'=>'',
            'prism_footer_background_image'=>'',
        ];
    }
}
if ($route === 'admin/theme-center/prism-builder') {
    require_admin();
    view('theme-center/prism-builder', ['pageTitle'=>'Prism Tema Builder']);
    exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/theme-center/prism-builder/save') {
    require_admin(); verify_csrf();
    $keys = ao_prism_builder_defaults_v2636();
    foreach($keys as $key=>$default){
        $val = trim((string)($_POST[$key] ?? $default));
        if(str_contains($key, 'color') && !preg_match('/^#[0-9a-fA-F]{6}$/', $val)) $val = $default;
        if(str_contains($key, 'radius') && !preg_match('/^[0-9]{1,2}px$/', $val)) $val = $default;
        if(str_contains($key, 'weight') && !in_array($val, ['300','400','420','500','560','600','650','700','720','760','800'], true)) $val = $default;
        if($key === 'prism_font_family') {
            $allowedFonts = [
                'Inter, Arial, sans-serif',
                'Arial, Helvetica, sans-serif',
                'Plus Jakarta Sans, Inter, Arial, sans-serif',
                'Manrope, Inter, Arial, sans-serif',
                'Roboto, Arial, sans-serif',
                'Poppins, Inter, Arial, sans-serif',
            ];
            if (!in_array($val, $allowedFonts, true)) $val = $default;
        }
        if(str_contains($key, 'image')) {
            $val = mb_substr(str_replace(["\r","\n"], '', $val), 0, 500, 'UTF-8');
        }
        save_setting($key, $val);
    }
    if(function_exists('ao_clear_theme_cache')) ao_clear_theme_cache();
    flash('success','Prism renk ayarları kaydedildi.');
    redirect_to('admin/theme-center/prism-builder');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/theme-center/prism-builder/reset') {
    require_admin(); verify_csrf();
    foreach(ao_prism_builder_defaults_v2636() as $key=>$default) {
        save_setting($key, $default);
    }
    if(function_exists('ao_clear_theme_cache')) ao_clear_theme_cache();
    flash('success','Prism tema ayarları varsayılana döndürüldü.');
    redirect_to('admin/theme-center/prism-builder');
}

if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/marketplace/listing-save') {
    require_admin(); verify_csrf(); ao_schema_ensure_v930();
    try{
        $id=(int)($_POST['id']??0); $title=trim($_POST['title']??''); if(!$title) throw new Exception('Başlık zorunlu.');
        $listingType=trim($_POST['listing_type']??'domain'); $domainField=$listingType==='domain'?ahost_domain_clean($_POST['domain_name']??''):trim($_POST['domain_name']??'');
        $data=[trim($_POST['seller_type']??'admin'),$listingType,$title,$domainField,trim($_POST['description']??''),trim($_POST['category']??''),(float)($_POST['price']??0),trim($_POST['currency']??'TRY'),trim($_POST['status']??'active'),!empty($_POST['is_featured'])?1:0,!empty($_POST['is_premium'])?1:0,!empty($_POST['is_urgent'])?1:0];
        $featuredUntil=null; if(!empty($_POST['featured_days'])) $featuredUntil=date('Y-m-d H:i:s', time()+((int)$_POST['featured_days']*86400));
        if($id>0){ db()->prepare('UPDATE marketplace_listings SET seller_type=?,listing_type=?,title=?,domain_name=?,description=?,category=?,price=?,currency=?,status=?,is_featured=?,is_premium=?,is_urgent=?,sale_model=?,commission_percent=?,delivery_days=?,featured_until=? WHERE id=?')->execute([...$data,$featuredUntil,$id]); }
        else{ db()->prepare('INSERT INTO marketplace_listings(seller_type,listing_type,title,domain_name,description,category,price,currency,status,is_featured,is_premium,is_urgent,sale_model,commission_percent,delivery_days,featured_until) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([...$data,$featuredUntil]); }
        flash('success','Marketplace ilanı kaydedildi.');
    }catch(Throwable $e){ flash('error','İlan kaydedilemedi: '.$e->getMessage()); }
    redirect_to('admin/marketplace');
}
if ($route==='admin/marketplace/delete') { require_admin(); verify_csrf(); ao_schema_ensure_v930(); $id=(int)($_GET['id']??0); try{ db()->prepare('DELETE FROM marketplace_listings WHERE id=?')->execute([$id]); flash('success','İlan silindi.'); }catch(Throwable $e){ flash('error','İlan silinemedi: '.$e->getMessage()); } redirect_to('admin/marketplace'); }
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/domain-intelligence/run') {
    require_admin(); verify_csrf(); try{ $report=ao_domain_intelligence_run($_POST['domain']??''); $_SESSION['ao_last_domain_intelligence']=$report; flash('success','Domain analizi tamamlandı: '.$report['summary']); }catch(Throwable $e){ flash('error','Analiz yapılamadı: '.$e->getMessage()); } redirect_to('admin/domain-intelligence');
}
