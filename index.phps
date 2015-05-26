<?php
#Copy this file to ../DOCUMENT_ROOT to start a new project

//error_reporting(-1);
//ini_set('display_startup_errors',true);
//ini_set('display_errors','stdout');

//define('SURIKAT_FREEZE_DI',true);
if(!@include(__DIR__.'/Surikat/bootstrap.php'))
	symlink('../Surikat','Surikat')&&include('Surikat/bootstrap.php');

//exit((($chrono=microtime(true)-$_SERVER["REQUEST_TIME_FLOAT"])?sprintf("%.2f", ($chrono>=1?$chrono:$chrono*(float)1000)).' '.($chrono>=1?'s':'ms'):null).' '.(($memory=memory_get_peak_usage())?rtrim(sprintf("%.2f",(float)($memory)/(float)pow(1024,$factor=floor((strlen($memory)-1)/3))),'.0').' '.('BKMGTP'[(int)$factor]).($factor?'B':'ytes'):null));

$SURIKAT->create('KungFu\Cms\FrontController\Index')->runFromGlobals();