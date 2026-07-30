<?php
declare(strict_types=1);
namespace App\Modules\Admin\Controllers;
use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View;
final class CookieAnalyticsController {
 public function index(Request $r): Response { $stats=['events'=>0,'visitors'=>0,'pageviews'=>0,'clicks'=>0,'cart'=>0];$pages=[];$recent=[];try{$stats['events']=(int)(Connection::selectOne('SELECT COUNT(*) v FROM cookie_analytics_events')['v']??0);$stats['visitors']=(int)(Connection::selectOne('SELECT COUNT(DISTINCT session_hash) v FROM cookie_analytics_events')['v']??0);$stats['pageviews']=(int)(Connection::selectOne("SELECT COUNT(*) v FROM cookie_analytics_events WHERE event_type='pageview'")['v']??0);$stats['clicks']=(int)(Connection::selectOne("SELECT COUNT(*) v FROM cookie_analytics_events WHERE event_type='click'")['v']??0);$stats['cart']=(int)(Connection::selectOne("SELECT COUNT(*) v FROM cookie_analytics_events WHERE event_type IN ('cart_add','cart_abandon')")['v']??0);$pages=Connection::select("SELECT url,COUNT(*) total FROM cookie_analytics_events WHERE event_type='pageview' GROUP BY url ORDER BY total DESC LIMIT 10");$recent=Connection::select('SELECT event_type,url,created_at FROM cookie_analytics_events ORDER BY id DESC LIMIT 20');}catch(\Throwable){}return Response::html((new View())->render('admin::cookie/index',['title'=>'Çerez Analizi','stats'=>$stats,'pages'=>$pages,'recent'=>$recent])); }
}