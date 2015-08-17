<?php
namespace Wild\Route;
function autoload($class){
	if(strpos($class,__NAMESPACE__.'\\')===0){
		$file = __DIR__.'/'.substr($class,strlen(__NAMESPACE__)+1).'.php';
		require $file;
		if(!class_exists($class,false)&&!interface_exists($class,false)&&!trait_exists($class,false))
			throw new \Exception('Class "'.$class.'" not found as expected in "'.$file.'"');
	}
}
spl_autoload_register(__NAMESPACE__.'\\autoload');