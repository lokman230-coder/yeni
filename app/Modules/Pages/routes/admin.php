<?php
use App\Core\Router;
use App\Modules\Pages\Controllers\AdminPageController;
/** @var Router $router */
$router->group(['prefix'=>'admin','middleware'=>['locale','admin.auth']],function(Router $router){$c=AdminPageController::class;$router->get('/sayfalar',[$c,'index'])->name('admin.pages');$router->get('/sayfalar/yeni',[$c,'form']);$router->get('/sayfalar/{id}/duzenle',[$c,'form']);$router->post('/sayfalar/kaydet',[$c,'save'])->middleware(['csrf']);$router->post('/sayfalar/{id}/sil',[$c,'delete'])->middleware(['csrf']);});