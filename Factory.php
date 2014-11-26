<?php namespace Surikat;
trait Factory{
	private static $__instances = [];
	static function getInstance($key=0,$args=[]){
		$c = isset(self::$__factory)?self::$__factory:get_called_class();
		if(!isset(self::$__instances[$c]))
			self::$__instances[$c] = [];
		if(!isset(self::$__instances[$c][$key])){
			self::$__instances[$c][$key] = new $c;
			self::$__instances[$c][$key]->setInstanceKey($key);
			call_user_func_array([self::$__instances[$c][$key],'__construct'],$args);
		}
		return self::$__instances[$c][$key];
	}
	protected $__instanceKey;
	protected function setInstanceKey($key){
		$this->__instanceKey = $key;
	}
	protected function getInstanceKey($key){
		return $this->__instanceKey;
	}
}