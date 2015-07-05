<?php
define('SURIKAT',__DIR__.'/');
define('SURIKAT_CWD',getcwd().'/');
if(!defined('SURIKAT_FREEZE_DI')) define('SURIKAT_FREEZE_DI',false);

require_once __DIR__.'/php/Unit/Autoloader.php';
Unit\Autoloader::register([SURIKAT_CWD.'php',SURIKAT.'php']);

$di = Unit\Di::load([
	SURIKAT.'.config.php',
	SURIKAT_CWD.'.config.php'
],SURIKAT_FREEZE_DI,SURIKAT_CWD.'.tmp/surikat.svar');

if($di['dev']['php']){
	$di->create('Unit\Debug')->handleErrors();
}
else{
	error_reporting(0);
	ini_set('display_startup_errors',false);
	ini_set('display_errors',false);
}