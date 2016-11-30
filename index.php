<?php
//define('REDCAT_DEV_CONFIG',false);
$redcat = require_once 'redcat.php';
$route = $redcat->get('#router');
$route->load();
$route->runFromGlobals();