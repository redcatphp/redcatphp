<?php namespace Surikat;
use Exception;
trait Factory{
	private static $__instances = [];
	static function getInstance($key=0,$args=[]){
		$c = isset(self::$__factory)?self::$__factory:get_called_class();
		if(!isset(self::$__instances[$c]))
			self::$__instances[$c] = [];
		if(!isset(self::$__instances[$c][$key])){
			self::$__instances[$c][$key] = new $c;
			self::$__instances[$c][$key]->setInstanceKey($key);
			if(method_exists(self::$__instances[$c][$key],'__construct'))
				call_user_func_array([self::$__instances[$c][$key],'__construct'],$args);
		}
		return self::$__instances[$c][$key];
	}
	protected $__instanceKey;
	protected function setInstanceKey($key){
		$this->__instanceKey = $key;
	}
	protected function getInstanceKey(){
		return $this->__instanceKey;
	}
	function __call($func,$args){
		if(!method_exists($this,$func))
			throw new Exception('Call to undefined method '.get_class($this).'::'.$func.'()');
		return call_user_func_array([$this,$func],$args);
	}
	static function __callStatic($func,$args){
		return call_user_func_array([static::getInstance(),$func],$args);
	}
}