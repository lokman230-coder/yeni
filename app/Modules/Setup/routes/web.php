<?php

use App\Core\Router;
use App\Modules\Setup\Controllers\SetupController;

/** @var Router $router */

$router->get('/kurulum',                [SetupController::class, 'index'])->name('setup.index');
$router->get('/kurulum/adim/{n}',       [SetupController::class, 'step'])->name('setup.step');
$router->post('/kurulum/db',            [SetupController::class, 'saveDb'])->middleware(['csrf']);
$router->post('/kurulum/migrate',       [SetupController::class, 'runMigrations'])->middleware(['csrf']);
$router->post('/kurulum/sql-yukle',     [SetupController::class, 'uploadSql'])->middleware(['csrf']);
$router->post('/kurulum/admin',         [SetupController::class, 'createAdmin'])->middleware(['csrf']);
$router->post('/kurulum/site',          [SetupController::class, 'saveSite'])->middleware(['csrf']);
$router->get('/kurulum/tamamlandi',     [SetupController::class, 'done'])->name('setup.done');
