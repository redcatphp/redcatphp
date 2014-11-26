<?php namespace Surikat\Control; 
abstract class OS{
	static function add_include_path($path){
		if(!is_array($path))
			$path = is_string($path)?explode(PATH_SEPARATOR,$path):(array)$path;
		$inc_path = explode(PATH_SEPARATOR,get_include_path());
		array_unshift($path,array_shift($inc_path));
		while(!empty($inc_path)) array_push($path,array_shift($inc_path));
		set_include_path(implode(PATH_SEPARATOR,$path));
	}
	static function remove_include_path($path){
		$inc_path = explode(PATH_SEPARATOR,get_include_path());
		if(!is_array($path))
			$path = is_string($path)?explode(PATH_SEPARATOR,$path):(array)$path;
		foreach(array_keys($inc_path) as $i)
			if(in_array($inc_path[$i],$path)) unset($inc_path[$i]);
		set_include_path(implode(PATH_SEPARATOR,$inc_path));
	}
	static function isRelativePath($path){
		return (!self::isWindows()&&stripos($path,'/')!==0)||stripos($path,':')!==1;
	}
	static function isWindows(){
		return PHP_OS=="WINNT"||PHP_OS=="Windows"||PHP_OS=="WIN32";
	}
	static function isUnix(){
		return !self::isWindows();
	}
}
