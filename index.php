<?php
//define('REDCAT_FREEZE_DI',true);
$redcat = require_once 'redcat.php';
$route = $redcat->create('#router');
$route->load();
$route->runFromGlobals();