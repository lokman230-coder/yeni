<?php
declare(strict_types=1);
namespace App\Modules\Admin\Controllers;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View;
final class SiteToolsController { public function index(Request $r): Response { $tools=[['name'=>'WHOIS','slug'=>'whois','desc'=>'Domain kayıt bilgilerini kontrol edin.'],['name'=>'DNS Kontrolü','slug'=>'dns','desc'=>'DNS kayıtlarını analiz edin.'],['name'=>'SSL Kontrolü','slug'=>'ssl','desc'=>'SSL sertifikası ve geçerlilik kontrolü.'],['name'=>'SEO Analizi','slug'=>'seo','desc'=>'Title, meta, heading ve teknik SEO analizi.'],['name'=>'Hız Testi','slug'=>'speed','desc'=>'Sayfa performansını ölçün.'],['name'=>'Güvenlik Başlıkları','slug'=>'headers','desc'=>'HTTP güvenlik başlıklarını kontrol edin.'],['name'=>'IP Lookup','slug'=>'ip','desc'=>'IP ve DNS bilgilerini görüntüleyin.'],['name'=>'Robots.txt','slug'=>'robots','desc'=>'Robots.txt dosyasını kontrol edin.']];return Response::html((new View())->render('admin::site-tools/index',['title'=>'Site Araçları','tools'=>$tools])); } }