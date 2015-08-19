<?php
//error_reporting(-1);
//ini_set('display_startup_errors',true);
//ini_set('display_errors','stdout');

//define('SURIKAT_FREEZE_DI',true);
require_once __DIR__.'/surikat/surikat.php';

$surikat->create('Wild\Plugin\FrontController\FrontOffice')->runFromGlobals();