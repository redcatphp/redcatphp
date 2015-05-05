<?php namespace ObjexLoader;
trait RegistryTrait{
	protected static $__instances = [];
	protected static $__instance;
	static function getStatic(){
		if(func_num_args()==0)
			return isset(self::$__instance)?self::$__instance:static::setStatic();
		else
			return static::getStaticArray(func_get_args());
	}
	static function setStatic(){
		return static::setStaticArray(func_get_args());
	}
	static function getStaticArray($args=null,$class=null){
		$key = empty($args)||$args===[0]?0:Container::hashArguments($args);
		if(!isset($class))
			$class = get_called_class();
		$key = $class.'.'.$key;
		if(!isset(self::$__instances[$key]))
			self::$__instances[$key] = static::__getStaticNew($args,$class);
		return self::$__instances[$key];
	}
	static function setStaticArray($args=null){
		self::$__instance = static::getStaticArray($args);
		if(method_exists(self::$__instance,'__setStatic'))
			self::$__instance->__setStatic();
		return self::$__instance;
	}
	static function __getStaticNew($args,$class){
		return Container::getStatic()->factoryDependency($class,$args,true);
	}
}