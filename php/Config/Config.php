<?php namespace Surikat\Config;
use Surikat\Vars\Arrays;
class Config {
	private static $registry = [];
	protected static $directory = 'config';
	static function STORE($f,$a){
		if(!isset(self::$registry[$f]))
			self::$registry[$f] = new static($f);
		self::$registry[$f]->conf = $a;
		return self::$registry[$f]->put_contents();
	}
	static function __callStatic($f,$args){
		if(!isset(self::$registry[$f]))
			self::$registry[$f] = new static($f);
		$ref =& self::$registry[$f]->conf;
		if(!empty($args)){
			foreach($args as $k){
				if(!is_array($ref))
					return;
				if(array_key_exists($k,$ref)){
					$ref =& $ref[$k];
				}
				else{
					if(self::$registry[$f]->loadConf())
						return static::__callStatic($f,$args);
					return;
				}
			}
		}
		else{
			while(self::$registry[$f]->loadConf()){
				$ref =& self::$registry[$f]->conf;
			}
		}
		return $ref;
	}
	private $conf = false;
	private $file;
	private $key;
	protected $extension = '.php';
	function __construct($f){
		$this->key = $f;
		$this->file = str_replace('_','.',$f).$this->extension;
		$this->dirs = [
			SURIKAT_PATH.static::$directory.'/',
			SURIKAT_SPATH.static::$directory.'/',
		];
		$this->loadConf();
	}
	protected function loadConf(){
		if(empty($this->dirs))
			return;
		foreach(array_keys($this->dirs) as $k){
			$d = $this->dirs[$k];
			unset($this->dirs[$k]);
			if(is_file($inc=$d.$this->file)){
				$conf = $this->getConf($inc);
				if(is_array($conf)){
					if(is_array($this->conf))
						$this->conf = Arrays::merge_recursive($conf,$this->conf);
					else
						$this->conf = $conf;
					break;
				}
			}
		}
		return true;
	}
	protected function getConf($inc){
		return include($inc);
	}
	protected function getString(){
		return '<?php return '.var_export($this->conf,true).';';
	}
	protected function put_contents(){
		return file_put_contents(SURIKAT_PATH.static::$directory.'/'.$this->file,$this->getString());
	}
}