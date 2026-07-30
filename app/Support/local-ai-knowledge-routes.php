<?php
// v25.1.2 Free AI + Local Knowledge Research Engine (no global CSS/layout changes)
function ao_ai_ensure_schema(){ static $done=false; if($done) return; $done=true;
    if(function_exists('ao_v23_ensure_schema')) ao_v23_ensure_schema();
    try{db()->exec("CREATE TABLE IF NOT EXISTS ai_settings(id INT AUTO_INCREMENT PRIMARY KEY, provider VARCHAR(40) DEFAULT 'local', api_key TEXT NULL, base_url VARCHAR(255) NULL, model VARCHAR(120) NULL, is_active TINYINT(1) DEFAULT 1, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");}catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{db()->exec("CREATE TABLE IF NOT EXISTS knowledge_research_queue(id INT AUTO_INCREMENT PRIMARY KEY, question TEXT, normalized_question VARCHAR(255), answer LONGTEXT NULL, sources LONGTEXT NULL, status VARCHAR(40) DEFAULT 'draft', article_id INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");}catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ if(!db()->query("SHOW COLUMNS FROM knowledge_research_queue LIKE 'feedback_value'")->fetch()) db()->exec("ALTER TABLE knowledge_research_queue ADD COLUMN feedback_value VARCHAR(20) NULL"); }catch(Throwable $e){}
    try{ if(!db()->query("SHOW COLUMNS FROM knowledge_research_queue LIKE 'feedback_at'")->fetch()) db()->exec("ALTER TABLE knowledge_research_queue ADD COLUMN feedback_at DATETIME NULL"); }catch(Throwable $e){}
    try{db()->exec("INSERT IGNORE INTO ai_settings(id,provider,model,is_active) VALUES(1,'local','local-research-v1',1)");}catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_ai_setting(){ ao_ai_ensure_schema(); try{$r=db()->query('SELECT * FROM ai_settings WHERE id=1')->fetch(); return $r?:['provider'=>'local','model'=>'local-research-v1','api_key'=>'','base_url'=>''];}catch(Throwable $e){return ['provider'=>'local','model'=>'local-research-v1','api_key'=>'','base_url'=>''];} }
function ao_ai_public_providers(){ return [
    'local'=>'Site İçi Akıllı Bilgi Motoru (API gerekmez)',
    'openai'=>'ChatGPT / OpenAI',
    'gemini'=>'Google Gemini Free Tier',
    'groq'=>'Groq Free Tier',
    'openrouter'=>'OpenRouter Free Modeller',
    'ollama'=>'Ollama / Yerel Model'
]; }
function ao_ai_setting_value($key, $default=''){
    try{ $q=db()->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1'); $q->execute([$key]); $v=$q->fetchColumn(); if($v!==false) return (string)$v; }catch(Throwable $e){}
    try{ $q=db()->prepare('SELECT setting_value FROM system_settings WHERE setting_key=? LIMIT 1'); $q->execute([$key]); $v=$q->fetchColumn(); if($v!==false) return (string)$v; }catch(Throwable $e){}
    return $default;
}
function ao_ai_provider_config($provider=''){
    $base=ao_ai_setting();
    $provider=trim((string)$provider);
    if($provider==='chatgpt') $provider='openai';
    if($provider==='') $provider=ao_ai_setting_value('default_ai_provider', $base['provider'] ?? 'local');
    $providers=json_decode(ao_ai_setting_value('ai_providers_json','{}'), true);
    $saved=is_array($providers) ? ($providers[$provider] ?? []) : [];
    $key=trim((string)($saved['api_key'] ?? ''));
    $model=trim((string)($saved['model'] ?? ''));
    $baseUrl=trim((string)($saved['base_url'] ?? ''));
    if(($base['provider'] ?? '')===$provider){
        $key=$key ?: trim((string)($base['api_key'] ?? ''));
        $model=$model ?: trim((string)($base['model'] ?? ''));
        $baseUrl=$baseUrl ?: trim((string)($base['base_url'] ?? ''));
    }
    if($provider==='openai'){
        $key=$key ?: trim(ao_ai_setting_value('openai_api_key'));
        $key=$key ?: trim(ao_ai_setting_value('module_openai_api_key'));
        $model=$model ?: trim(ao_ai_setting_value('openai_model'));
        $model=$model ?: 'gpt-4o-mini';
        $baseUrl=$baseUrl ?: 'https://api.openai.com/v1/chat/completions';
    } elseif($provider==='gemini'){
        $key=$key ?: trim(ao_ai_setting_value('gemini_api_key'));
        $key=$key ?: trim(ao_ai_setting_value('ai_api_key'));
        $model=$model ?: trim(ao_ai_setting_value('gemini_model'));
        $model=$model ?: 'gemini-2.5-flash';
    } elseif($provider==='groq'){
        $model=$model ?: 'llama-3.1-8b-instant';
    } elseif($provider==='openrouter'){
        $model=$model ?: 'meta-llama/llama-3.2-3b-instruct:free';
    }
    return ['provider'=>$provider,'api_key'=>$key,'model'=>$model,'base_url'=>$baseUrl];
}
function ao_kb_norm($q){ $q=mb_strtolower(trim((string)$q),'UTF-8'); $q=preg_replace('/\s+/u',' ',$q); return mb_substr($q,0,255,'UTF-8'); }
function ao_kb_find_local($question){ ao_ai_ensure_schema(); $q=ao_kb_norm($question); if($q==='') return null; try{
    $like='%'.str_replace(['%','_'],['\\%','\\_'],$q).'%';
    $st=db()->prepare("SELECT * FROM knowledge_articles WHERE audience='customer' AND status IN('published','draft') AND (LOWER(title) LIKE ? OR LOWER(content) LIKE ? OR LOWER(tags) LIKE ?) ORDER BY status='published' DESC, id DESC LIMIT 1");
    $st->execute([$like,$like,$like]); $row=$st->fetch();
    if($row) {
        $queueId=0;
        try{ $qq=db()->prepare('SELECT id FROM knowledge_research_queue WHERE article_id=? ORDER BY id DESC LIMIT 1'); $qq->execute([(int)($row['id']??0)]); $queueId=(int)$qq->fetchColumn(); }catch(Throwable $e){}
        return ['source'=>'knowledge','title'=>$row['title'],'answer'=>strip_tags((string)($row['content']?:$row['excerpt']?:$row['title'])),'article'=>$row,'queue_id'=>$queueId];
    }
  }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
  return null;
}
function ao_http_get_text($url,$timeout=8){
    $ctx=stream_context_create(['http'=>['timeout'=>$timeout,'header'=>"User-Agent: AhostOneResearchBot/1.0\r\nAccept: text/html,application/json\r\n"]]);
    $body=@file_get_contents($url,false,$ctx); if(!is_string($body)) return '';
    return mb_substr($body,0,120000,'UTF-8');
}
function ao_kb_web_research($question){
    $query=urlencode($question.' hosting domain DNS SSL'); $html=ao_http_get_text('https://duckduckgo.com/html/?q='.$query,10);
    $sources=[]; $texts=[];
    if($html){
        if(preg_match_all('~<a[^>]+class="result__a"[^>]+href="([^"]+)"[^>]*>(.*?)</a>~si',$html,$m,PREG_SET_ORDER)){
            foreach(array_slice($m,0,4) as $r){ $u=html_entity_decode($r[1]); $t=trim(strip_tags(html_entity_decode($r[2]))); if(str_contains($u,'uddg=')){parse_str(parse_url($u,PHP_URL_QUERY)?:'', $qs); if(!empty($qs['uddg'])) $u=$qs['uddg'];} $sources[]=['title'=>$t,'url'=>$u]; }
        }
    }
    foreach($sources as $src){ $page=ao_http_get_text($src['url'],6); if(!$page) continue; $page=preg_replace('~<(script|style|noscript)[^>]*>.*?</\1>~si',' ',$page); $txt=trim(preg_replace('/\s+/u',' ',strip_tags($page))); if($txt) $texts[]=mb_substr($txt,0,2500,'UTF-8'); }
    return ['sources'=>$sources,'texts'=>$texts];
}
function ao_local_answer_from_research($question,$research){
    $q=trim((string)$question); $joined=implode("\n", $research['texts'] ?? []);
    $bullets=[];
    foreach(preg_split('/(?<=[.!?])\s+/u',$joined) as $sent){
        $sent=trim($sent); if(mb_strlen($sent,'UTF-8')<45 || mb_strlen($sent,'UTF-8')>260) continue;
        $score=0; foreach(preg_split('/\s+/u',ao_kb_norm($q)) as $w){ if(mb_strlen($w,'UTF-8')>3 && str_contains(mb_strtolower($sent,'UTF-8'),$w)) $score++; }
        if($score>0) $bullets[$sent]=$score; if(count($bullets)>16) break;
    }
    arsort($bullets); $top=array_slice(array_keys($bullets),0,5);
    if(!$top) $top=['Bu konu için canlı kaynaklardan yeterli veri alınamadı. Admin onayıyla manuel makale taslağı oluşturulabilir.'];
    $ans="Sorunuz: ".$q."\n\nÖzet:\n";
    foreach($top as $b){ $ans.='- '.$b."\n"; }
    $ans.="\nÖneri: Bu cevap otomatik araştırma taslağıdır. Yayınlanmadan önce admin tarafından kontrol edilmelidir.";
    return $ans;
}
function ao_ai_call_optional($prompt, $providerOverride=''){
    $s=ao_ai_provider_config($providerOverride); $provider=$s['provider']??'local'; $key=trim((string)($s['api_key']??'')); $model=trim((string)($s['model']??'')); $baseUrl=trim((string)($s['base_url']??''));
    if($provider==='local' || $key==='') return null;
    $payload=null; $url=''; $headers=['Content-Type: application/json'];
    if($provider==='openai'){ $model=$model?:'gpt-4o-mini'; $url=$baseUrl?:'https://api.openai.com/v1/chat/completions'; $headers[]='Authorization: Bearer '.$key; $payload=['model'=>$model,'messages'=>[['role'=>'system','content'=>'Return concise, valid output for the requested builder task.'],['role'=>'user','content'=>$prompt]],'temperature'=>0.2]; }
    elseif($provider==='gemini'){ $model=$model?:'gemini-2.5-flash'; $url='https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode($model).':generateContent?key='.rawurlencode($key); $payload=['contents'=>[['parts'=>[['text'=>$prompt]]]]]; }
    elseif($provider==='groq'){ $model=$model?:'llama-3.1-8b-instant'; $url='https://api.groq.com/openai/v1/chat/completions'; $headers[]='Authorization: Bearer '.$key; $payload=['model'=>$model,'messages'=>[['role'=>'user','content'=>$prompt]],'temperature'=>0.2]; }
    elseif($provider==='openrouter'){ $model=$model?:'meta-llama/llama-3.2-3b-instruct:free'; $url='https://openrouter.ai/api/v1/chat/completions'; $headers[]='Authorization: Bearer '.$key; $headers[]='HTTP-Referer: '.(ahost_config('base_url','')?:'http://localhost'); $payload=['model'=>$model,'messages'=>[['role'=>'user','content'=>$prompt]],'temperature'=>0.2]; }
    else return null;
    $ctx=stream_context_create(['http'=>['method'=>'POST','timeout'=>20,'header'=>implode("\r\n",$headers)."\r\n",'content'=>json_encode($payload,JSON_UNESCAPED_UNICODE)]]);
    $res=@file_get_contents($url,false,$ctx); if(!$res) return null; $j=json_decode($res,true);
    return $j['candidates'][0]['content']['parts'][0]['text'] ?? $j['choices'][0]['message']['content'] ?? null;
}
function ao_kb_answer_question($question,$autoDraft=true){
    ao_ai_ensure_schema(); if($local=ao_kb_find_local($question)) return ['ok'=>true,'mode'=>'local','answer'=>$local['answer'],'article'=>$local['article'],'sources'=>[],'queue_id'=>(int)($local['queue_id']??0)];
    $research=ao_kb_web_research($question); $base=ao_local_answer_from_research($question,$research);
    $notes=trim(implode("\n---\n",$research['texts'] ?? []));
    $ai=ao_ai_call_optional("Türkçe, kısa ve müşterinin anlayacağı bir bilgi bankası cevabı üret. Eğer araştırma notları varsa onlardan yararlan; not yoksa genel teknik bilgine dayanarak cevap ver. Emin olmadığın noktaları kesin hüküm gibi yazma. Gerekiyorsa 3-5 maddelik adım veya açıklama ekle.\n\nSoru: {$question}\n\nAraştırma notları:\n".($notes!==''?$notes:'Not bulunamadı.'));
    $answer=$ai ?: $base; $sources=json_encode($research['sources']??[],JSON_UNESCAPED_UNICODE);
    $qid=0; try{ $st=db()->prepare('INSERT INTO knowledge_research_queue(question,normalized_question,answer,sources,status) VALUES(?,?,?,?,?)'); $st->execute([$question,ao_kb_norm($question),$answer,$sources,'draft']); $qid=(int)db()->lastInsertId(); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    if($qid>0){
        try{
            $title=mb_substr(trim($question),0,180,'UTF-8');
            $slug=ao_v23_slug($title).'-ai-'.date('ymd').'-'.$qid;
            $excerpt=mb_substr(trim(strip_tags($answer)),0,240,'UTF-8');
            $content=nl2br(e($answer));
            db()->prepare("INSERT INTO knowledge_articles(audience,category,title,slug,excerpt,content,status,lang,tags,is_seed) VALUES('customer','AI Yanıtları',?,?,?,?, 'draft','tr','ai,bilgi bankası,otomatik yanıt',0)")->execute([$title,$slug,$excerpt,$content]);
            $aid=(int)db()->lastInsertId();
            db()->prepare("UPDATE knowledge_research_queue SET article_id=?, status='article_draft' WHERE id=?")->execute([$aid,$qid]);
        }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    }
    return ['ok'=>true,'mode'=>'research','answer'=>$answer,'sources'=>$research['sources']??[],'queue_id'=>$qid];
}
function ao_kb_publish_queue($id){ ao_ai_ensure_schema(); $id=(int)$id; if(!$id) return false; try{ $q=db()->prepare('SELECT * FROM knowledge_research_queue WHERE id=?'); $q->execute([$id]); $r=$q->fetch(); if(!$r) return false; $title=mb_substr(trim($r['question']),0,180,'UTF-8'); $slug=ao_v23_slug($title).'-'.date('ymd').'-'.$id; $content=nl2br(e($r['answer'])); db()->prepare("INSERT INTO knowledge_articles(audience,category,title,slug,excerpt,content,status,lang,tags,is_seed) VALUES('customer','Otomatik Araştırma',?,?,?,?, 'draft','tr','otomatik,araştırma',0)")->execute([$title,$slug,mb_substr(strip_tags($r['answer']),0,240,'UTF-8'),$content]); $aid=(int)db()->lastInsertId(); db()->prepare("UPDATE knowledge_research_queue SET status='article_draft', article_id=? WHERE id=?")->execute([$aid,$id]); return true; }catch(Throwable $e){ return false; } }

if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/ai-center/settings/save') { require_admin(); verify_csrf(); ao_ai_ensure_schema(); $provider=$_POST['provider']??'local'; if(!array_key_exists($provider,ao_ai_public_providers())) $provider='local'; $model=trim($_POST['model']??''); $base=trim($_POST['base_url']??''); $key=trim($_POST['api_key']??''); try{db()->prepare('UPDATE ai_settings SET provider=?, model=?, base_url=?, api_key=?, is_active=1 WHERE id=1')->execute([$provider,$model,$base,$key]); save_setting('default_ai_provider',$provider); if($provider==='openai') save_setting('openai_api_key',$key); elseif($provider==='gemini') save_setting('gemini_api_key',$key); else save_setting('ai_api_key',$key); if($model!=='') save_setting('default_ai_model',$model); if($base!=='') save_setting('ai_base_url',$base); flash('success','AI ayarları Genel Ayarlar > AI Center altında kaydedildi.');}catch(Throwable $e){flash('error','AI ayarı kaydedilemedi: '.$e->getMessage());} redirect_to('admin/settings#ai'); }
if ($route==='admin/ai-center/settings') { require_admin(); flash('info','AI API ayarları tek merkezde tutulur: Genel Ayarlar > AI Center.'); redirect_to('admin/settings#ai'); }
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/knowledge-research/publish') { require_admin(); verify_csrf(); $ok=ao_kb_publish_queue((int)($_POST['id']??0)); flash($ok?'success':'error',$ok?'Araştırma bilgi bankasına taslak makale olarak eklendi.':'Taslak makale oluşturulamadı.'); redirect_to('admin/knowledge-research'); }
if ($route==='admin/knowledge-research') { require_admin(); ao_ai_ensure_schema(); try{$rows=db()->query('SELECT * FROM knowledge_research_queue ORDER BY id DESC LIMIT 100')->fetchAll();}catch(Throwable $e){$rows=[];} $trs=''; foreach($rows as $r){$src=json_decode($r['sources']??'[]',true)?:[]; $trs.='<tr><td><strong>'.e(mb_substr($r['question'],0,90,'UTF-8')).'</strong><br><small>'.e($r['created_at']).'</small></td><td>'.e($r['status']).'</td><td>'.count($src).' kaynak</td><td><form method="post" action="'.url('admin/knowledge-research/publish').'">'.csrf_field().'<input type="hidden" name="id" value="'.(int)$r['id'].'"><button class="ao-mini-btn">Taslak Makale Yap</button></form></td></tr>';}
    ao_admin_fallback_shell('Bilgi Araştırma Kuyruğu','<div class="ao-page-head"><div><h2>Bilgi Araştırma Kuyruğu</h2><p>Bilgi bankasında bulunamayan sorular burada toplanır, onayla taslak makaleye dönüşür.</p></div><a class="ao-btn soft" href="'.url('admin/settings#ai').'">AI Ayarları</a></div><div class="ao-card"><div class="ao-table-wrap"><table class="ao-table"><tr><th>Soru</th><th>Durum</th><th>Kaynak</th><th>İşlem</th></tr>'.($trs?:'<tr><td colspan="4">Henüz araştırma yok.</td></tr>').'</table></div></div>'); }
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='bilgi-bankasi/feedback') {
    header('Content-Type: application/json; charset=utf-8');
    ao_ai_ensure_schema();
    $id=(int)($_POST['id']??0);
    $value=trim((string)($_POST['value']??''));
    if(!$id || !in_array($value,['yes','no'],true)){ echo json_encode(['ok'=>false,'message'=>'Eksik geri bildirim.'], JSON_UNESCAPED_UNICODE); exit; }
    try{
        db()->prepare("UPDATE knowledge_research_queue SET feedback_value=?, feedback_at=NOW() WHERE id=?")->execute([$value,$id]);
        echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);
    }catch(Throwable $e){
        echo json_encode(['ok'=>false,'message'=>'Geri bildirim kaydedilemedi.'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}
if (in_array($route,['bilgi-bankasi/ask','knowledgebase/ask','knowledge-base/ask'],true)) { $question=trim($_GET['q']??$_POST['q']??''); $result=$question?ao_kb_answer_question($question):null; site_view('knowledge-base/ask', ['pageTitle'=>'Bilgi Bankası Asistanı','question'=>$question,'result'=>$result]); exit; }
if (in_array($route,['bilgi-bankasi/ask','knowledgebase/ask','knowledge-base/ask'],true)) { $question=trim($_GET['q']??$_POST['q']??''); $result=$question?ao_kb_answer_question($question):null; require_once dirname(__DIR__) . '/Views/site/shared/content-renderer.php'; ob_start(); echo '<section class="ao-content-panel"><form method="get" action="'.url('bilgi-bankasi/ask').'"><label><strong>Sorunuzu yazın</strong><input name="q" value="'.e($question).'" placeholder="Örn: DNS yayılımı ne kadar sürer?" style="width:100%;margin-top:8px"></label><button class="ao-btn" style="margin-top:10px">Sor</button></form></section>'; if($result){ echo '<section class="ao-content-panel"><div class="ao-content-meta"><strong>'.($result['mode']==='local'?'Bilgi Bankası Cevabı':'Otomatik Araştırma Cevabı').'</strong></div><div style="white-space:pre-wrap">'.e($result['answer']).'</div>'; if(!empty($result['sources'])){echo '<hr><strong>Kaynaklar</strong><ul>'; foreach($result['sources'] as $src){echo '<li><a rel="nofollow" target="_blank" href="'.e($src['url']??'#').'">'.e($src['title']??$src['url']??'Kaynak').'</a></li>';} echo '</ul>';} echo '</section>'; } $content=ob_get_clean(); ao_site_content_page(['content'=>$content,'heroTitle'=>'Bilgi Bankası Asistanı','kicker'=>'Akıllı Arama','summary'=>'Önce site içi bilgi bankasında arar; sonuç yoksa ücretsiz araştırma motoruyla taslak cevap oluşturur.','breadcrumbs'=>[['label'=>'Ana Sayfa','href'=>url('')],['label'=>'Bilgi Bankası','href'=>url('bilgi-bankasi')],['label'=>'Soru Sor']]]); exit; }

if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='api/ai-generate-site') {
    require_admin();
    header('Content-Type: application/json; charset=utf-8');
    $siteType=trim($_POST['site_type']??'landing');
    $siteName=trim($_POST['site_name']??'Yeni Site') ?: 'Yeni Site';
    $tagline=trim($_POST['tagline']??'');
    $services=trim($_POST['services']??'');
    $color=trim($_POST['color_scheme']??'modern');
    $custom=trim($_POST['custom_prompt']??'');
    $aiProvider=trim($_POST['ai_provider']??'');
    $fallback=[
        ['type'=>'hero','title'=>$siteName,'text'=>$tagline ?: 'Modern ve hızlı dijital deneyim','button'=>'Teklif Al','style'=>['scheme'=>$color]],
        ['type'=>'features','title'=>'Hizmetler','items'=>array_values(array_filter(array_map('trim', explode(',', $services ?: 'Web tasarım, Hosting, SEO'))))],
        ['type'=>'cta','title'=>'Projeni birlikte başlatalım','text'=>'İhtiyacınızı yazın, size uygun çözümü hazırlayalım.']
    ];
    $prompt="Türkçe bir site builder için sadece geçerli JSON array döndür. Markdown kullanma. Site türü: {$siteType}. Site adı: {$siteName}. Slogan: {$tagline}. Hizmetler: {$services}. Renk yaklaşımı: {$color}. Ek not: {$custom}. Her bölümde type,title,text,items,style alanları olabilir.";
    $ai=ao_ai_call_optional($prompt, $aiProvider);
    $design=$fallback;
    if(is_string($ai) && trim($ai)!==''){
        $clean=trim(preg_replace('/```(?:json)?|```/i','',$ai));
        $decoded=json_decode($clean,true);
        if(is_array($decoded)) $design=$decoded;
    }
    $pageId=0;
    try{
        $ascii=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$siteName);
        $slug=trim(preg_replace('/[^a-z0-9]+/','-', strtolower($ascii ?: $siteName)),'-') ?: 'ai-site';
        $slug.='-'.date('ymd-His');
        db()->prepare("INSERT INTO sitebuilder_pages(project_id,title,slug,builder_json,status) VALUES(1,?,?,?,'draft')")->execute([$siteName,$slug,json_encode($design,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
        $pageId=(int)db()->lastInsertId();
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    $aiUsed=is_string($ai) && trim($ai)!=='';
    echo json_encode(['success'=>true,'page_id'=>$pageId,'design'=>$design,'provider'=>$aiProvider ?: (ao_ai_provider_config()['provider'] ?? 'local'),'ai_used'=>$aiUsed,'message'=>$aiUsed?'Tasarım taslağı AI ile oluşturuldu.':'Seçilen AI sağlayıcısından cevap alınamadı; güvenli taslak oluşturuldu.'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='api/ai-generate-app') {
    require_admin();
    header('Content-Type: application/json; charset=utf-8');
    $appName=trim($_POST['app_name']??'Ahost Mobile') ?: 'Ahost Mobile';
    $features=array_values(array_filter((array)($_POST['features']??[])));
    $aiProvider=trim($_POST['ai_provider']??'');
    $spec=[
        'name'=>$appName,
        'platform'=>trim($_POST['platform']??$_POST['selected_platform']??'pwa'),
        'screens'=>[
            ['name'=>'Splash','description'=>'Marka açılış ekranı'],
            ['name'=>'Home','description'=>'Ana sayfa ve hızlı erişimler'],
            ['name'=>'Profile','description'=>'Kullanıcı profil alanı']
        ],
        'features'=>$features,
        'theme'=>['primary'=>'#2563eb','secondary'=>'#06b6d4']
    ];
    $prompt="Türkçe bir mobile builder için sadece geçerli JSON object döndür. Markdown kullanma. Uygulama adı: {$appName}. Açıklama: ".trim($_POST['app_description']??'').". Platform: ".trim($_POST['platform']??$_POST['selected_platform']??'pwa').". Kategori: ".trim($_POST['category']??'business').". Özellikler: ".implode(', ', $features).". Renk: ".trim($_POST['color_scheme']??'blue').". UI: ".trim($_POST['ui_style']??'material').". JSON alanları: name, platform, screens, navigation, features, theme.";
    $ai=ao_ai_call_optional($prompt, $aiProvider);
    if(is_string($ai) && trim($ai)!==''){
        $clean=trim(preg_replace('/```(?:json)?|```/i','',$ai));
        $decoded=json_decode($clean,true);
        if(is_array($decoded)) $spec=$decoded;
    }
    $aiUsed=is_string($ai) && trim($ai)!=='';
    echo json_encode(['success'=>true,'project_id'=>0,'spec'=>$spec,'provider'=>$aiProvider ?: (ao_ai_provider_config()['provider'] ?? 'local'),'ai_used'=>$aiUsed,'message'=>$aiUsed?'Mobil uygulama taslağı AI ile oluşturuldu.':'Seçilen AI sağlayıcısından cevap alınamadı; güvenli mobil taslak oluşturuldu.'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

if (isset($adminMap[$route])) { require_admin(); view($adminMap[$route], ['pageTitle' => ucwords(str_replace(['admin/','-'], ['', ' '], $route ?: 'Admin'))]); exit; }
// Admin announcements handlers (create/toggle/delete) and listing data
if ($route === 'admin/announcements' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin(); verify_csrf();
    $action = $_POST['action'] ?? 'create';
    try {
        db()->exec('CREATE TABLE IF NOT EXISTS announcements (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(190) NULL, short_description TEXT NULL, body TEXT NOT NULL, target VARCHAR(40) DEFAULT "all", channel VARCHAR(40) DEFAULT "site", starts_at DATETIME NULL, ends_at DATETIME NULL, is_active TINYINT(1) DEFAULT 1, created_by INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        try {
            $existing = db()->query("SHOW COLUMNS FROM announcements LIKE 'short_description'")->fetch();
            if (!$existing) {
                db()->exec('ALTER TABLE announcements ADD COLUMN short_description TEXT NULL');
            }
        } catch (Throwable $ignored) {}
        if ($action === 'create' || $action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $title = trim((string)($_POST['title'] ?? ''));
            $shortDescription = trim((string)($_POST['short_description'] ?? ''));
            $body = trim((string)($_POST['body'] ?? ''));
            if ($body === '') throw new Exception('Duyuru metni zorunlu.');
            $target = trim((string)($_POST['target'] ?? 'all'));
            $channel = trim((string)($_POST['channel'] ?? 'site'));
            $starts = trim((string)($_POST['starts_at'] ?? '')) ?: null;
            $ends = trim((string)($_POST['ends_at'] ?? '')) ?: null;
            $is_active = (int)($_POST['is_active'] ?? 0);
            if ($action === 'update' && $id > 0) {
                db()->prepare('UPDATE announcements SET title=?, short_description=?, body=?, target=?, channel=?, starts_at=?, ends_at=?, is_active=? WHERE id=?')->execute([$title, $shortDescription, $body, $target, $channel, $starts, $ends, $is_active, $id]);
                flash('success','Duyuru güncellendi.');
            } else {
                db()->prepare('INSERT INTO announcements(title, short_description, body, target, channel, starts_at, ends_at, is_active, created_by) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$title, $shortDescription, $body, $target, $channel, $starts, $ends, $is_active, (int)($_SESSION['admin_id'] ?? 0)]);
                flash('success','Duyuru oluşturuldu.');
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                db()->prepare('DELETE FROM announcements WHERE id=?')->execute([$id]);
                flash('success','Duyuru silindi.');
            }
        } elseif ($action === 'toggle') {
            $id = (int)($_POST['id'] ?? 0);
            $val = (int)($_POST['is_active'] ?? 0);
            if ($id) {
                db()->prepare('UPDATE announcements SET is_active=? WHERE id=?')->execute([$val, $id]);
                flash('success','Duyuru durumu güncellendi.');
            }
        }
    } catch(Throwable $e) {
        flash('error','İşlem başarısız: '.$e->getMessage());
    }
    redirect_to('admin/announcements');
}
if ($route === 'admin/announcements' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    require_admin();
    try {
        db()->exec('CREATE TABLE IF NOT EXISTS announcements (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(190) NULL, short_description TEXT NULL, body TEXT NOT NULL, target VARCHAR(40) DEFAULT "all", channel VARCHAR(40) DEFAULT "site", starts_at DATETIME NULL, ends_at DATETIME NULL, is_active TINYINT(1) DEFAULT 1, created_by INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        try {
            $existing = db()->query("SHOW COLUMNS FROM announcements LIKE 'short_description'")->fetch();
            if (!$existing) {
                db()->exec('ALTER TABLE announcements ADD COLUMN short_description TEXT NULL');
            }
        } catch (Throwable $ignored) {}
    } catch(Throwable $x) {}
    $q = db()->prepare('SELECT * FROM announcements ORDER BY id DESC LIMIT 200'); $q->execute(); $annList = $q->fetchAll() ?: [];
    $edit = null; $editId = (int)($_GET['edit'] ?? 0); if ($editId) { $e=db()->prepare('SELECT * FROM announcements WHERE id=? LIMIT 1'); $e->execute([$editId]); $edit = $e->fetch() ?: null; }
    view('announcements/index', ['pageTitle'=>'Duyurular','announcements'=>$annList,'editAnnouncement'=>$edit]); exit;
}
if (isset($authMap[$route])) { $authTitle = str_starts_with($route, 'admin/') ? 'Admin Girişi' : 'Müşteri Girişi'; auth_view($authMap[$route], ['pageTitle' => $authTitle]); exit; }
if (isset($customerMap[$route])) { require_customer(); customer_view($customerMap[$route], ['pageTitle' => ucwords(str_replace(['client/','-'], ['', ' '], $route ?: 'Müşteri Paneli'))]); exit; }
if (isset($siteMap[$route])) { site_view($siteMap[$route], ['pageTitle' => 'Ahost One']); exit; }
if (function_exists('ao_public_route_dispatch') && ao_public_route_dispatch($route)) { exit; }
http_response_code(404); site_view('errors/404', ['pageTitle'=>'404 - Sayfa Bulunamadı']);
