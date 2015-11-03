<?php
//define('REDCAT_FREEZE_DI',true);
require_once __DIR__.'/redcat/redcat.php';
$surikat->create('RedCat\Plugin\FrontController\FrontOffice')->runFromGlobals();