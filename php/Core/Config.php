<?php namespace Surikat\Core;
use Surikat\Core\ArrayTools;
class Config extends ArrayObject {
	private static $registry = [];
	private static $directory = 'config';
	static function __callStatic($f,$args){
		if(!isset(self::$registry[$f]))
			self::$registry[$f] = new static($f);
		$ref =& self::$registry[$f]->conf;
		foreach($args as $k){
			if(!is_array($ref))
				return;
			if(array_key_exists($k,$ref)){
				$ref =& $ref[$k];
			}
			else{
				if(self::$registry[$f]->loadConf())
					return self::__callStatic($f,$args);
				return;
			}
		}
		return $ref;
	}
	private $conf = false;
	private $file;
	private $key;
	function __construct($f){
		$this->key = $f;
		$this->file = str_replace('_','.',$f).'.php';
		$this->dirs = [
			SURIKAT_PATH.self::$directory.'/',
			SURIKAT_SPATH.self::$directory.'/',
		];
		$this->loadConf();
	}
	function loadConf(){
		if(empty($this->dirs))
			return;
		foreach(array_keys($this->dirs) as $k){
			$d = $this->dirs[$k];
			unset($this->dirs[$k]);
			if(is_file($inc=$d.$this->file)){
				$conf = include($inc);
				if(is_array($conf)){
					if(is_array($this->conf))
						$this->conf = ArrayTools::array_merge_recursive($conf,$this->conf);
					else
						$this->conf = $conf;
					break;
				}
			}
		}
		return true;
	}
}