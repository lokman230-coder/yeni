<?php
declare(strict_types=1);
namespace App\Modules\Admin\Controllers;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
final class ThemeBlocksController {
    private static function file(): string { return AHO_ROOT.'/storage/theme-blocks.json'; }
    private static function blocks(): array { if(!is_file(self::file())) return [['key'=>'hero','label'=>'Hero / Domain arama','active'=>1],['key'=>'products','label'=>'Ürün kartları','active'=>1],['key'=>'tools','label'=>'Site araçları','active'=>1],['key'=>'builder','label'=>'Builder tanıtımı','active'=>1],['key'=>'references','label'=>'Referanslar','active'=>1],['key'=>'blog','label'=>'Blog / Duyurular','active'=>1]]; $v=json_decode((string)file_get_contents(self::file()),true); return is_array($v)?$v:[]; }
    public function index(Request $r): Response { $view=new View(); return Response::html($view->render('admin::theme-blocks/index',['title'=>'Site Tema Blokları','blocks'=>self::blocks()])); }
    public function toggle(Request $r): Response { $key=(string)$r->input('key',''); $items=self::blocks(); foreach($items as &$b) if($b['key']===$key) $b['active']=empty($b['active'])?1:0; @mkdir(dirname(self::file()),0775,true); file_put_contents(self::file(),json_encode($items,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),LOCK_EX); SessionManager::flash('success','Tema bloğu güncellendi.'); return Response::redirect('/admin/tema-bloklari'); }
}
