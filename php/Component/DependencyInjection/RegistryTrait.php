<?php namespace Surikat\Component\DependencyInjection;
trait RegistryTrait{
	protected static $__instances = [];
	protected static $__instance;
	static function getStatic(){
		return isset(static::$__instance)?static::$__instance:static::setStatic();
	}
	static function setStatic(){
		return static::$__instance = static::registry(func_get_args());
	}
	static function registry($args=null,$class=null){
		$key = empty($args)?0:sha1(serialize($args));
		if(!isset($class))
			$class = get_called_class();
		else
			$key = $class.'.'.$key;
		if(!isset(static::$__instances[$key])){
			if(is_array($args)&&!empty($args))
				static::$__instances[$key] = (new \ReflectionClass($class))->newInstanceArgs($args);
			else
				static::$__instances[$key] = new $class();
		}
		return static::$__instances[$key];
	}
}