<?php namespace Surikat\Component\DependencyInjection;
trait RegistryTrait{
	protected static $__instances = [];
	protected static $__instance;
	static function getStatic(){
		if(func_num_args()==0)
			return isset(self::$__instance)?self::$__instance:self::setStatic();
		else
			return self::getStaticArray(func_get_args());
	}
	static function setStatic(){
		return self::setStaticArray(func_get_args());
	}
	static function getStaticArray($args=null,$class=null){
		$key = empty($args)||$args===[0]?0:Container::hashArguments($args);
		if(!isset($class))
			$class = get_called_class();
		$key = $class.'.'.$key;
		if(!isset(self::$__instances[$key])){
			if($class==__NAMESPACE__.'\Container'&&empty($args)){
				self::$__instances[$key] = new $class();
			}
			else{
				self::$__instances[$key] = Container::getStatic()->factoryDependency($class,$args,true);
			}
		}
		return self::$__instances[$key];
	}
	static function setStaticArray($args=null){
		self::$__instance = self::getStaticArray($args);
		if(method_exists(self::$__instance,'__setStatic'))
			self::$__instance->__setStatic();
		return self::$__instance;
	}
}