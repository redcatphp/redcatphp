<?php namespace Surikat\Component\DependencyInjection;
trait RegistryTrait{
	protected static $__instances = [];
	protected static $__instance;
	static function getStatic(){
		if(func_num_args()==0)
			return isset(static::$__instance)?static::$__instance:static::setStatic();
		else
			return static::getStaticArray(func_get_args());
	}
	static function setStatic(){
		return static::setStaticArray(func_get_args());
	}
	static function getStaticArray($args=null,$class=null){
		$key = empty($args)||$args==[0]?0:Container::hashArguments($args);
		if(!isset($class))
			$class = get_called_class();
		else
			$key = $class.'.'.$key;
		if(!isset(static::$__instances[$key])){
			if($class==__NAMESPACE__.'\Container'&&empty($args)){
				static::$__instances[$key] = new $class();
			}
			else{
				static::$__instances[$key] = Container::getStatic()->factoryDependency($class,$args,true);
			}
		}
		return static::$__instances[$key];
	}
	static function setStaticArray($args=null){
		return static::$__instance = static::getStaticArray($args);
	}
}