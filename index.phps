<?php
//define('SURIKAT_FREEZE_DI',true);
require_once __DIR__.'/surikat/surikat.php';
$surikat->create('Wild\Plugin\FrontController\FrontOffice')->runFromGlobals();