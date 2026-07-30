<?php
declare(strict_types=1);
namespace App\Modules\Pages\Controllers;
use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
final class AdminPageController {
 public function index(Request $r): Response {return Response::html((new View())->render('pages::admin.index',['title'=>'Sayfalar','items'=>Connection::select('SELECT * FROM cms_pages ORDER BY id DESC')]));}
 public function form(Request $r): Response {$id=(int)$r->param('id');$item=$id?Connection::selectOne('SELECT * FROM cms_pages WHERE id=?',[$id]):null;return Response::html((new View())->render('pages::admin.form',['title'=>$id?'Sayfa düzenle':'Yeni sayfa','item'=>$item]));}
 public function save(Request $r): Response {$id=(int)$r->input('id',0);$title=trim((string)$r->input('title',''));$slug=trim((string)$r->input('slug',''));$content=(string)$r->input('content','');if($title===''||$slug===''){SessionManager::flash('error','Başlık ve slug zorunludur.');return Response::redirect('/admin/sayfalar/yeni');}$data=['title'=>$title,'slug'=>$slug,'content'=>$content,'seo_title'=>trim((string)$r->input('seo_title',''))?:$title,'seo_description'=>trim((string)$r->input('seo_description','')),'is_published'=>$r->input('is_published')?1:0,'updated_at'=>date('Y-m-d H:i:s')];if($id)Connection::update('cms_pages',$data,'id=?',[$id]);else{$data['created_at']=date('Y-m-d H:i:s');Connection::insert('cms_pages',$data);}SessionManager::flash('success','Sayfa kaydedildi.');return Response::redirect('/admin/sayfalar');}
 public function delete(Request $r): Response {$id=(int)$r->param('id');if($id)Connection::delete('cms_pages','id=?',[$id]);return Response::redirect('/admin/sayfalar');}
}