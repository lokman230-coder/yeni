<?php
declare(strict_types=1);
namespace App\Modules\Admin\Controllers;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
final class ModuleController {
    private static function stateFile(): string { return AHO_ROOT.'/storage/module-state.json'; }
    public function index(Request $request): Response { $states=is_file(self::stateFile())?(json_decode((string)file_get_contents(self::stateFile()),true)?:[]):[]; $modules=[]; foreach(glob(AHO_ROOT.'/app/Modules/*',GLOB_ONLYDIR) as $dir){$slug=basename($dir);$modules[]=['slug'=>$slug,'label'=>preg_replace('/(?<!^)([A-Z])/',' $1',$slug),'active'=>($states[$slug]??1)];} usort($modules,fn($a,$b)=>strcmp($a['label'],$b['label'])); $view=new View(); return Response::html($view->render('admin::modules/index',['title'=>'Modül Merkezi','modules'=>$modules])); }
    public function toggle(Request $request): Response { $slug=preg_replace('/[^A-Za-z0-9_-]/','',(string)$request->input('slug','')); if($slug==='') return Response::redirect('/admin/modul-merkezi'); $states=is_file(self::stateFile())?(json_decode((string)file_get_contents(self::stateFile()),true)?:[]):[]; $states[$slug]=empty($states[$slug])?1:0; @mkdir(dirname(self::stateFile()),0775,true); file_put_contents(self::stateFile(),json_encode($states,JSON_PRETTY_PRINT),LOCK_EX); SessionManager::flash('success','Modül durumu güncellendi.'); return Response::redirect('/admin/modul-merkezi'); }
}
