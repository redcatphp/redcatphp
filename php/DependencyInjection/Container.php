<?php namespace Surikat\DependencyInjection;
use ReflectionClass;
use Surikat\DependencyInjection\MutatorMagic;
use Surikat\DependencyInjection\Facade;
class Container{
	use MutatorMagic,Facade{
		Facade::__call insteadof MutatorMagic;
		MutatorMagic::__call as ___call;
	}
	static function get($key=null){
		return $key?static::getStatic()->getDependency($key):static::getStatic();
	}
	static function set($key,$value){
		return static::getStatic()->setDependency($key,$value);
	}
	static function factory($value){
		$value = Convention::toClassMixed($value);
		if($value&&!is_object($value)){
			if(is_array($value)&&!empty($value)){
				$value = (new ReflectionClass(array_shift($value)))->newInstanceArgs($value);
			}
			else{
				$value = new $value();
			}
		}
		return $value;
	}
	function defaultDependency($key){
		return $this->defaultNew($key);
	}
}