<?php
if(!defined('REDCAT'))
	define('REDCAT',__DIR__.'/');
if(!defined('REDCAT_PUBLIC'))
	define('REDCAT_PUBLIC',getcwd().'/');
if(!defined('REDCAT_SHARED'))
	define('REDCAT_SHARED',REDCAT.'shared/');

require_once REDCAT.'vendor/autoload.php';

$redcat = RedCat\Wire\Di::load(
	[
		REDCAT.'.config.php',
		REDCAT_SHARED.'.config.php',
		REDCAT_PUBLIC.'.config.php'
	],
	(defined('REDCAT_FREEZE_DI')?REDCAT_FREEZE_DI:false),
	REDCAT_PUBLIC.'.tmp/redcat.svar'
);

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

return $redcat;