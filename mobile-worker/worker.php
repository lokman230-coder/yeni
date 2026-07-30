<?php
declare(strict_types=1);
$path=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH);
header('Content-Type: application/json; charset=utf-8');
if($path==='/health'){echo json_encode(['ok'=>true,'worker'=>'ahost-mobile','flutter'=>trim((string)@shell_exec('flutter --version --machine 2>/dev/null'))]);exit;}
$key=$_SERVER['HTTP_X_WORKER_KEY']??'';
if(!hash_equals((string)(getenv('WORKER_API_KEY')?:''),$key)){http_response_code(401);echo json_encode(['ok'=>false,'error'=>'unauthorized']);exit;}
if($path==='/build'&&($_SERVER['REQUEST_METHOD']??'GET')==='POST'){ $body=json_decode((string)file_get_contents('php://input'),true)?:[]; $id=bin2hex(random_bytes(8)); file_put_contents('/opt/ahost-worker/storage/job-'.$id.'.json',json_encode(['id'=>$id,'status'=>'queued','payload'=>$body,'created_at'=>date('c')],JSON_PRETTY_PRINT)); echo json_encode(['ok'=>true,'job_id'=>$id,'status'=>'queued']);exit; }
if($path==='/status'){ $id=preg_replace('/[^a-z0-9]/i','',(string)($_GET['id']??''));$file='/opt/ahost-worker/storage/job-'.$id.'.json';if(!is_file($file)){http_response_code(404);echo json_encode(['ok'=>false,'error'=>'job_not_found']);exit;}echo file_get_contents($file);exit; }
http_response_code(404);echo json_encode(['ok'=>false,'error'=>'not_found']);
