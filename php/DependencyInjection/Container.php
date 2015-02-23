<?php namespace Surikat\DependencyInjection;
use Surikat\DependencyInjection\Mutator;
use Surikat\DependencyInjection\MutatorProperty;
use Surikat\DependencyInjection\MutatorCall;
use Surikat\DependencyInjection\Facade;
class Container{
	use Mutator;
	use MutatorProperty;
	use MutatorCall,Facade{
		Facade::__call insteadof MutatorCall;
		MutatorCall::__call as ___call;
	}
	static function get($key=null){
		return $key?self::getStatic()->getDependency($key):self::getStatic();
	}
	static function set($key,$value){
		return self::getStatic()->setDependency($key,$value);
	}
	function defaultDependency($key){
		return $this->defaultNew($key);
	}
}