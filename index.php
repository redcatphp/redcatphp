<?php
//define('REDCAT_DEV_CONFIG',false);
$redcat = require_once 'redcat.php';
$route = $redcat->create('#router');
$route->load();
$route->runFromGlobals();