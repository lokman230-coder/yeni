<?php
// v14.0.0 Site Builder Pro Foundation
function ao_schema_ensure_v1400() { static $done=false; if($done) return; $done=true;
    try {
        db()->exec("CREATE TABLE IF NOT EXISTS sitebuilder_projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NULL,
            name VARCHAR(160) NOT NULL,
            type VARCHAR(40) DEFAULT 'site',
            theme_slug VARCHAR(80) DEFAULT 'default',
            status VARCHAR(30) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        db()->exec("CREATE TABLE IF NOT EXISTS sitebuilder_pages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            title VARCHAR(180) NOT NULL,
            slug VARCHAR(180) NOT NULL,
            page_type VARCHAR(40) DEFAULT 'page',
            builder_json LONGTEXT NULL,
            html_cache LONGTEXT NULL,
            status VARCHAR(30) DEFAULT 'draft',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX(project_id), UNIQUE KEY uq_project_slug(project_id, slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        db()->exec("CREATE TABLE IF NOT EXISTS sitebuilder_revisions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            page_id INT NOT NULL,
            builder_json LONGTEXT NULL,
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(page_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        db()->exec("CREATE TABLE IF NOT EXISTS sitebuilder_exports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            status VARCHAR(30) DEFAULT 'ready',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(project_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        db()->exec("CREATE TABLE IF NOT EXISTS sitebuilder_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(160) NOT NULL,
            category VARCHAR(80) DEFAULT 'general',
            builder_json LONGTEXT NULL,
            is_active TINYINT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $exists=(int)db()->query('SELECT COUNT(*) FROM sitebuilder_projects')->fetchColumn();
        if(!$exists){
            db()->exec("INSERT INTO sitebuilder_projects(name,type,theme_slug,status) VALUES ('Ahost Demo Site','site','default','active')");
            $pid=(int)db()->lastInsertId();
            $json=json_encode([['id'=>'hero','type'=>'hero','cols'=>1,'content'=>[
              ['id'=>'h1','type'=>'heading','text'=>'Ahost One ile dijital işinizi büyütün','props'=>[]],
              ['id'=>'p1','type'=>'text','text'=>'Domain, hosting, marketplace ve site builder çözümleri tek platformda.','props'=>[]],
              ['id'=>'b1','type'=>'button','text'=>'Hemen Başla','props'=>[]]
            ]]], JSON_UNESCAPED_UNICODE);
            $st=db()->prepare('INSERT INTO sitebuilder_pages(project_id,title,slug,page_type,builder_json,status) VALUES(?,?,?,?,?,?)');
            $st->execute([$pid,'Ana Sayfa','index','home',$json,'published']);
        }
        try{ db()->prepare("INSERT INTO settings(setting_key,setting_value) VALUES('ahost_version','25.0.0-rc25') ON DUPLICATE KEY UPDATE setting_value='25.0.0-rc25'")->execute(); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        try{ db()->prepare("INSERT INTO admin_search_index(title,route,category,keywords,is_active) VALUES('Site Builder Pro','admin/site-builder','Builder','sitebuilder site builder sayfa oluştur zip export elementor sürükle bırak',1) ON DUPLICATE KEY UPDATE keywords=VALUES(keywords),is_active=1")->execute(); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_sitebuilder_default_page_id(){ ao_schema_ensure_v1400(); try{return (int)db()->query('SELECT id FROM sitebuilder_pages ORDER BY id ASC LIMIT 1')->fetchColumn();}catch(Throwable $e){return 0;} }
function ao_sitebuilder_style_attr($style){
    if(!is_array($style)) return '';
    $allowed=['fontSize','color','background','padding','margin','width','height','minHeight','maxWidth','textAlign','borderRadius','alignSelf'];
    $map=['fontSize'=>'font-size','minHeight'=>'min-height','maxWidth'=>'max-width','textAlign'=>'text-align','borderRadius'=>'border-radius','alignSelf'=>'align-self'];
    $out=[];
    foreach($allowed as $key){
        if(!isset($style[$key]) || $style[$key]==='') continue;
        $prop=$map[$key]??$key;
        $value=str_replace([';','{','}'], '', (string)$style[$key]);
        $out[]=$prop.':'.$value;
    }
    return $out ? ' style="'.htmlspecialchars(implode(';',$out),ENT_QUOTES,'UTF-8').'"' : '';
}
function ao_sitebuilder_public_player_script(){
    static $done=false; if($done) return ''; $done=true;
    return '<script>(function(){document.addEventListener("click",async function(e){var btn=e.target.closest("[data-sbx-radio-play]");if(!btn)return;var box=btn.closest(".sbx-radio-player");var audio=box&&box.querySelector("audio");var status=box&&box.querySelector("[data-sbx-radio-status]");var url=(btn.getAttribute("data-stream-url")||"").trim();if(!audio||!url){if(status)status.textContent="Stream URL girilmedi.";return;}if(location.protocol==="https:"&&/^http:\/\//i.test(url)){if(status)status.textContent="HTTPS sayfada HTTP stream engellenebilir.";return;}try{if(!audio.paused){audio.pause();btn.textContent="Dinle";if(status)status.textContent="Yayın duraklatıldı.";return;}if(audio.src!==url){audio.src=url;audio.load();}if(status)status.textContent="Yayın bağlanıyor...";await audio.play();btn.textContent="Duraklat";if(status)status.textContent="Yayın çalıyor";}catch(err){btn.textContent="Dinle";if(status)status.textContent="Yayın çalınamadı. URL, codec veya CORS kontrol edin.";}});})();</script>';
}
function ao_sitebuilder_render_element($el){
    $type=(string)($el['type']??'text');
    if($type==='price') $type='price_card';
    $content=is_array($el['content']??null)?$el['content']:[];
    $style=is_array($el['style']??null)?$el['style']:[];
    $props=is_array($el['props']??null)?$el['props']:[];
    $text=(string)($el['text']??($content['text']??''));
    $title=(string)($el['title']??($content['title']??$text));
    $desc=(string)($el['desc']??($content['desc']??$text));
    $button=(string)($el['button']??($content['button']??($content['text']??'Buton')));
    $styleAttr=ao_sitebuilder_style_attr($style);
    $e=function($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');};
    if($type==='heading'){ $level=preg_match('/^h[1-4]$/',(string)($content['level']??''))?$content['level']:'h2'; return '<'.$level.$styleAttr.'>'.$e($text ?: $title ?: 'Başlık').'</'.$level.'>'; }
    if($type==='text') return '<p'.$styleAttr.'>'.$e($text ?: 'Metin alanı').'</p>';
    if($type==='button'){ $url=(string)($content['url']??($props['actionUrl']??'#')); return '<a class="sbx-btn" href="'.$e($url).'"'.$styleAttr.'>'.$e($button ?: 'Buton').'</a>'; }
    if($type==='image'){ $src=(string)($content['src']??$text); return '<img class="sbx-image" src="'.$e($src ?: 'https://placehold.co/900x520?text=Ahost+Builder').'" alt=""'.$styleAttr.'>'; }
    if($type==='feature') return '<article class="sbx-feature"'.$styleAttr.'><span>'.$e($content['icon']??'★').'</span><h3>'.$e($title ?: 'Özellik').'</h3><p>'.$e($desc ?: 'Özellik açıklaması').'</p></article>';
    if($type==='price_card') return '<article class="sbx-price'.(!empty($content['popular'])?' popular':'').'"'.$styleAttr.'><h3>'.$e($title ?: 'Plan').'</h3><strong>'.$e($content['price']??($el['price']??'₺499')).'</strong><small>'.$e($content['period']??'/ay').'</small><p>'.$e($desc ?: 'Paket açıklaması').'</p><a class="sbx-btn" href="#">Seç</a></article>';
    if($type==='divider') return '<hr class="sbx-divider"'.$styleAttr.'>';
    if($type==='spacer') return '<div class="sbx-spacer"'.$styleAttr.'></div>';
    if($type==='radio_player'){
        $url=(string)($content['streamUrl']??$content['stream_url']??$content['url']??$props['streamUrl']??'');
        $station=(string)($content['station']??$title??'Canlı Radyo');
        return '<article class="sbx-radio-player"'.$styleAttr.'><div class="sbx-radio-disc"><span></span></div><div><small>CANLI YAYIN</small><h3>'.$e($station).'</h3><p>'.$e($desc ?: 'Stream URL girildiğinde play tuşu yayını dener.').'</p><button type="button" class="sbx-btn" data-sbx-radio-play data-stream-url="'.$e($url).'">Dinle</button><em data-sbx-radio-status>'.($url!==''?'Yayın hazır':'Stream URL bekleniyor').'</em><audio preload="none"></audio></div></article>'.ao_sitebuilder_public_player_script();
    }
    if($type==='now_playing') return '<article class="sbx-now-playing"'.$styleAttr.'><small>ŞU AN ÇALIYOR</small><h3>'.$e($title ?: 'Canlı Yayın').'</h3><p>'.$e($desc ?: 'Program ve parça bilgisi burada gösterilir.').'</p></article>';
    if($type==='song_request') return '<form class="sbx-form sbx-request"'.$styleAttr.'><h3>'.$e($title ?: 'Şarkı İsteği').'</h3><input placeholder="Adınız"><input placeholder="Şarkı adı"><button type="button">İstek Gönder</button></form>';
    if($type==='form') return '<form class="sbx-form"'.$styleAttr.'><h3>'.$e($title ?: 'İletişim Formu').'</h3><input placeholder="Ad Soyad"><input placeholder="E-posta"><textarea placeholder="Mesajınız"></textarea><button type="button">'.$e($button ?: 'Gönder').'</button></form>';
    if($type==='map') return '<article class="sbx-map"'.$styleAttr.'><h3>'.$e($title ?: 'Konum').'</h3><p>'.$e($desc ?: 'Harita ve yol tarifi alanı.').'</p><div>Harita Alanı</div></article>';
    if($type==='notification') return '<article class="sbx-notice"'.$styleAttr.'><b>'.$e($title ?: 'Bildirim').'</b><p>'.$e($desc ?: 'Kampanya, duyuru veya push bildirim alanı.').'</p></article>';
    if($type==='cart') return '<article class="sbx-cart"'.$styleAttr.'><h3>'.$e($title ?: 'Sepet').'</h3><p>'.$e($desc ?: 'Sepet ve ödeme akışı.').'</p><a class="sbx-btn" href="#">Sepete Git</a></article>';
    return '<article class="sbx-generic"'.$styleAttr.'><small>'.$e($type).'</small><h3>'.$e($title ?: ucfirst($type)).'</h3><p>'.$e($desc ?: $text ?: 'Bu blok önizleme için hazırlanmıştır.').'</p></article>';
}
function ao_sitebuilder_render_html($builderJson){
    $sections=json_decode($builderJson ?: '[]', true); if(!is_array($sections)) $sections=[]; $html='';
    foreach($sections as $sec){
        $children=is_array($sec['children']??null)?$sec['children']:(is_array($sec['content']??null)?$sec['content']:[]);
        $cols=max(1,min(4,(int)($sec['cols']??count($children)?:1)));
        $sectionStyle=ao_sitebuilder_style_attr($sec['style']??[]);
        if(isset($sec['content']['bgColor'])) $sectionStyle=' style="background-color:'.htmlspecialchars((string)$sec['content']['bgColor'],ENT_QUOTES,'UTF-8').';padding:'.htmlspecialchars((string)($sec['content']['padding']??'40px'),ENT_QUOTES,'UTF-8').'"';
        $html.='<section class="sbx-section"'.$sectionStyle.'><div class="sbx-row sbx-cols-'.$cols.'">';
        foreach($children as $el){ $html.='<div class="sbx-col">'.ao_sitebuilder_render_element($el).'</div>'; }
        if(!$children) $html.='<div class="sbx-col"><h2>'.htmlspecialchars((string)($sec['label']??'Yeni Bölüm'),ENT_QUOTES,'UTF-8').'</h2></div>';
        $html.='</div></section>';
    }
    return $html ?: '<section class="sbx-section"><h1>Ahost One Site Builder</h1></section>';
}
function ao_sitebuilder_export_project($projectId){
    ao_schema_ensure_v1400(); $projectId=(int)$projectId; $q=db()->prepare('SELECT * FROM sitebuilder_projects WHERE id=? LIMIT 1'); $q->execute([$projectId]); $project=$q->fetch(); if(!$project) throw new Exception('Proje bulunamadı.');
    $q=db()->prepare('SELECT * FROM sitebuilder_pages WHERE project_id=? ORDER BY id'); $q->execute([$projectId]); $pages=$q->fetchAll(); if(!$pages) throw new Exception('Export edilecek sayfa yok.');
    $dir=__DIR__.'/storage/exports'; if(!is_dir($dir)) mkdir($dir,0775,true); $file=$dir.'/sitebuilder_'.$projectId.'_'.date('Ymd_His').'.zip'; $zip=new ZipArchive(); if($zip->open($file,ZipArchive::CREATE)!==true) throw new Exception('ZIP oluşturulamadı.');
    $css='body{font-family:Arial,sans-serif;margin:0;color:#0f172a;background:#f8fafc}.sbx-section{padding:60px 8%;background:#fff;margin:18px;border-radius:22px;box-shadow:0 12px 30px rgba(15,23,42,.08)}.sbx-row{display:grid;gap:20px}.sbx-cols-1{grid-template-columns:1fr}.sbx-cols-2{grid-template-columns:1fr 1fr}.sbx-cols-3{grid-template-columns:repeat(3,1fr)}.sbx-cols-4{grid-template-columns:repeat(4,1fr)}.sbx-btn{display:inline-block;background:#2563eb;color:#fff;text-decoration:none;padding:12px 18px;border-radius:12px}.sbx-form input{display:block;margin:8px 0;padding:12px;border:1px solid #ddd;border-radius:10px;width:100%;max-width:360px}.sbx-price{border:1px solid #e5e7eb;border-radius:16px;padding:18px}@media(max-width:760px){.sbx-row{grid-template-columns:1fr!important}.sbx-section{padding:32px 18px;margin:8px}}';
    $zip->addFromString('assets/style.css',$css); $zip->addFromString('README.txt','Ahost One Site Builder export paketi. index.html dosyasını herhangi bir hostingde yayınlayabilirsiniz.');
    foreach($pages as $p){ $body=ao_sitebuilder_render_html($p['builder_json']); $html='<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.htmlspecialchars($p['title'],ENT_QUOTES,'UTF-8').'</title><link rel="stylesheet" href="assets/style.css"></head><body>'.$body.'</body></html>'; $name=($p['slug']==='index'?'index':preg_replace('/[^a-z0-9_-]+/i','-',$p['slug'])).'.html'; $zip->addFromString($name,$html); }
    $zip->close(); db()->prepare('INSERT INTO sitebuilder_exports(project_id,file_path,status) VALUES(?,?,"ready")')->execute([$projectId,$file]); return $file;
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/site-builder/page-create') { require_admin(); verify_csrf(); ao_schema_ensure_v1400(); $pid=(int)($_POST['project_id']??1); $title=trim($_POST['title']??'Yeni Sayfa'); $slug=trim($_POST['slug']??'') ?: strtolower(preg_replace('/[^a-z0-9]+/i','-',$title)); $json=json_encode([['id'=>'sec1','type'=>'section','cols'=>1,'content'=>[['id'=>'h1','type'=>'heading','text'=>$title,'props'=>[]]]]],JSON_UNESCAPED_UNICODE); try{ $st=db()->prepare('INSERT INTO sitebuilder_pages(project_id,title,slug,builder_json,status) VALUES(?,?,?,?,"draft")'); $st->execute([$pid,$title,$slug,$json]); flash('success','Sayfa oluşturuldu.'); redirect_to('admin/site-builder/editor?id='.db()->lastInsertId()); }catch(Throwable $e){ flash('error','Sayfa oluşturulamadı: '.$e->getMessage()); redirect_to('admin/site-builder/pages'); } }
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/site-builder/page-save') { require_admin(); verify_csrf(); ao_schema_ensure_v1400(); $id=(int)($_POST['id']??0); $json=$_POST['builder_json']??'[]'; $html=ao_sitebuilder_render_html($json); try{ db()->prepare('UPDATE sitebuilder_pages SET builder_json=?, html_cache=?, status="published" WHERE id=?')->execute([$json,$html,$id]); db()->prepare('INSERT INTO sitebuilder_revisions(page_id,builder_json,created_by) VALUES(?,?,?)')->execute([$id,$json,(int)($_SESSION['admin_id']??0)]); flash('success','Sayfa kaydedildi.'); }catch(Throwable $e){ flash('error','Kaydedilemedi: '.$e->getMessage()); } redirect_to('admin/site-builder/editor?id='.$id); }

