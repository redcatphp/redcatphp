<?php
define('SURIKAT',__DIR__.'/');
define('SURIKAT_CWD',getcwd().'/');
if(!defined('SURIKAT_FREEZE_DI')) define('SURIKAT_FREEZE_DI',false);

require_once __DIR__.'/php/Wild/Autoload/Autoload.php';
Wild\Autoload\Autoload::register([
	SURIKAT_CWD.'php',
	SURIKAT.'php',
	SURIKAT_CWD.'plugin/php',
]);

$surikat = Wild\Kinetic\Di::load([
	SURIKAT.'.config.php',
	SURIKAT_CWD.'.config.php'
],SURIKAT_FREEZE_DI,SURIKAT_CWD.'.tmp/surikat.svar');

if($surikat['dev']['php']){
	$surikat->create('Wild\Debug\ErrorHandler')->handle();
}
else{
	error_reporting(0);
	ini_set('display_startup_errors',false);
	ini_set('display_errors',false);
	register_shutdown_function(function(){
		$error = error_get_last();
		if($error&&$error['type']&(E_ERROR|E_USER_ERROR|E_PARSE|E_CORE_ERROR|E_COMPILE_ERROR|E_RECOVERABLE_ERROR))
			header('Location: /500',true,302);
	});
}