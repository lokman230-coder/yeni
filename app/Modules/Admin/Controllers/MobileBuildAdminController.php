<?php
declare(strict_types=1);
namespace App\Modules\Admin\Controllers;
use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View;
use App\Modules\Builder\Services\MobileBuildService;
use App\Services\Settings\SettingsManager;
final class MobileBuildAdminController { public function index(Request $r): Response { $jobs=[];try{$jobs=Connection::select('SELECT b.*,c.email,p.name project_name FROM mobile_build_jobs b LEFT JOIN customers c ON c.id=b.customer_id LEFT JOIN builder_projects p ON p.id=b.project_id ORDER BY b.id DESC LIMIT 200');}catch(\Throwable){}return Response::html((new View())->render('admin::mobile-builds/index',['title'=>'Mobile Build İşleri','jobs'=>$jobs])); }
 public function githubHealth(Request $r): Response { $r=\App\Modules\Builder\Services\GithubActionsBuildService::latestRun();return Response::json(['ok'=>(bool)($r['ok']??false),'status'=>$r['status']??null,'run'=>$r['run']??null,'error'=>$r['error']??null]); }
 public function status(Request $r): Response { $id=(int)$r->param('id');$job=Connection::selectOne('SELECT id,status,progress,error_log,updated_at FROM mobile_build_jobs WHERE id=?',[$id]);return $job?Response::json(['ok'=>true,'job'=>$job]):Response::json(['ok'=>false,'error'=>'not_found'],404); }
 public function workerHealth(Request $r): Response { $url=rtrim((string)SettingsManager::get('mobile.worker_url',''),'/');if($url==='')return Response::json(['ok'=>false,'error'=>'worker_url_not_configured'],400);$ch=curl_init($url.'/health');curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>10]);$raw=curl_exec($ch);$code=curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);$data=$raw?json_decode((string)$raw,true):[];return Response::json(['ok'=>$code===200,'status'=>$code,'data'=>$data,'error'=>$err?:null]); }
 public function retry(Request $r): Response { $id=(int)$r->param('id');$job=Connection::selectOne('SELECT * FROM mobile_build_jobs WHERE id=?',[$id]);if($job&&in_array($job['status'],['failed','waiting_worker'],true)){Connection::update('mobile_build_jobs',['status'=>'queued','progress'=>0,'error_log'=>null,'updated_at'=>date('Y-m-d H:i:s')],'id=?',[$id]);MobileBuildService::dispatch($id);}return Response::redirect('/admin/mobile-buildler'); }
}