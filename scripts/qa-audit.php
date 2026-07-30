<?php
declare(strict_types=1);
$root=dirname(__DIR__);$fail=[];$warn=[];
$required=['index.php','install.php','.htaccess','app','config','database','routes','storage','vendor'];
foreach($required as $p) if(!file_exists($root.'/'.$p))$fail[]='Eksik: '.$p;
$stubs=0;foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/app')) as $f){if($f->isFile()&&in_array($f->getExtension(),['php','js','css'],true)){ $s=@file_get_contents($f->getPathname())?:''; if(stripos($s,'StubController')!==false||stripos($s,'— Yakında')!==false)$stubs++; }}if($stubs)$warn[]=$stubs.' placeholder referansı bulundu.';
if(!is_writable($root.'/storage'))$fail[]='storage yazılabilir değil';
foreach(['pdo_mysql','mbstring','curl','openssl','json'] as $ext)if(!extension_loaded($ext))$fail[]='PHP eklentisi eksik: '.$ext;
echo "Ahost One QA Audit\n=================\n";echo 'Root: '.$root."\n";echo 'Durum: '.($fail?'FAIL':'OK')."\n";if($fail){echo "\nHATALAR:\n";foreach($fail as $x)echo "- $x\n";}if($warn){echo "\nUYARILAR:\n";foreach($warn as $x)echo "- $x\n";}if(!$fail&&!$warn)echo "\nTemel kontroller başarılı.\n";exit($fail?1:0);
