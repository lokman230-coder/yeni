<?php
declare(strict_types=1);
namespace App\Modules\Admin\Controllers;
use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View;
final class AdminBuilderController {
 public function index(Request $r): Response { $q=trim((string)$r->input('q',''));$kind=trim((string)$r->input('kind',''));$where='';$params=[];if($q!==''){$where.=' AND (p.name LIKE ? OR p.slug LIKE ? OR c.email LIKE ?)';$params[]="%$q%";$params[]="%$q%";$params[]="%$q%";}if(in_array($kind,['site','mobile'],true)){$where.=' AND p.kind=?';$params[]=$kind;}$items=[];try{$items=Connection::select("SELECT p.*,c.email FROM builder_projects p LEFT JOIN customers c ON c.id=p.customer_id WHERE 1=1{$where} ORDER BY p.id DESC LIMIT 100",$params);}catch(\Throwable){}return Response::html((new View())->render('admin::builder/index',['title'=>'Builder Projeleri','items'=>$items,'q'=>$q,'kind'=>$kind])); }
 public function updateStatus(Request $r): Response { $id=(int)$r->param('id');$status=(string)$r->input('status','draft');if(in_array($status,['draft','published','archived'],true)) Connection::update('builder_projects',['status'=>$status,'updated_at'=>date('Y-m-d H:i:s')],'id=?',[$id]);return Response::redirect('/admin/site-builder/'.$id); }
 public function show(Request $r): Response { $id=(int)$r->param('id');$project=Connection::selectOne("SELECT p.*,c.email FROM builder_projects p LEFT JOIN customers c ON c.id=p.customer_id WHERE p.id=?",[$id]);if(!$project)return Response::notFound('Proje bulunamadı');$pages=Connection::select('SELECT id,title,slug,is_home FROM builder_pages WHERE project_id=? ORDER BY id',[$id]);return Response::html((new View())->render('admin::builder/show',['title'=>'Builder Proje Detayı','project'=>$project,'pages'=>$pages])); }
}