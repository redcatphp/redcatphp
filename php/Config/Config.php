<?php namespace Surikat\Config;
abstract class Config {
	private static $registry = [];
	private static $directory = 'config';
	static function __callStatic($f,$args){
		if(!isset(self::$registry[$f]))
			self::$registry[$f] = is_file($file=SURIKAT_PATH.self::$directory.'/'.str_replace('_','.',$f).'.php')?include($file):false;
		$ref =& self::$registry[$f];
		foreach($args as $k)
			if(is_array($ref)&&isset($ref[$k]))
				$ref =& $ref[$k];
			else
				return;
		return $ref;
	}
}