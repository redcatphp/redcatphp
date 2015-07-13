<?php
//error_reporting(-1);
//ini_set('display_startup_errors',true);
//ini_set('display_errors','stdout');

//define('SURIKAT_FREEZE_DI',true);
require_once __DIR__.'/surikat/surikat.php';

Unit\Di::make('KungFu\Cms\FrontController\Index')->runFromGlobals();