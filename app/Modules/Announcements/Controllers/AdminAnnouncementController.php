<?php
declare(strict_types=1);
namespace App\Modules\Announcements\Controllers;
use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
final class AdminAnnouncementController {
 public function index(Request $r): Response { $rows=Connection::select('SELECT * FROM announcements ORDER BY id DESC'); return Response::html((new View())->render('announcements::admin.index',['title'=>'Duyurular','items'=>$rows])); }
 public function form(Request $r): Response { $id=(int)$r->param('id'); $item=$id?Connection::selectOne('SELECT * FROM announcements WHERE id=?',[$id]):null; return Response::html((new View())->render('announcements::admin.form',['title'=>$id?'Duyuru düzenle':'Yeni duyuru','item'=>$item])); }
 public function save(Request $r): Response { $id=(int)$r->input('id',0); $title=trim((string)$r->input('title',''));$content=trim((string)$r->input('content',''));$status=(string)$r->input('status','draft'); if($title===''||$content===''){SessionManager::flash('error','Başlık ve içerik zorunludur.');return Response::redirect('/admin/duyurular/yeni');} $slug=trim((string)$r->input('slug',''))?:strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',iconv('UTF-8','ASCII//TRANSLIT',$title)),'-')); if($id) Connection::update('announcements',['title'=>$title,'slug'=>$slug,'content'=>$content,'status'=>$status,'published_at'=>$status==='published'?date('Y-m-d H:i:s'):null,'updated_at'=>date('Y-m-d H:i:s')],'id=?',[$id]); else Connection::insert('announcements',['title'=>$title,'slug'=>$slug,'content'=>$content,'status'=>$status,'published_at'=>$status==='published'?date('Y-m-d H:i:s'):null,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]); SessionManager::flash('success','Duyuru kaydedildi.');return Response::redirect('/admin/duyurular'); }
 public function delete(Request $r): Response { $id=(int)$r->param('id'); if($id) Connection::delete('announcements','id=?',[$id]); return Response::redirect('/admin/duyurular'); }
}