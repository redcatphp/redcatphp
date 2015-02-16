<?php namespace Surikat\Dependency;
use BadMethodCallException;
trait Facade{	
	private static $__instance;
	static function getSelf(){
		return isset(self::$__instance)?self::$__instance:self::setSelf();
	}
	static function setSelf(){
		$args = func_get_args();
		array_unshift($args,get_called_class());
		return self::$__instance = call_user_func_array(['Dependency\Registry','instance'],$args);
	}
	static function __callStatic($f,$args){
		if(strpos($f,'self')===0&&ctype_upper(substr($f,4,1))){
			return call_user_func_array([self::getSelf(),lcfirst(substr($f,4))],$args);
		}
		if(method_exists(get_called_class(),__FUNCTION__))
			return parent::__callStatic($f,$args);
		throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()',get_called_class(),$f));
	}
}