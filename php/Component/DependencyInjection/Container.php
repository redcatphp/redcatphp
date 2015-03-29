<?php namespace Surikat\Component\DependencyInjection;
class Container{
	use MutatorMagic;
	use Registry;
	static function get(){
		$args = func_get_args();
		if(empty($args))
			return static::getStatic();
		$key = array_shift($args);
		return static::getStatic()->getDependency($key,$args);
	}
	static function set($key,$value){
		return static::getStatic()->setDependency($key,$value);
	}
	function defaultDependency($key,$args=null){
		return $this->__factoryDependency(self::__interfaceSubstitutionDefaultClass($this->__prefixClassName($key)),$args);
	}
}