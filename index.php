<?php
//define('REDCAT_DING_FREEZE',true);
$redcat = require_once 'redcat.php';
$route = $redcat->create('#router');
$route->load();
$route->runFromGlobals();