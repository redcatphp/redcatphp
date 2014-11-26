<?php namespace Surikat\Control; 
use control;
class Config {
	private static $factory = [];
	static function __callStatic($f,$args){
		if(!isset(self::$factory[$f]))
			self::$factory[$f] = is_file($file=Control::$CWD.str_replace('_',DIRECTORY_SEPARATOR,$f).'/config.php')?include($file):false;		
		$ref =& self::$factory[$f];
		foreach($args as $k)
			if(is_array($ref)&&isset($ref[$k]))
				$ref =& $ref[$k];
			else
				return;
		return $ref;
	}
}