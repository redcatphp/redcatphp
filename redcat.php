<?php
define('REDCAT',__DIR__.'/');
define('REDCAT_CWD',getcwd().'/');

require_once __DIR__.'/vendor/autoload.php';

$configMap = [REDCAT.'.config.php'];
if(REDCAT!=REDCAT_CWD)
	$configMap[] = REDCAT_CWD.'.config.php';

$redcat = RedCat\Wire\Di::load($configMap);

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