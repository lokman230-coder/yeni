<?php
// Currency, scan and domain tools route helpers.
if ($route === 'currency/rate') {
    header('Content-Type: application/json; charset=utf-8');
    $cur = strtoupper($_GET['currency'] ?? 'TRY');
    echo json_encode(['ok'=>true,'currency'=>$cur,'usd_try'=>ao_currency_rate('USD','TRY')], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($route === 'language/set') {
    header('Content-Type: application/json; charset=utf-8');
    $lang = strtolower(preg_replace('~[^a-z_-]~i','', (string)($_POST['lang'] ?? $_GET['lang'] ?? '')));
    $options = function_exists('ao_language_options') ? ao_language_options() : ['tr'=>'Türkçe'];
    if ($lang === '' || !array_key_exists($lang, $options)) {
        http_response_code(422);
        echo json_encode(['ok'=>false,'message'=>'Dil seçeneği geçersiz.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $_SESSION['lang'] = $lang;
    if (!headers_sent()) setcookie('ao_lang', $lang, time()+31536000, '/');
    echo json_encode(['ok'=>true,'lang'=>$lang,'label'=>$options[$lang] ?? strtoupper($lang)], JSON_UNESCAPED_UNICODE);
    exit;
}

// v24.10.0 Scan & Report Center Pro + DomainNameAPI REST/API Key adapter
function ao_diag_mask($value) {
    $value = (string)$value;
    if ($value === '') return '';
    if (strlen($value) <= 4) return str_repeat('*', strlen($value));
    return substr($value, 0, 2) . str_repeat('*', max(4, strlen($value)-4)) . substr($value, -2);
}
function ao_dna_library_path() { return dirname(__DIR__) . '/Services/domainnameapi/dna.php'; }
function ao_dna_client($bundle) {
    if (!file_exists(ao_dna_library_path())) throw new Exception('DomainNameAPI kütüphanesi bulunamadı: app/Services/domainnameapi');
    require_once ao_dna_library_path();
    [$user,$pass] = ao_dna_creds($bundle);
    if ($user === '' || $pass === '') throw new Exception('DomainNameAPI Reseller ID veya API Key boş.');
    $cfg = $bundle['config'] ?? []; $reg = $bundle['registrar'] ?? [];
    $test = (int)($reg['test_mode'] ?? 0) === 1;
    // DomainNameAPI test/canlı ayrımı registrarın Test Modu alanından yönetilir.
    // Eski config[test_mode] kayıtları canlı bağlantıyı yanlışlıkla OTE'ye yönlendirmesin diye burada kullanılmaz.
    return new \DomainNameApi\DomainNameAPI_PHPLibrary($user, $pass, $test);
}
function ao_scan_add(&$rows, $category, $name, $status, $detail='', $priority='medium', $recommendation='') {
    $rows[] = ['category'=>$category,'name'=>$name,'status'=>$status,'detail'=>$detail,'priority'=>$priority,'recommendation'=>$recommendation];
}
function ao_scan_score($rows) {
    if (!$rows) return 100;
    $penalty = 0;
    foreach ($rows as $r) {
        if ($r['status'] === 'fail') $penalty += $r['priority']==='high' ? 18 : 10;
        elseif ($r['status'] === 'warning') $penalty += $r['priority']==='high' ? 8 : 4;
        elseif ($r['status'] === 'demo') $penalty += 6;
    }
    return max(0, 100 - min(100, $penalty));
}
function ao_run_full_scan() {
    $rows = [];
    foreach (['customers','domains','orders','invoices','tickets','products','server_nodes','domain_registrars','registrar_configs','api_logs'] as $table) {
        try { $count = table_count($table); ao_scan_add($rows,'Veritabanı',$table,'pass',$count.' kayıt'); }
        catch(Throwable $e) { ao_scan_add($rows,'Veritabanı',$table,'fail',$e->getMessage(),'high','Kurulum SQL dosyasını ve import edilen SQL dosyalarını kontrol edin.'); }
    }
    foreach (['public/assets/css/areas/admin/base.css','public/assets/css/areas/admin/sidebar.css','public/assets/css/areas/customer/panel.css','public/themes/prism/assets/css/header.css','public/themes/prism/assets/css/footer.css','app/Views/admin/partials/header.php','app/Views/customer/partials/header.php','app/install.php'] as $file) {
        $ok = file_exists(__DIR__.'/'.$file);
        ao_scan_add($rows,'Dosya Sistemi',$file,$ok?'pass':'fail',$ok?'Var':'Eksik',$ok?'low':'high',$ok?'':'Kurulum paketine ekleyin.');
    }
    $phpExtensions = ['pdo_mysql','openssl','soap','json','mbstring'];
    foreach ($phpExtensions as $ext) {
        $ok = extension_loaded($ext);
        ao_scan_add($rows,'PHP Extension',$ext,$ok?'pass':'fail',$ok?'Aktif':'Kurulu değil',$ok?'low':'high',$ok?'':'Sunucuda php-'.$ext.' paketini etkinleştirin.');
    }
    try {
        $regs = db()->query('SELECT * FROM domain_registrars ORDER BY name')->fetchAll();
        if (!$regs) ao_scan_add($rows,'Registrar','Registrar kaydı','fail','Registrar kaydı bulunamadı.','high','DomainNameAPI ve diğer registrar kayıtlarını kurulum SQL/modül install SQL içine ekleyin.');
        foreach ($regs as $r) {
            $bundle = ao_registrar_bundle_by_id((int)$r['id']);
            $cfg = $bundle['config'] ?? [];
            $isActive = ($r['status'] ?? '') === 'active';
            ao_scan_add($rows,'Registrar',$r['name'].' durumu',$isActive?'pass':'warning',$r['status'] ?? '-', $isActive?'low':'medium','Kullanılacak registrar aktif olmalı.');
            if (stripos($r['slug'] ?? '', 'domainnameapi') !== false || stripos($r['module_name'] ?? '', 'domainnameapi') !== false) {
                $hasReseller = !empty($cfg['reseller_id']);
                $testMode = (int)($r['test_mode'] ?? 0) === 1;
                $hasApiKey = $testMode ? (!empty($cfg['ote_api_key']) || !empty($cfg['api_key'])) : !empty($cfg['api_key']);
                $hasCred = $hasReseller && $hasApiKey;
                $credDetail = $hasCred ? ('Reseller: '.ao_diag_mask($cfg['reseller_id']).' / API Key aktif') : 'Reseller ID veya API Key eksik';
                ao_scan_add($rows,'Registrar Diagnostics','DomainNameAPI kimlik bilgisi',$hasCred?'pass':'fail',$credDetail,'high','DomainNameAPI için Reseller ID, canlı API Key ve test kullanılıyorsa OTE API Key girilmelidir.');
                if ($hasCred && $isActive) {
                    $test = ao_registrar_api_call($bundle, 'test', $cfg['test_domain'] ?? 'example.com');
                    ao_scan_add($rows,'Registrar Diagnostics','DomainNameAPI bağlantı testi',$test['ok']?'pass':'fail',$test['message'] ?? '', $test['ok']?'low':'high', $test['ok']?'':'Endpoint, test modu, PHP SOAP ve kullanıcı/şifreyi kontrol edin.');
                    foreach (ao_dna_diagnostic_rows($bundle, $cfg['test_domain'] ?? 'example.com') as $dr) {
                        ao_scan_add($rows,'Registrar Diagnostics','DomainNameAPI '.$dr['name'],$dr['ok']?'pass':'fail',($dr['method']?('Method: '.$dr['method'].' | '):'').$dr['message'],$dr['ok']?'low':$dr['priority'],'API loglarında aynı method için ham yanıtı kontrol edin.');
                    }
                }
            }
        }
    } catch (Throwable $e) { ao_scan_add($rows,'Registrar Diagnostics','Registrar taraması','fail',$e->getMessage(),'high'); }
    $demoTerms = ['demo ödeme','kurulum paketinde ödeme demo','hazır alan','registrar api bağlanacak','simülasyon'];
    $paths = ['app/Views','index.php']; $found = [];
    foreach ($paths as $path) {
        $base = __DIR__.'/'.$path;
        if (is_file($base)) $files = [$base]; else { $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)); $files = iterator_to_array($it); }
        foreach ($files as $f) {
            $name = (string)$f; if (!preg_match('/\.(php|js|css)$/',$name)) continue;
            $txt = @file_get_contents($name); if ($txt === false) continue;
            foreach ($demoTerms as $term) if (stripos($txt,$term)!==false) { $found[] = str_replace(__DIR__.'/','',$name).' içinde "'.$term.'"'; break; }
        }
    }
    ao_scan_add($rows,'Demo / Placeholder','Demo içerik taraması',count($found)?'demo':'pass',count($found)?implode('; ',array_slice($found,0,12)):'Demo ifade bulunmadı.',count($found)?'medium':'low',count($found)?'Bu ifadeler canlı modda kaldırılmalı veya gerçek veriye bağlanmalı.':'');
    return ['rows'=>$rows,'score'=>ao_scan_score($rows),'generated_at'=>date('Y-m-d H:i:s')];
}
function ao_pdf_escape($s){ return str_replace(['\\','(',')'], ['\\\\','\\(','\\)'], (string)$s); }
function ao_build_simple_pdf($title, $lines) {
    $content = "BT /F1 16 Tf 50 800 Td (".ao_pdf_escape($title).") Tj ET\n";
    $y=770; $content .= "BT /F1 9 Tf 50 {$y} Td";
    foreach ($lines as $line) {
        $safe = mb_substr((string)$line,0,145);
        $content .= " (".ao_pdf_escape($safe).") Tj 0 -13 Td";
    }
    $content .= " ET";
    $objects=[];
    $objects[]="<< /Type /Catalog /Pages 2 0 R >>";
    $objects[]="<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
    $objects[]="<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>";
    $objects[]="<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
    $objects[]="<< /Length ".strlen($content)." >>\nstream\n$content\nendstream";
    $pdf="%PDF-1.4\n"; $offsets=[0];
    foreach($objects as $i=>$obj){ $offsets[$i+1]=strlen($pdf); $pdf.=($i+1)." 0 obj\n$obj\nendobj\n"; }
    $xref=strlen($pdf); $pdf.="xref\n0 ".(count($objects)+1)."\n0000000000 65535 f \n";
    for($i=1;$i<=count($objects);$i++) $pdf.=sprintf("%010d 00000 n \n",$offsets[$i]);
    $pdf.="trailer << /Size ".(count($objects)+1)." /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
    return $pdf;
}




// v7.5.8 DomainNameAPI Production + Ahost Domain Intelligence
function ao_is_domainnameapi_bundle($bundle) {
    $slug = strtolower((string)($bundle['registrar']['slug'] ?? $bundle['registrar']['module_name'] ?? ''));
    return str_contains($slug, 'domainnameapi') || str_contains($slug, 'dna');
}
function ao_dna_endpoint($bundle) {
    $cfg = $bundle['config'] ?? [];
    $reg = $bundle['registrar'] ?? [];
    // Kritik düzeltme: DomainNameAPI canlı/test ayrımı sadece domain_registrars.test_mode üzerinden yapılır.
    // registrar_configs.test_mode eski importlardan kalabilir ve canlı hesabı yanlışlıkla OTE'ye gönderebilir.
    $test = (int)($reg['test_mode'] ?? 0) === 1;
    $endpoint = trim((string)($cfg['api_endpoint'] ?? ''));
    // DomainNameAPI için kullanıcı endpoint'i boş bırakabilir. ?singlewsdl burada sadece WSDL için kullanılır,
    // servis endpoint'i gibi saklanmışsa otomatik temizlenir.
    if ($endpoint === '' || str_contains($endpoint, 'demo.ahostone') || str_contains($endpoint, 'domainnameapi.com')) {
        $endpoint = $test ? 'https://ote.domainresellerapi.com' : 'https://api.domainresellerapi.com';
    }
    $endpoint = preg_replace('/\?(wsdl|singlewsdl)$/i', '', $endpoint);
    return rtrim($endpoint, '/');
}
function ao_dna_creds($bundle) {
    $cfg = $bundle['config'] ?? [];
    $reg = $bundle['registrar'] ?? [];
    $test = (int)($reg['test_mode'] ?? 0) === 1;
    $reseller = trim((string)($cfg['reseller_id'] ?? ''));
    $apiKey = trim((string)($test ? ($cfg['ote_api_key'] ?? $cfg['api_key'] ?? '') : ($cfg['api_key'] ?? '')));
    return [$reseller, $apiKey];
}
function ao_arr($v) { return json_decode(json_encode($v), true) ?: []; }

function ao_dna_error_text($response) {
    $arr = is_array($response) ? $response : ao_arr($response);
    $parts = [];
    $fmt = function($v) {
        if (is_array($v) || is_object($v)) return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        return (string)$v;
    };
    $code = ao_find_deep($arr, ['Code','ErrorCode','errorCode','code']);
    $msg = ao_find_deep($arr, ['Message','OperationMessage','error','Error','message']);
    $details = ao_find_deep($arr, ['Details','Detail','detail','description','Description']);
    if ($code !== null && $code !== '') $parts[] = 'Kod: '.$fmt($code);
    if ($msg !== null && $msg !== '') $parts[] = 'Mesaj: '.$fmt($msg);
    if ($details !== null && $details !== '') $parts[] = 'Detay: '.mb_substr($fmt($details), 0, 260);
    if (!$parts && isset($arr['error'])) {
        if (is_array($arr['error'])) $parts[] = json_encode($arr['error'], JSON_UNESCAPED_UNICODE);
        else $parts[] = (string)$arr['error'];
    }
    if (!$parts) $parts[] = 'DomainNameAPI hata döndürdü; API log payload içinde ham yanıtı kontrol edin.';
    return implode(' | ', $parts);
}
function ao_dna_ok($response, $method = '') {
    $arr = is_array($response) ? $response : ao_arr($response);
    $result = strtolower((string)($arr['result'] ?? ''));
    if ($result === 'ok' || $result === 'success') return true;
    if ($result && in_array($result, ['error','fail','failed','false','0'], true)) return false;
    if (isset($arr['error']) || isset($arr['Code']) || isset($arr['ErrorCode'])) return false;
    $method = strtolower((string)$method);
    if ($method === 'checkavailability') {
        return ao_find_deep($arr, ['Status','status','Availability','availability']) !== null;
    }
    if ($method === 'getdetails') {
        return ao_find_deep($arr, ['DomainName','domainName','Status','status','AuthCode','authCode','EppCode','eppCode']) !== null;
    }
    if ($method === 'getresellerdetails') {
        return !empty($arr) && ao_find_deep($arr, ['ResellerId','ResellerID','Currency','Balance','Name','Email','data']) !== null;
    }
    return !empty($arr);
}
function ao_dna_call($bundle, $method, $request = []) {
    $method = trim((string)$method);
    $safeRequest = $request;
    unset($safeRequest['Password'], $safeRequest['api_password']);
    try {
        $client = ao_dna_client($bundle);
        $realMethod = $method;
        if ($method === 'getResellerDetails') {
            $realMethod = 'getResellerDetails';
            $response = $client->getResellerDetails();
        } elseif ($method === 'checkAvailability') {
            $realMethod = 'checkAvailability';
            $domains = $request['DomainNameList'] ?? ['example'];
            $tlds = $request['TldList'] ?? ['com'];
            $response = $client->checkAvailability($domains, $tlds, (int)($request['Period'] ?? 1), $request['Commad'] ?? 'create');
        } elseif ($method === 'getDetails') {
            $realMethod = 'getDetails';
            $response = $client->getDetails($request['DomainName'] ?? 'example.com');
        } elseif ($method === 'renew') {
            $realMethod = 'renew';
            $response = $client->renew($request['DomainName'] ?? 'example.com', (int)($request['Period'] ?? 1));
        } elseif ($method === 'transfer') {
            $realMethod = 'transfer';
            $response = $client->transfer($request['DomainName'] ?? 'example.com', $request['EppCode'] ?? '', (int)($request['Period'] ?? 1));
        } elseif ($method === 'enableTheftProtectionLock') {
            $realMethod = 'enableTheftProtectionLock';
            $response = $client->enableTheftProtectionLock($request['DomainName'] ?? 'example.com');
        } elseif ($method === 'disableTheftProtectionLock') {
            $realMethod = 'disableTheftProtectionLock';
            $response = $client->disableTheftProtectionLock($request['DomainName'] ?? 'example.com');
        } else {
            $response = $client->{$method}();
        }
        $ok = ao_dna_ok($response, $realMethod);
        $body = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        $message = $ok ? 'DomainNameAPI yanıt verdi.' : ao_dna_error_text($response);
        ao_log_simple('domainnameapi', $realMethod, $ok ? 'success' : 'error', $message, json_encode(['request'=>$safeRequest, 'response'=>$response], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
        return ['ok'=>$ok,'code'=>200,'body'=>$body,'message'=>$message, 'method'=>$realMethod];
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        ao_log_simple('domainnameapi', $method, 'error', $msg, json_encode(['request'=>$safeRequest, 'exception'=>get_class($e)], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
        return ['ok'=>false,'code'=>500,'body'=>json_encode(['error'=>$msg,'method'=>$method], JSON_UNESCAPED_UNICODE),'message'=>$msg, 'method'=>$method];
    }
}

function ao_dna_diagnostic_rows($bundle, $domain='') {
    $rows = [];
    $cfg = $bundle['config'] ?? [];
    $domain = ahost_domain_clean($domain ?: ($cfg['test_domain'] ?? 'example.com'));
    $add = function($name, $res, $priority='high') use (&$rows) {
        $rows[] = ['name'=>$name, 'ok'=>!empty($res['ok']), 'message'=>$res['message'] ?? '', 'priority'=>$priority, 'method'=>$res['method'] ?? ''];
    };
    try {
        [$u,$p] = ao_dna_creds($bundle);
        $rows[] = ['name'=>'Kimlik bilgisi', 'ok'=>($u!=='' && $p!==''), 'message'=>($u!=='' && $p!=='') ? 'Reseller ID ve API Key girilmiş.' : 'Reseller ID veya API Key boş.', 'priority'=>'high', 'method'=>'credentials'];
        if ($u === '' || $p === '') return $rows;
        $add('Reseller Details', ao_dna_call($bundle, 'getResellerDetails'), 'high');
        $tld = ao_domain_tld($domain); $label = $tld ? substr($domain, 0, -strlen($tld)) : $domain;
        $add('Domain Check', ao_dna_call($bundle, 'checkAvailability', ['DomainNameList'=>[$label], 'TldList'=>[ltrim($tld,'.') ?: 'com'], 'Period'=>1, 'Commad'=>'create']), 'high');
        $details = ao_dna_call($bundle, 'getDetails', ['DomainName'=>$domain]);
        $add('WHOIS/GetDetails', $details, 'high');
        $arr = json_decode($details['body'] ?? '[]', true) ?: [];
        $epp = ao_find_deep($arr, ['AuthCode','Auth','EppCode','eppCode','authCode']);
        $rows[] = ['name'=>'EPP/Auth Code', 'ok'=>!empty($epp), 'message'=>!empty($epp) ? 'EPP/Auth Code döndü.' : 'GetDetails yanıtında EPP/Auth Code bulunamadı. Domain registrar panelinde EPP veriyorsa adapter alan eşlemesi kontrol edilmeli.', 'priority'=>'high', 'method'=>'getDetails'];
    } catch (Throwable $e) {
        $rows[] = ['name'=>'DomainNameAPI Diagnostics', 'ok'=>false, 'message'=>$e->getMessage(), 'priority'=>'high', 'method'=>'diagnostics'];
    }
    return $rows;
}

function ao_dna_action_call($bundle, $action, $domain='', $extra=[]) {
    $domain = ahost_domain_clean($domain ?: ($bundle['config']['test_domain'] ?? 'example.com'));
    if ($action === 'test') return ao_dna_call($bundle, 'getResellerDetails', ['CurrencyId'=>1]);
    if ($action === 'check') {
        $tld = ao_domain_tld($domain); $label = $tld ? substr($domain, 0, -strlen($tld)) : $domain;
        return ao_dna_call($bundle, 'checkAvailability', ['DomainNameList'=>[$label], 'TldList'=>[ltrim($tld,'.')], 'Period'=>(int)($extra['period'] ?? 1), 'Commad'=>$extra['command'] ?? 'create']);
    }
    if ($action === 'whois' || $action === 'epp') return ao_dna_call($bundle, 'getDetails', ['DomainName'=>$domain]);
    if ($action === 'renew') return ao_dna_call($bundle, 'renew', ['DomainName'=>$domain, 'Period'=>(int)($extra['period'] ?? 1)]);
    if ($action === 'transfer') return ao_dna_call($bundle, 'transfer', ['DomainName'=>$domain, 'EppCode'=>$extra['epp'] ?? '', 'Period'=>(int)($extra['period'] ?? 1)]);
    if ($action === 'nameserver') return ao_dna_call($bundle, 'getDetails', ['DomainName'=>$domain]);
    if ($action === 'lock') return ao_dna_call($bundle, !empty($extra['unlock']) ? 'disableTheftProtectionLock' : 'enableTheftProtectionLock', ['DomainName'=>$domain]);
    return ao_dna_call($bundle, 'getDetails', ['DomainName'=>$domain]);
}
function ao_find_deep($arr, $keys) {
    $out = [];
    $walk = function($v) use (&$walk,&$out,$keys){
        if (!is_array($v)) return;
        foreach ($v as $k=>$val) {
            foreach ($keys as $needle) if (strtolower((string)$k) === strtolower($needle) && $val !== '' && $val !== null) $out[] = $val;
            if (is_array($val)) $walk($val);
        }
    };
    $walk($arr); return $out[0] ?? null;
}
function ao_whois_server_for_tld($tld) {
    $map = ['com'=>'whois.verisign-grs.com','net'=>'whois.verisign-grs.com','org'=>'whois.pir.org','info'=>'whois.afilias.net','biz'=>'whois.biz','io'=>'whois.nic.io','co'=>'whois.nic.co','tr'=>'whois.trabis.gov.tr'];
    return $map[strtolower(ltrim($tld,'.'))] ?? 'whois.iana.org';
}
function ao_raw_whois($domain) {
    $tld = ltrim(ao_domain_tld($domain), '.'); $server = ao_whois_server_for_tld($tld);
    $fp = @fsockopen($server, 43, $errno, $errstr, 5);
    if (!$fp) return '';
    fwrite($fp, $domain."\r\n"); $out=''; while(!feof($fp)) $out .= fgets($fp, 2048); fclose($fp);
    if ($server === 'whois.iana.org' && preg_match('/refer:\s*(\S+)/i', $out, $m)) {
        $fp=@fsockopen(trim($m[1]),43,$errno,$errstr,5); if($fp){ fwrite($fp,$domain."\r\n"); $out=''; while(!feof($fp)) $out .= fgets($fp,2048); fclose($fp); }
    }
    return $out;
}
function ao_parse_whois_text($txt) {
    $pick=function($patterns) use ($txt){ foreach((array)$patterns as $p) if(preg_match($p,$txt,$m)) return trim($m[1]); return ''; };
    return [
        'Registrar'=>$pick(['/Registrar:\s*(.+)/i','/Sponsoring Registrar:\s*(.+)/i']),
        'Kayıt Tarihi'=>$pick(['/Creation Date:\s*(.+)/i','/Created On:\s*(.+)/i','/Registered on:\s*(.+)/i']),
        'Son Güncelleme'=>$pick(['/Updated Date:\s*(.+)/i','/Last Updated On:\s*(.+)/i']),
        'Bitiş Tarihi'=>$pick(['/Registry Expiry Date:\s*(.+)/i','/Expiration Date:\s*(.+)/i','/Expiry Date:\s*(.+)/i']),
        'Domain Durumu'=>$pick(['/Domain Status:\s*(.+)/i','/Status:\s*(.+)/i']),
        'DNSSEC'=>$pick('/DNSSEC:\s*(.+)/i'),
        'IANA ID'=>$pick('/Registrar IANA ID:\s*(.+)/i'),
    ];
}
function ao_page_basic_analysis($domain) {
    $url = 'https://' . $domain; $html = @file_get_contents($url, false, stream_context_create(['http'=>['timeout'=>5,'ignore_errors'=>true,'user_agent'=>'AhostOneBot/1.0'],'ssl'=>['verify_peer'=>false,'verify_peer_name'=>false]]));
    if (!$html) { $url = 'http://' . $domain; $html = @file_get_contents($url, false, stream_context_create(['http'=>['timeout'=>5,'ignore_errors'=>true,'user_agent'=>'AhostOneBot/1.0']])); }
    $title=''; $desc=''; $h1=0;
    if ($html) { if(preg_match('/<title[^>]*>(.*?)<\/title>/is',$html,$m)) $title=trim(strip_tags($m[1])); if(preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)/i',$html,$m)) $desc=trim($m[1]); $h1=preg_match_all('/<h1\b/i',$html); }
    return ['reachable'=>(bool)$html,'title'=>$title,'description'=>$desc,'h1_count'=>$h1,'html_bytes'=>$html?strlen($html):0];
}
function ao_domain_valuation_score($domain, $whoisRows=[], $sslRows=[], $dnsCount=0, $seo=[]) {
    $base = 1200; $len = strlen(preg_replace('/\..+$/','',$domain));
    $score = 50;
    if ($len <= 6) $score += 20; elseif ($len <= 10) $score += 12; else $score += 4;
    if (str_ends_with($domain,'.com')) $score += 18; elseif (preg_match('/\.(net|org|io|co)$/',$domain)) $score += 10;
    if (!preg_match('/[-0-9]/',$domain)) $score += 8;
    if (!empty($sslRows['SSL Durumu']) && $sslRows['SSL Durumu']==='Aktif') $score += 6;
    if ($dnsCount > 0) $score += 6;
    if (!empty($seo['title'])) $score += 5;
    $score = max(1,min(100,$score));
    $value = (int)round($base * ($score/50) * max(1, 14 / max(4,$len)));
    return ['score'=>$score,'value'=>$value,'seo_score'=>min(100,40+(!empty($seo['title'])*20)+(!empty($seo['description'])*20)+min(20,(int)$seo['h1_count']*10))];
}

// v7.3.0 Domain Center UX Pro - popup based WHOIS/DNS/SSL/valuation lookup API
function ahost_domain_clean($domain) {
    $domain = strtolower(trim((string)$domain));
    $domain = preg_replace('#^https?://#', '', $domain);
    $domain = preg_replace('#/.*$#', '', $domain);
    $domain = preg_replace('/[^a-z0-9\-.]/', '', $domain);
    return trim($domain, '.');
}
function ahost_domain_valid($domain) { return (bool)preg_match('/^[a-z0-9][a-z0-9\-]{0,62}(\.[a-z0-9][a-z0-9\-]{0,62})+$/', $domain); }
function ahost_domain_search_term($value) {
    $value = ahost_domain_clean($value);
    $value = preg_replace('/[^a-z0-9\-.]/', '', $value);
    $value = trim($value, '.-');
    if ($value === '') return '';
    if (str_contains($value, '.')) {
        $parts = explode('.', $value);
        return preg_replace('/[^a-z0-9-]/', '', (string)$parts[0]);
    }
    return preg_replace('/[^a-z0-9-]/', '', $value);
}
function ahost_domain_has_tld($value) {
    return ahost_domain_valid(ahost_domain_clean($value));
}
function ahost_modal_table($rows) {
    $html = '<div class="ao-modal-table ao-tool-row-grid">';
    foreach ($rows as $label => $value) {
        $value = trim((string)$value);
        if ($value === '') $value = '-';
        $html .= '<div class="ao-tool-row"><span>'.e($label).'</span><b>'.e($value).'</b></div>';
    }
    return $html.'</div>';
}
function ahost_whois_premium_table($rows) {
    $html = '<div class="ao-whois-premium-list">';
    foreach ($rows as $label => $value) {
        $value = trim((string)$value);
        if ($value === '') $value = '-';
        $html .= '<div class="ao-whois-premium-row"><span>'.e($label).'</span><strong>'.e($value).'</strong></div>';
    }
    return $html.'</div>';
}
function ahost_domain_quote_price_html(array $quote, string $suffix = ''): string {
    $amount = (float)($quote['sale_price'] ?? 0);
    $currency = strtoupper(trim((string)($quote['rule']['currency'] ?? $quote['currency'] ?? admin_setting('default_currency', 'TRY'))));
    if ($currency === '') $currency = 'TRY';
    if (function_exists('ao_price_html')) {
        return ao_price_html($amount, $currency) . e($suffix);
    }
    return e(number_format($amount, 2, ',', '.') . ' ' . $currency . $suffix);
}
function ahost_domain_admin_tld_quote(string $domain, string $action = 'register'): array {
    $domain = ahost_domain_clean($domain);
    $tld = ao_domain_tld($domain);
    $plainTld = ltrim(strtolower($tld), '.');
    $column = $action === 'transfer' ? 'transfer_price' : ($action === 'renew' ? 'renew_price' : 'register_price');
    try {
        $q = db()->prepare("SELECT tld, register_price, transfer_price, renew_price, currency, registrar_slug FROM tld_pricing WHERE LOWER(TRIM(LEADING '.' FROM tld))=? AND is_active=1 ORDER BY CASE WHEN registrar_slug='domainnameapi' THEN 0 ELSE 1 END, id ASC LIMIT 1");
        $q->execute([$plainTld]);
        if ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            $amount = (float)($row[$column] ?? 0);
            if ($amount > 0) {
                return [
                    'domain' => $domain,
                    'tld' => '.' . $plainTld,
                    'action' => $action,
                    'sale_price' => $amount,
                    'registrar_cost' => $amount,
                    'currency' => strtoupper((string)($row['currency'] ?? admin_setting('default_currency', 'TRY') ?: 'TRY')),
                    'selected_registrar' => (string)($row['registrar_slug'] ?? ''),
                    'source' => 'tld_pricing',
                    'rule' => ['currency' => strtoupper((string)($row['currency'] ?? admin_setting('default_currency', 'TRY') ?: 'TRY'))],
                ];
            }
        }
    } catch (Throwable $e) {
        error_log('[ao domain quote] '.$e->getMessage());
    }
    return ao_smart_domain_quote($domain, $action);
}
function ahost_domain_rdap_lookup(string $domain): array {
    $domain = ahost_domain_clean($domain);
    if (!ahost_domain_valid($domain)) return ['rows'=>[], 'raw'=>''];
    $plainTld = ltrim(strtolower(ao_domain_tld($domain)), '.');
    $urls = ['https://rdap.org/domain/' . rawurlencode($domain)];
    if (in_array($plainTld, ['com', 'net'], true)) {
        $urls[] = 'https://rdap.verisign.com/' . $plainTld . '/v1/domain/' . strtoupper(rawurlencode($domain));
    } elseif ($plainTld === 'org') {
        $urls[] = 'https://rdap.publicinterestregistry.org/rdap/domain/' . rawurlencode($domain);
    } elseif ($plainTld === 'info') {
        $urls[] = 'https://rdap.afilias.net/rdap/info/domain/' . rawurlencode($domain);
    }
    try {
        $res = ['ok'=>false, 'body'=>''];
        foreach (array_values(array_unique($urls)) as $url) {
            $res = function_exists('ao_http_request')
                ? ao_http_request('GET', $url, ['Accept: application/rdap+json, application/json', 'User-Agent: AhostOne/1.0'], null, 8)
                : ['ok'=>false, 'body'=>''];
            if (!empty($res['ok']) && trim((string)($res['body'] ?? '')) !== '') break;
        }
        if (empty($res['ok']) || trim((string)($res['body'] ?? '')) === '') return ['rows'=>[], 'raw'=>''];
        $json = json_decode((string)$res['body'], true);
        if (!is_array($json)) return ['rows'=>[], 'raw'=>''];
        $events = [];
        foreach (($json['events'] ?? []) as $event) {
            $action = strtolower((string)($event['eventAction'] ?? ''));
            if ($action !== '' && !empty($event['eventDate'])) $events[$action] = (string)$event['eventDate'];
        }
        $registrar = '';
        foreach (($json['entities'] ?? []) as $entity) {
            $roles = array_map('strtolower', (array)($entity['roles'] ?? []));
            if (in_array('registrar', $roles, true)) {
                $registrar = ahost_rdap_vcard_value($entity, 'fn') ?: ahost_rdap_vcard_value($entity, 'org');
                break;
            }
        }
        $rows = [
            'Domain' => strtoupper((string)($json['ldhName'] ?? $domain)),
            'Registrar' => $registrar,
            'Kayıt Tarihi' => $events['registration'] ?? $events['registered'] ?? '',
            'Son Güncelleme' => $events['last changed'] ?? $events['last update of rdap database'] ?? '',
            'Bitiş Tarihi' => $events['expiration'] ?? '',
            'Domain Durumu' => implode(' / ', array_slice((array)($json['status'] ?? []), 0, 4)),
            'DNSSEC' => !empty($json['secureDNS']) ? (!empty($json['secureDNS']['delegationSigned']) ? 'Aktif' : 'Pasif') : '',
            'IANA ID' => '',
            'Kaynak' => 'Canlı RDAP',
        ];
        $nameServers = [];
        foreach (($json['nameservers'] ?? []) as $ns) {
            if (!empty($ns['ldhName'])) $nameServers[] = (string)$ns['ldhName'];
        }
        if ($nameServers) $rows['_nameservers'] = $nameServers;
        return ['rows'=>array_filter($rows, fn($v) => $v !== '' && $v !== null), 'raw'=>json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
    } catch (Throwable $e) {
        error_log('[ao rdap] '.$e->getMessage());
        return ['rows'=>[], 'raw'=>''];
    }
}
function ahost_rdap_vcard_value(array $entity, string $key): string {
    $vcard = $entity['vcardArray'][1] ?? [];
    foreach ((array)$vcard as $row) {
        if (is_array($row) && strtolower((string)($row[0] ?? '')) === strtolower($key)) {
            return trim((string)($row[3] ?? ''));
        }
    }
    return '';
}
function ao_whois_status_display_v2632($status) {
    $status = trim((string)$status);
    if ($status === '') return 'Bilinmiyor';
    $status = preg_replace('/\s+https?:\/\/\S+/i', ' ', $status);
    $status = preg_replace('/\s+/', ' ', (string)$status);
    $clean = trim((string)$status);
    if ($clean === '') return 'Bilinmiyor';
    $lower = strtolower($clean);
    $flags = [];
    if (str_contains($lower, 'active') || str_contains($lower, 'ok')) $flags[] = 'Aktif';
    if (str_contains($lower, 'transferprohibited')) $flags[] = 'Transfer kilidi aktif';
    if (str_contains($lower, 'deleteprohibited')) $flags[] = 'Silme kilidi aktif';
    if (str_contains($lower, 'updateprohibited')) $flags[] = 'Güncelleme kilidi aktif';
    if (str_contains($lower, 'renewprohibited')) $flags[] = 'Yenileme kilidi aktif';
    if (str_contains($lower, 'hold')) $flags[] = 'Beklemede';
    if (str_contains($lower, 'pending')) $flags[] = 'İşlem bekliyor';
    $flags = array_values(array_unique($flags));
    if ($flags) return implode(' / ', array_slice($flags, 0, 3));
    if (preg_match('/client|server/i', $clean)) return 'Kayitli / koruma aktif';
    return $clean;
}
function ao_site_tools_ensure_schema_v2632() {
    try {
        db()->exec("CREATE TABLE IF NOT EXISTS site_tool_usage (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(80) NOT NULL,
            customer_id INT NULL,
            tool_key VARCHAR(80) NOT NULL,
            target VARCHAR(190) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY ip_tool_date (ip_address, tool_key, created_at),
            KEY customer_id (customer_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    } catch(Throwable $e) { error_log('[ao tools] '.$e->getMessage()); }
}
function ao_site_tools_paid_customer_v2632() {
    $customer = function_exists('current_customer') ? current_customer() : null;
    if (!$customer) return false;
    $cid = (int)($customer['id'] ?? 0);
    if ($cid <= 0) return false;
    try {
        $q = db()->prepare("SELECT COUNT(*) FROM services WHERE customer_id=? AND status IN ('active','Active','Aktif','paid','completed') LIMIT 1");
        $q->execute([$cid]);
        if ((int)$q->fetchColumn() > 0) return true;
    } catch(Throwable $e) {}
    try {
        $q = db()->prepare("SELECT COUNT(*) FROM orders WHERE customer_id=? AND status IN ('paid','completed','active','approved') LIMIT 1");
        $q->execute([$cid]);
        return (int)$q->fetchColumn() > 0;
    } catch(Throwable $e) {}
    return false;
}
function ao_site_tools_quota_v2632($toolKey='site-tools') {
    ao_site_tools_ensure_schema_v2632();
    if (ao_site_tools_paid_customer_v2632()) return ['allowed'=>true,'used'=>0,'limit'=>0,'paid'=>true];
    $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1')) ?: '127.0.0.1';
    $limit = max(1, (int)admin_setting('site_tools_free_daily_limit', '3'));
    try {
        $q = db()->prepare('SELECT COUNT(*) FROM site_tool_usage WHERE ip_address=? AND created_at>=DATE_SUB(NOW(), INTERVAL 24 HOUR)');
        $q->execute([$ip]);
        $used = (int)$q->fetchColumn();
        return ['allowed'=>$used < $limit, 'used'=>$used, 'limit'=>$limit, 'paid'=>false];
    } catch(Throwable $e) {
        return ['allowed'=>true,'used'=>0,'limit'=>$limit,'paid'=>false];
    }
}
function ao_site_tools_record_v2632($toolKey, $target='') {
    ao_site_tools_ensure_schema_v2632();
    if (ao_site_tools_paid_customer_v2632()) return;
    try {
        $customer = function_exists('current_customer') ? current_customer() : null;
        db()->prepare('INSERT INTO site_tool_usage(ip_address,customer_id,tool_key,target) VALUES(?,?,?,?)')
            ->execute([
                trim((string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1')) ?: '127.0.0.1',
                $customer ? (int)($customer['id'] ?? 0) : null,
                preg_replace('/[^a-z0-9_-]/i', '', (string)$toolKey) ?: 'site-tools',
                mb_substr((string)$target, 0, 190, 'UTF-8')
            ]);
    } catch(Throwable $e) { error_log('[ao tools] '.$e->getMessage()); }
}
if (!function_exists('ao_repair_tr_mojibake_v270')) {
    function ao_repair_tr_mojibake_v270($value) {
        if (is_array($value)) {
            foreach ($value as $k => $v) $value[$k] = ao_repair_tr_mojibake_v270($v);
            return $value;
        }
        if (!is_string($value) || $value === '') return $value;
        return strtr($value, [
            'Ã¼'=>'ü','Ãœ'=>'Ü','Ã¶'=>'ö','Ã–'=>'Ö','Ã§'=>'ç','Ã‡'=>'Ç',
            'ÄŸ'=>'ğ','Äž'=>'Ğ','Ä±'=>'ı','Ä°'=>'İ','ÅŸ'=>'ş','Åž'=>'Ş',
            'â‚º'=>'₺','â€™'=>'’','â€œ'=>'“','â€'=>'”','â€“'=>'-','â€”'=>'-',
            'ï¿½?'=>'Ş','Ãœlke / ï¿½?ehir'=>'Ülke / Şehir',
        ]);
    }
}
function ahost_domain_lookup_html($tool, $domain) {
    $domain = ahost_domain_clean($domain);
    if (!ahost_domain_valid($domain)) return ['title'=>'Geçersiz domain','html'=>'<div class="ao-modal-error">Lütfen geçerli bir domain yazın. Örnek: ahostone.com</div>'];
    $dbDomain = null; $dnsRows = []; $ns = null; $contact = null; $customerContact = [];
    try {
        $q=db()->prepare('SELECT * FROM domains WHERE domain_name=? LIMIT 1'); $q->execute([$domain]); $dbDomain=$q->fetch() ?: null;
        if ($dbDomain) {
            $q=db()->prepare('SELECT * FROM domain_dns_records WHERE domain_id=? ORDER BY record_type, host'); $q->execute([$dbDomain['id']]); $dnsRows=$q->fetchAll();
            $q=db()->prepare('SELECT * FROM domain_nameservers WHERE domain_id=? LIMIT 1'); $q->execute([$dbDomain['id']]); $ns=$q->fetch() ?: null;
            $q=db()->prepare('SELECT * FROM domain_contacts WHERE domain_id=? LIMIT 1'); $q->execute([$dbDomain['id']]); $contact=$q->fetch() ?: null;
            if (!empty($dbDomain['customer_id'])) {
                $q=db()->prepare('SELECT * FROM customers WHERE id=? LIMIT 1'); $q->execute([(int)$dbDomain['customer_id']]); $customerContact=$q->fetch() ?: [];
            }
        }
    } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    if ($tool === 'whois') {
        $rawWhois = '';
        $rdapNameServers = [];
        $rows = [
            'Domain' => $domain,
            'Registrar' => $dbDomain['registrar'] ?? 'DomainNameAPI / yapılandırılacak registrar',
            'Kayıt Tarihi' => $dbDomain['registration_date'] ?? 'Registrar yanıtı bekleniyor',
            'Son Güncelleme' => date('Y-m-d'),
            'Bitiş Tarihi' => $dbDomain['expiry_date'] ?? 'Registrar yanıtı bekleniyor',
            'Domain Durumu' => $dbDomain['status'] ?? 'unknown',
            'Registrar Lock' => isset($dbDomain['lock_status']) ? ((int)$dbDomain['lock_status'] ? 'Kilitli' : 'Açık') : 'Bilinmiyor',
            'Oto Yenileme' => isset($dbDomain['auto_renew']) ? ((int)$dbDomain['auto_renew'] ? 'Açık' : 'Kapalı') : 'Bilinmiyor',
            'DNSSEC' => 'Bilinmiyor',
            'IANA ID' => 'Bilinmiyor'
        ];
        try {
            $bundle = $dbDomain ? ao_domain_registrar_bundle($dbDomain) : ao_registrar_bundle('domainnameapi');
            if ($bundle && (($bundle['registrar']['status'] ?? '') === 'active')) {
                $api = ao_registrar_api_call($bundle, 'whois', $domain);
                if ($api['ok']) {
                    $rawWhois = is_string($api['body'] ?? null) ? (string)$api['body'] : json_encode($api['body'] ?? [], JSON_UNESCAPED_UNICODE);
                    $apiRows = ao_extract_whois_rows_from_response($api['body']);
                    foreach ($apiRows as $k=>$v) if ($v !== '' && $v !== null) $rows[$k] = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v;
                    $rows['Kaynak'] = 'Registrar API';
                } else { $rows['Kaynak'] = $dbDomain ? 'Yerel domain kaydı / registrar API cevap vermedi' : 'Canlı DNS / registrar API cevap vermedi'; }
            }
        } catch (Throwable $e) { $rows['Kaynak'] = $dbDomain ? 'Yerel domain kaydı' : 'Canlı DNS'; }
        if (($rows['Kaynak'] ?? '') !== 'Registrar API') {
            $rawWhois = ao_raw_whois($domain);
            if ($rawWhois !== '') {
                foreach (ao_parse_whois_text($rawWhois) as $k=>$v) if ($v !== '') $rows[$k] = $v;
                $rows['Kaynak'] = 'Canlı WHOIS port 43';
            } else {
                $rdap = ahost_domain_rdap_lookup($domain);
                if (!empty($rdap['rows'])) {
                    foreach ($rdap['rows'] as $k=>$v) {
                        if ($k === '_nameservers') continue;
                        if ($v !== '' && $v !== null) $rows[$k] = is_array($v) ? implode(', ', $v) : $v;
                    }
                    $rawWhois = (string)($rdap['raw'] ?? '');
                    if (!empty($rdap['rows']['_nameservers'])) {
                        $rdapNameServers = (array)$rdap['rows']['_nameservers'];
                    }
                }
            }
        }
        $nameServers = [];
        if ($ns) {
            foreach (['ns1','ns2','ns3','ns4'] as $nsKey) {
                if (!empty($ns[$nsKey])) $nameServers[] = (string)$ns[$nsKey];
            }
        }
        if (!empty($dbDomain['nameservers'])) {
            foreach (preg_split('/[\r\n,;]+/', (string)$dbDomain['nameservers']) ?: [] as $server) {
                $server = trim((string)$server);
                if ($server !== '') $nameServers[] = $server;
            }
        }
        if (!empty($rdapNameServers)) {
            foreach ($rdapNameServers as $server) $nameServers[] = trim((string)$server);
        }
        if ($rawWhois !== '' && preg_match_all('/(?:Name Server|Nameserver|nserver):\s*([^\r\n]+)/i', $rawWhois, $matches)) {
            foreach ($matches[1] as $server) $nameServers[] = trim((string)$server);
        }
        if (!$nameServers && function_exists('dns_get_record')) {
            $liveNs = @dns_get_record($domain, DNS_NS);
            if (is_array($liveNs)) {
                foreach ($liveNs as $record) {
                    $server = trim((string)($record['target'] ?? $record['host'] ?? ''));
                    if ($server !== '' && strcasecmp($server, $domain) !== 0) $nameServers[] = $server;
                }
            }
        }
        $nameServers = array_values(array_unique(array_filter(array_map(
            fn($server) => strtolower(trim((string)$server, " .\t\n\r\0\x0B")),
            $nameServers
        ))));
        $contactName = trim((string)($contact['full_name'] ?? $contact['registrant_name'] ?? ''));
        $contactName = $contactName ?: trim((string)(($customerContact['first_name'] ?? '').' '.($customerContact['last_name'] ?? '')));
        $contactCompany = trim((string)($contact['company'] ?? $contact['registrant_organization'] ?? $customerContact['company'] ?? ''));
        $contactEmail = trim((string)($contact['email'] ?? $contact['registrant_email'] ?? $customerContact['email'] ?? ''));
        $contactPhone = trim((string)($contact['phone'] ?? $contact['registrant_phone'] ?? $customerContact['phone'] ?? ''));
        $contactCity = trim((string)($contact['city'] ?? $contact['registrant_city'] ?? $customerContact['city'] ?? ''));
        $contactCountry = trim((string)($contact['country'] ?? $contact['registrant_country'] ?? $customerContact['country'] ?? ''));
        $whoisSignalText = (string)($rows['Domain Durumu'] ?? '').' '.$rawWhois;
        $protectionRows = [
            'Transfer Koruması' => preg_match('/transferprohibited|clienttransferprohibited|servertransferprohibited/i', $whoisSignalText) ? 'Aktif' : 'Yok',
            'Silme Koruması' => preg_match('/deleteprohibited|clientdeleteprohibited|serverdeleteprohibited/i', $whoisSignalText) ? 'Aktif' : 'Yok',
            'Güncelleme Koruması' => preg_match('/updateprohibited|clientupdateprohibited|serverupdateprohibited/i', $whoisSignalText) ? 'Aktif' : 'Yok',
            'WHOIS Gizliliği' => preg_match('/privacy|redacted|gdpr|data protected|whoisguard|contact privacy|private registration/i', $rawWhois) ? 'Var' : 'Yok',
        ];
        $suggestions = [];
        foreach (ao_domain_suggestion_candidates($domain, 3) as $candidate) {
            if ($candidate === $domain) continue;
            $sr = ao_domain_availability($candidate);
            $quote = ahost_domain_admin_tld_quote($candidate, 'register');
            $suggestions[] = [
                'domain' => $candidate,
                'available' => !empty($sr['available']),
                'price_html' => ahost_domain_quote_price_html($quote),
            ];
        }
        $transferQuote = ahost_domain_admin_tld_quote($domain, 'transfer');
        $isLocalDomain = !empty($dbDomain);
        $status = trim((string)($rows['Domain Durumu'] ?? 'unknown'));
        $statusDisplay = ao_whois_status_display_v2632($status);
        $statusClass = preg_match('/active|ok|client/i', $status) ? 'is-active' : (preg_match('/pending|bekleniyor|unknown/i', $status) ? 'is-waiting' : 'is-muted');
        $expiryText = trim((string)($rows['Bitiş Tarihi'] ?? $rows['Bitiş Tarihi'] ?? ''));
        $heroRows = [
            'Domain' => $rows['Domain'] ?? $domain,
            'Kayıt Durumu' => $rows['Domain Durumu'] ?? $statusDisplay,
            'Kayıt Tarihi' => $rows['Kayıt Tarihi'] ?? $rows['Kayıt Tarihi'] ?? '-',
            'Bitiş Tarihi' => $rows['Bitiş Tarihi'] ?? $rows['Bitiş Tarihi'] ?? '-',
        ];
        $techRows = [
            'Son Güncelleme' => $rows['Son Güncelleme'] ?? $rows['Son Güncelleme'] ?? '-',
            'Registrar Lock' => $rows['Registrar Lock'] ?? '-',
            'Oto Yenileme' => $rows['Oto Yenileme'] ?? '-',
            'DNSSEC' => $rows['DNSSEC'] ?? '-',
            'IANA ID' => $rows['IANA ID'] ?? '-',
            'Kaynak' => $rows['Kaynak'] ?? '-',
        ];
        $html = '<div class="ao-whois-premium">';
        $html .= '<div class="ao-whois-premium-hero"><div><span class="ao-whois-kicker">WHOIS Detay Raporu</span><h3>'.e($domain).'</h3><p>Kayıt durumu, registrar, nameserver, koruma ve sahiplik bilgileri tek panelde özetlendi.</p></div><div class="ao-whois-status-card"><span>Domain Durumu</span><strong class="'.e($statusClass).'">'.e($statusDisplay).'</strong><small>'.e($expiryText ? ('Bitiş: '.$expiryText) : 'Bitiş bilgisi bekleniyor').'</small></div></div>';
        $html .= '<div class="ao-whois-premium-layout"><main>';
        $html .= '<div class="ao-whois-premium-grid"><section><h4>Domain Durumu</h4>'.ahost_whois_premium_table($heroRows).'</section><section><h4>Kayıt Şirketi Bilgileri</h4>'.ahost_whois_premium_table($techRows).'</section></div>';
        $html .= '<section class="ao-whois-premium-section"><h4>Name Server (DNS) Bilgileri</h4>'.ahost_whois_premium_table($nameServers ? array_combine(array_map(fn($i)=>'NS'.($i+1), array_keys($nameServers)), $nameServers) : ['Sonuç'=>'Nameserver bilgisi alınamadı.']).'</section>';
        $html .= '<section class="ao-whois-premium-section"><h4>Domain Sahibi Hakkında</h4>'.ahost_whois_premium_table([
            'Ad Soyad' => $contactName ?: 'Gizli / Whois Privacy',
            'Firma' => $contactCompany ?: '-',
            'E-posta' => $contactEmail ?: 'Gizli / Whois Privacy',
            'Telefon' => $contactPhone ?: '-',
            'Ülke / Şehir' => trim($contactCountry.' / '.$contactCity, ' /') ?: '-'
        ]).'</section>';
        $html .= '<section class="ao-whois-premium-section"><h4>Önemli Tarihler</h4>'.ahost_whois_premium_table([
            'Kayıt Tarihi' => $rows['Kayıt Tarihi'] ?? '-',
            'Son Güncelleme' => $rows['Son Güncelleme'] ?? '-',
            'Bitiş Tarihi' => $rows['Bitiş Tarihi'] ?? '-',
            'Kalan Süre' => function_exists('ao_site_tools_whois_days_left') ? ao_site_tools_whois_days_left((string)($rows['Bitiş Tarihi'] ?? '')) : '-',
            'Domain Yaşı' => function_exists('ao_site_tools_whois_age') ? ao_site_tools_whois_age((string)($rows['Kayıt Tarihi'] ?? '')) : '-',
        ]).'</section>';
        if ($rawWhois !== '') {
            $detailText = mb_substr($rawWhois, 0, 12000, 'UTF-8');
        } elseif ($isLocalDomain) {
            $detailText = "Bu alan adı Ahost One yerel domain kayıtlarında bulundu.\nCanlı WHOIS/RDAP bağlantısı yerel ortamda dış ağa çıkamadığı için ham yanıt yerine sistemdeki domain, müşteri, nameserver ve registrar özet bilgileri gösterildi.";
        } else {
            $detailText = "Canlı WHOIS/RDAP cevabı alınamadı.\nYukarıdaki bilgiler registrar API ve çözümlenen teknik sinyallerden hazırlanmıştır.";
        }
        $html .= '<details class="ao-tool-raw-details"><summary>Detaylı WHOIS cevabını aç</summary><pre class="ao-tool-pre">'.e($detailText).'</pre></details>';
        $html .= '</main><aside>';
        if (!$isLocalDomain) {
            $html .= '<div class="ao-whois-transfer-card"><span>Bu alan adı size mi ait?</span><strong>Transfer sürecini Ahost One ile başlatın</strong><b>'.ahost_domain_quote_price_html($transferQuote).'</b><a href="'.e(url('domain-transfer?domain='.rawurlencode($domain))).'">Transferi Başlat</a></div>';
        } else {
            $html .= '<div class="ao-whois-transfer-card is-local-domain"><span>Ahost One domaini</span><strong>Bu alan adı sisteminizde kayıtlı görünüyor.</strong><small>Transfer yerine müşteri panelindeki domain yönetimi kullanılmalı.</small><a href="'.e(url('client/domains')).'">Domainlerime Git</a></div>';
        }
        $html .= '<section class="ao-whois-premium-section"><h4>Alternatif Uzantılar</h4><div class="ao-whois-alt-list">';
        foreach ($suggestions as $s) {
            $html .= '<a href="'.e(url('domain?domain='.rawurlencode($s['domain']))).'"><span>'.e($s['domain']).'</span><small>'.e($s['available'] ? 'Uygun' : 'Kayıtlı').'</small><b>'.$s['price_html'].'</b></a>';
        }
        if (!$suggestions) $html .= '<p>Alternatif öneri hazırlanamadı.</p>';
        $html .= '</div></section>';
        $html .= '<section class="ao-whois-premium-section"><h4>Domain Üzerindeki Korumalar</h4>'.ahost_whois_premium_table($protectionRows).'</section>';
        $html .= '</aside></div>';
        $html .= '</div>';
        return ['title'=>'Detaylı WHOIS: '.$domain,'html'=>$html];
    }
    if ($tool === 'dns') {
        $records = $dnsRows;
        if (!$records && function_exists('dns_get_record')) {
            $dnsTypeMap = ['A'=>DNS_A,'AAAA'=>defined('DNS_AAAA')?DNS_AAAA:DNS_ALL,'CNAME'=>DNS_CNAME,'MX'=>DNS_MX,'TXT'=>DNS_TXT,'NS'=>DNS_NS,'CAA'=>defined('DNS_CAA')?DNS_CAA:DNS_ALL];
            foreach ($dnsTypeMap as $type => $dnsConst) {
                $live = @dns_get_record($domain, $dnsConst);
                if (is_array($live)) foreach ($live as $r) $records[] = ['record_type'=>$r['type']??$type,'host'=>$r['host']??'@','record_value'=>$r['ip']??($r['ipv6']??($r['target']??($r['txt']??($r['mname']??'-')))),'priority'=>$r['pri']??null,'ttl'=>$r['ttl']??3600];
            }
        }
        $typeCounts = [];
        foreach ($records as $record) {
            $type = strtoupper((string)($record['record_type'] ?? '-'));
            $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
        }
        $summaryRows = [
            'Domain' => $domain,
            'Toplam Kayıt' => (string)count($records),
            'Kayıt Türleri' => $typeCounts ? implode(', ', array_keys($typeCounts)) : 'Bulunamadı',
            'DNS Durumu' => $records ? 'Kayıtlar çözümlendi' : 'Kayıt bulunamadı',
        ];
        $html = '<div class="ao-tool-result-card ao-dns-premium">';
        $html .= '<div class="ao-tool-result-head"><span>DNS Kayıt Raporu</span><h3>'.e($domain).'</h3><p>A, AAAA, CNAME, MX, TXT, NS ve CAA kayıtları filtrelenebilir kart yapısında listelendi.</p></div>';
        $html .= '<div class="ao-tool-summary-grid">';
        foreach ($summaryRows as $label => $value) {
            $html .= '<div><small>'.e($label).'</small><strong>'.e($value).'</strong></div>';
        }
        $html .= '</div>';
        $html .= '<div class="ao-dns-tabs"><button class="active" data-dns-filter="ALL">Tümü</button><button data-dns-filter="A">A</button><button data-dns-filter="AAAA">AAAA</button><button data-dns-filter="CNAME">CNAME</button><button data-dns-filter="MX">MX</button><button data-dns-filter="TXT">TXT</button><button data-dns-filter="NS">NS</button><button data-dns-filter="CAA">CAA</button></div>';
        $html .= '<div class="ao-dns-record-grid">';
        if ($records) {
            foreach ($records as $r) {
                $type = strtoupper((string)($r['record_type'] ?? '-'));
                $html .= '<article class="ao-dns-record-card" data-record-type="'.e($type).'"><span>'.e($type).'</span><strong>'.e($r['host'] ?? '@').'</strong><p>'.e($r['record_value'] ?? '-').'</p><small>Öncelik: '.e($r['priority'] ?? '-').' · TTL: '.e($r['ttl'] ?? '-').'</small></article>';
            }
        } else {
            $html .= '<div class="ao-modal-error">DNS kaydı bulunamadı veya alan adı DNS yayımlamıyor.</div>';
        }
        $html .= '</div></div>';
        return ['title'=>'DNS Kayıtları: '.$domain,'html'=>$html];
    }
    if ($tool === 'ssl') {
        $rows = ['Domain'=>$domain,'SSL Durumu'=>'Pasif / erişilemedi','Issuer'=>'-','Başlangıç'=>'-','Bitiş'=>'-','TLS'=>'443 bağlantısı kurulamadı','Zincir'=>'-'];
        $ctx = @stream_context_create(['ssl'=>['capture_peer_cert'=>true,'verify_peer'=>false,'verify_peer_name'=>false], 'socket'=>['timeout'=>3]]);
        $client = @stream_socket_client('ssl://'.$domain.':443', $errno, $errstr, 3, STREAM_CLIENT_CONNECT, $ctx);
        if ($client) {
            $params = stream_context_get_params($client); $cert = $params['options']['ssl']['peer_certificate'] ?? null; $parsed = $cert ? @openssl_x509_parse($cert) : null;
            if ($parsed) {
                $rows['SSL Durumu']='Aktif'; $rows['Issuer']=$parsed['issuer']['O'] ?? ($parsed['issuer']['CN'] ?? '-');
                $rows['Başlangıç']=isset($parsed['validFrom_time_t']) ? date('Y-m-d H:i',$parsed['validFrom_time_t']) : '-';
                $rows['Bitiş']=isset($parsed['validTo_time_t']) ? date('Y-m-d H:i',$parsed['validTo_time_t']) : '-';
                $rows['CN']=$parsed['subject']['CN'] ?? '-';
            }
        }
        $active = ($rows['SSL Durumu'] ?? '') === 'Aktif';
        $left = '-';
        if (!empty($rows['Bitiş']) && $rows['Bitiş'] !== '-') {
            $ts = strtotime((string)$rows['Bitiş']);
            if ($ts) $left = max(0, (int)ceil(($ts - time()) / 86400)).' gün';
        }
        $html = '<div class="ao-tool-result-card ao-ssl-premium">';
        $html .= '<div class="ao-tool-result-head"><span>SSL Sertifika Raporu</span><h3>'.e($domain).'</h3><p>Sertifika durumu, sağlayıcı, bitiş tarihi ve TLS bağlantısı tek panelde gösterildi.</p></div>';
        $html .= '<div class="ao-tool-status-grid">';
        $html .= '<div class="ao-tool-status-card is-'.($active ? 'success' : 'warn').'"><strong>'.($active ? '✓' : '!').'</strong><h4>SSL Sertifikası</h4><p>'.e($rows['SSL Durumu']).'</p></div>';
        $html .= '<div class="ao-tool-status-card is-info"><strong>i</strong><h4>Kalan Süre</h4><p>'.e($left).'</p></div>';
        $html .= '<div class="ao-tool-status-card is-info"><strong>🔒</strong><h4>Issuer</h4><p>'.e($rows['Issuer']).'</p></div>';
        $html .= '</div>';
        $html .= '<div class="ao-tool-section-grid">';
        $html .= '<section class="ao-tool-section"><h4>Genel Bilgiler</h4>'.ahost_modal_table([
            'Domain' => $rows['Domain'],
            'Sertifika Durumu' => $rows['SSL Durumu'],
            'Başlangıç Tarihi' => $rows['Başlangıç'],
            'Bitiş Tarihi' => $rows['Bitiş'],
            'Kalan Süre' => $left,
        ]).'</section>';
        $html .= '<section class="ao-tool-section"><h4>Sertifika Detayları</h4>'.ahost_modal_table([
            'Sertifika Adı' => $rows['CN'] ?? '-',
            'Marka / Issuer' => $rows['Issuer'],
            'Şifreleme' => $rows['TLS'],
            'Zincir' => $rows['Zincir'],
        ]).'</section>';
        $html .= '</div></div>';
        return ['title'=>'SSL Kontrolü: '.$domain,'html'=>$html];
    }
    if ($tool === 'valuation') {
        $sslRows = ['SSL Durumu'=>'Pasif'];
        $ctx = @stream_context_create(['ssl'=>['capture_peer_cert'=>true,'verify_peer'=>false,'verify_peer_name'=>false], 'socket'=>['timeout'=>3]]);
        $client = @stream_socket_client('ssl://'.$domain.':443', $errno, $errstr, 3, STREAM_CLIENT_CONNECT, $ctx);
        if ($client) $sslRows['SSL Durumu'] = 'Aktif';
        $dnsCount = 0;
        if (function_exists('dns_get_record')) { $d = @dns_get_record($domain, DNS_ALL); $dnsCount = is_array($d) ? count($d) : 0; }
        $seo = ao_page_basic_analysis($domain);
        $whoisRows = ao_parse_whois_text(ao_raw_whois($domain));
        $val = ao_domain_valuation_score($domain, $whoisRows, $sslRows, $dnsCount, $seo);
        $value = '₺'.number_format((float)($val['value'] ?? 0), 0, ',', '.');
        $score = (int)($val['score'] ?? 0);
        $seoScore = (int)($val['seo_score'] ?? 0);
        $html = '<div class="ao-tool-result-card ao-domain-appraisal">';
        $html .= '<div class="ao-domain-appraisal-hero"><div><span>Domain Değerleme</span><h3>'.e($domain).'</h3><p>Tahmini değer <strong>'.e($value).'</strong></p><small>Kısa ad, TLD, DNS, SSL, WHOIS ve temel SEO sinyalleriyle hesaplanan Ahost One değerlendirme raporu.</small></div><div class="ao-domain-appraisal-score"><b>'.$score.'/100</b><span>Domain Skoru</span></div></div>';
        $html .= '<div class="ao-tool-status-grid">';
        $html .= '<div class="ao-tool-status-card is-info"><strong>₺</strong><h4>Tahmini Değer</h4><p>'.e($value).'</p></div>';
        $html .= '<div class="ao-tool-status-card is-info"><strong>SEO</strong><h4>SEO Sinyali</h4><p>'.$seoScore.'/100</p></div>';
        $html .= '<div class="ao-tool-status-card is-info"><strong>DNS</strong><h4>DNS Kaydı</h4><p>'.(int)$dnsCount.' kayıt</p></div>';
        $html .= '</div>';
        $html .= '<div class="ao-tool-section-grid">';
        $html .= '<section class="ao-tool-section"><h4>Canlı Analiz Sinyalleri</h4>'.ahost_modal_table([
            'Title' => $seo['title'] ?: 'Bulunamadı',
            'Meta Açıklama' => $seo['description'] ?: 'Bulunamadı',
            'H1 Sayısı' => (string)$seo['h1_count'],
            'SSL' => $sslRows['SSL Durumu'],
            'WHOIS Kaynak' => array_filter($whoisRows) ? 'Canlı WHOIS' : 'Bulunamadı',
            'Trafik Tahmini' => $seo['reachable'] ? 'Site erişilebilir; harici trafik API bağlanırsa sayısal trafik güncellenir.' : 'Site erişilemedi; trafik hesaplanamadı.'
        ]).'</section>';
        $html .= '<section class="ao-tool-section"><h4>Satış ve Takip Aksiyonları</h4>'.ahost_modal_table([
            'Marketplace' => 'Domain size aitse satış ilanı oluşturabilirsiniz.',
            'Transfer' => 'Domain size aitse Ahost One transfer sürecini başlatabilirsiniz.',
            'Backorder' => 'Domain kayıtlıysa ön sipariş/takip talebi oluşturulabilir.',
        ]).'<div class="ao-tool-modal-actions"><a href="'.e(url('marketplace/create?domain='.rawurlencode($domain))).'">Satışa Ekle</a><a href="'.e(url('domain-transfer?domain='.rawurlencode($domain))).'">Transfer Et</a></div></section>';
        $html .= '</div>';
        $html .= '<p class="ao-muted">Değerleme Ahost One iç algoritmasıyla; domain uzunluğu, TLD, WHOIS, DNS, SSL ve sayfa SEO sinyalleri üzerinden hesaplanır.</p></div>';
        return ['title'=>'Domain Değerleme: '.$domain,'html'=>$html];
    }
    return ['title'=>'Domain Sorgu','html'=>'<div class="ao-modal-error">Geçersiz işlem.</div>'];
}
if ($route === 'api/domain-tool') {
    if (ob_get_length()) {
        ob_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    $tool = trim($_GET['tool'] ?? 'whois'); $domain = trim($_GET['domain'] ?? '');
    if (!in_array($tool, ['whois','dns','ssl','valuation'], true)) $tool = 'whois';
    if (!empty($_GET['limited'])) {
        $quota = ao_site_tools_quota_v2632($tool);
        if (empty($quota['allowed'])) {
            echo json_encode([
                'title' => 'Kullanım limiti',
                'html' => '<div class="ao-modal-error"><strong>Ücretsiz kullanım limiti doldu.</strong><br>Aynı IP ile 24 saat içinde en fazla '.(int)$quota['limit'].' araç sorgusu yapılabilir. Sınırsız kullanım için müşteri paneline giriş yapın veya bir paket satın alın.<div style="margin-top:12px"><a class="site-btn" href="'.e(url('urunler')).'">Paketleri İncele</a></div></div>'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        ao_site_tools_record_v2632($tool, $domain);
    }
    echo json_encode(ao_repair_tr_mojibake_v270(ahost_domain_lookup_html($tool, $domain)), JSON_UNESCAPED_UNICODE);
    exit;
}

function ao_domain_suggestion_candidates($domain, $limit = 12) {
    $domain = ahost_domain_clean($domain);
    $sld = ahost_domain_search_term($domain);
    if ($sld === '') return [];
    $parts = str_contains($domain, '.') ? explode('.', $domain) : [$sld];
    array_shift($parts);
    $currentTld = implode('.', $parts);
    $tlds = ['com','net','com.tr','org','net.tr','info','co','io','app','shop','store','online','site','web.tr'];
    $names = [$sld, $sld.'host', $sld.'web', $sld.'online', $sld.'net', $sld.'pro', $sld.'ajans', $sld.'dijital'];
    $out = [];
    foreach ($tlds as $tld) {
        $candidate = $sld.'.'.$tld;
        if ($candidate !== $domain) $out[] = $candidate;
    }
    foreach ($names as $name) {
        $candidate = preg_replace('/[^a-z0-9-]/', '', strtolower($name)).'.'.($currentTld ?: 'com');
        if ($candidate && $candidate !== $domain) $out[] = $candidate;
    }
    return array_slice(array_values(array_unique($out)), 0, max(1, (int)$limit));
}

function ao_domain_search_card_html(array $row) {
    $domain = (string)($row['domain'] ?? '');
    $available = !empty($row['available']);
    $message = (string)($row['message'] ?? ($available ? 'Kayıt için uygun görünüyor.' : 'Kayıtlı görünüyor.'));
    $html = '<div class="ao-domain-suggestion '.($available ? 'available' : 'taken').'">';
    $html .= '<b>'.e($domain).'</b><span>'.($available ? 'Müsait' : 'Kayıtlı').'</span><small>'.e($message).'</small>';
    if ($available) $html .= '<a href="'.e(url('cart/add?domain='.rawurlencode($domain))).'">Sepete Ekle</a>';
    else $html .= '<button data-domain-tool="whois" data-domain-value="'.e($domain).'">WHOIS</button><button type="button" data-domain-backorder-link data-domain-backorder="'.e($domain).'">Ön sipariş ver</button>';
    return $html.'</div>';
}

if ($route === 'api/domain-search') {
    if (ob_get_length()) {
        ob_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    $rawDomain = trim($_GET['domain'] ?? '');
    $domain = ahost_domain_clean($rawDomain);
    $hasTld = ahost_domain_has_tld($domain);
    $searchTerm = ahost_domain_search_term($domain);
    $more = !empty($_GET['more']);
    $compact = !empty($_GET['compact']);
    if (!$compact) {
        $refPath = trim((string)parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_PATH), '/');
        $basePath = trim((string)parse_url(url(''), PHP_URL_PATH), '/');
        $compact = ($refPath === $basePath);
    }
    if ($searchTerm === '') {
        echo json_encode(['ok'=>false,'domain'=>$domain,'message'=>'Lütfen sorgulanacak domain adını yazın.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (!$hasTld) {
        $suggestions = [];
        foreach (ao_domain_suggestion_candidates($searchTerm, $more ? 16 : 6) as $candidate) {
            $sr = ao_domain_availability($candidate);
            if (empty($sr['ok'])) continue;
            $suggestions[] = ['domain'=>$candidate, 'available'=>!empty($sr['available']), 'message'=>$sr['message'] ?? 'Sonuç alındı'];
        }
        $html = '<div class="ao-search-result ao-search-result--keyword"><b>'.e($searchTerm).'</b><span>Uzantısız arama yaptınız. En çok kullanılan uzantılar aşağıda listelendi.</span></div>';
        if ($suggestions) {
            $html .= '<div class="ao-domain-suggestions"><h4>Domain Önerileri</h4><div class="ao-domain-suggestion-grid">';
            foreach ($suggestions as $s) $html .= ao_domain_search_card_html($s);
            $html .= '</div>';
            if (!$more) $html .= '<button type="button" class="site-btn ao-domain-more-btn" data-domain-more="'.e($searchTerm).'">Daha fazla</button>';
            $html .= '</div>';
        }
        echo json_encode(['ok'=>true,'keyword'=>$searchTerm,'domain'=>$searchTerm,'available'=>null,'message'=>'Uzantı önerileri listelendi.','suggestions'=>$suggestions,'html'=>$html], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $result = ao_domain_availability($domain);
    if (!empty($result['ok'])) {
        $quote = ahost_domain_admin_tld_quote($result['domain'] ?? $domain, 'register');
        $result['register_price'] = $quote['sale_price'];
        $result['selected_registrar'] = $quote['selected_registrar'];
        $result['registrar_cost'] = $quote['registrar_cost'] ?? null;
        $result['currency'] = $quote['rule']['currency'] ?? $quote['currency'] ?? (function_exists('admin_setting') ? admin_setting('default_currency', 'TRY') : 'TRY');
        $suggestions = [];
        foreach (ao_domain_suggestion_candidates($result['domain'] ?? $domain, $more ? 16 : 6) as $candidate) {
            $sr = ao_domain_availability($candidate);
            if (empty($sr['ok'])) continue;
            $suggestions[] = ['domain'=>$candidate, 'available'=>!empty($sr['available']), 'message'=>$sr['message'] ?? 'Sonuç alındı'];
        }
        $result['suggestions'] = $suggestions;
        $priceLine = ahost_domain_quote_price_html($quote, ' / yıl');
        $html = '<div class="ao-search-result '.($result['available']?'available':'taken').'">';
        if (!empty($result['available'])) {
            $html .= '<b>'.e($result['domain']).'</b><span>'.e($result['message']).'</span><strong>Tebrikler! '.e($result['domain']).' satın almaya uygun!</strong><em class="ao-domain-price">Yıllık kayıt ücreti: '.$priceLine.'</em><a class="site-btn" href="'.e(url('cart/add?domain='.rawurlencode($result['domain']))).'">Sepete Ekle</a>';
        } else {
            $suggestionsAction = ($suggestions && $compact)
                ? '<a class="site-btn" href="'.e(url('domain?domain='.rawurlencode($result['domain'] ?? $domain).'#whois')).'">Önerilen Domainler</a>'
                : '';
            $html .= '<span class="ao-domain-taken-kicker">Üzgünüz</span><b>'.e($result['domain']).' daha önce kayıt edilmiş.</b><strong class="ao-domain-taken-note">Yeniden sorgulama yapabilir ya da aşağıdaki alan adı önerilerine göz atabilirsiniz.</strong><div class="ao-domain-result-actions"><button data-domain-tool="whois" data-domain-value="'.e($result['domain']).'">WHOIS</button><button data-domain-tool="dns" data-domain-value="'.e($result['domain']).'">DNS</button><button type="button" class="site-btn" data-domain-backorder-link data-domain-backorder="'.e($result['domain']).'">Ön sipariş ver</button>'.$suggestionsAction.'</div>';
        }
        $html .= '</div>';
        if ($suggestions && !$compact) {
            $html .= '<div class="ao-domain-suggestions"><h4>Önerilen Domainler</h4><div class="ao-domain-suggestion-grid">';
            foreach ($suggestions as $s) $html .= ao_domain_search_card_html($s);
            $html .= '</div>';
            if (!$more) $html .= '<button type="button" class="site-btn ao-domain-more-btn" data-domain-more="'.e(ahost_domain_search_term($result['domain'] ?? $domain)).'">Daha fazla</button>';
            $html .= '</div>';
        }
        $result['html'] = $html;
    }
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}
if ($route === 'api/domain-quote') {
    header('Content-Type: application/json; charset=utf-8');
    $domain = trim($_GET['domain'] ?? '');
    $gateway = trim($_GET['gateway'] ?? 'paytr');
    $quote = ao_smart_domain_quote($domain, 'register');
    $quote['payment'] = ao_payment_fee_quote((float)$quote['sale_price'], $gateway);
    echo json_encode($quote, JSON_UNESCAPED_UNICODE);
    exit;
}











