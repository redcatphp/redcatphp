<?php
//define('REDCAT_FREEZE_DI',true);
$redcat = require_once 'redcat.php';
$route = $redcat->create(RedCat\Framework\FrontController\FrontOffice::class);
$route->load();
$route->runFromGlobals();