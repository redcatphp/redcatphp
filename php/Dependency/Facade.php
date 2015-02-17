<?php namespace Surikat\Dependency;
use BadMethodCallException;
trait Facade{	
	protected static $__instances = [];
	protected static $__instance;
	static function getSelf(){
		return isset(self::$__instance)?self::$__instance:self::setSelf();
	}
	static function setSelf(){
		return self::$__instance = self::registry(get_called_class(),func_get_args());
	}
	static function registry($class,$args=null){
		$key = empty($args)?0:sha1(serialize($args));
		if(!isset(self::$__instances[$key])){
			self::$__instances[$key] = self::factory($class,$args);
		}
		return self::$__instances[$key];
	}
	static function factory($class,$args=null){
		if(is_array($args)&&!empty($args)){
			 return (new ReflectionClass($class))->newInstanceArgs($args);
		}
		else{
			return new $class();
		}
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