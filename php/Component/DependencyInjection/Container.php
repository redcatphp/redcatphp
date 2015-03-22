<?php namespace Surikat\DependencyInjection;
use Surikat\DependencyInjection\MutatorMagic;
use Surikat\DependencyInjection\Facade;
class Container{
	use MutatorMagic,Facade{
		Facade::__call insteadof MutatorMagic;
		MutatorMagic::__call as ___call;
	}
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
		return $this->factoryDependency($key,$args);
	}
}