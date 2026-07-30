<?php
use App\Core\Router;
use App\Modules\Announcements\Controllers\AdminAnnouncementController;
/** @var Router $router */
$router->group(['prefix'=>'admin','middleware'=>['locale','admin.auth']],function(Router $router){$c=AdminAnnouncementController::class;$router->get('/duyurular',[$c,'index'])->name('admin.announcements');$router->get('/duyurular/yeni',[$c,'form']);$router->get('/duyurular/{id}/duzenle',[$c,'form']);$router->post('/duyurular/kaydet',[$c,'save'])->middleware(['csrf']);$router->post('/duyurular/{id}/sil',[$c,'delete'])->middleware(['csrf']);});