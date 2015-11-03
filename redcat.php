<?php
define('REDCAT',__DIR__.'/');
define('REDCAT_CWD',getcwd().'/');
if(!defined('REDCAT_FREEZE_DI')) define('REDCAT_FREEZE_DI',false);

require_once __DIR__.'/php/RedCat/Autoload/Autoload.php';
RedCat\Autoload\Autoload::register([
	REDCAT_CWD.'php',
	REDCAT.'php',
	REDCAT_CWD.'plugin/php',
]);

$redcat = RedCat\Wire\Di::load([
	REDCAT.'.config.php',
	REDCAT_CWD.'.config.php'
],REDCAT_FREEZE_DI,REDCAT_CWD.'.tmp/redcat.svar');

if($redcat['dev']['php']){
	$redcat->create('RedCat\Debug\ErrorHandler')->handle();
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