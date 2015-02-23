<?php namespace Surikat\DependencyInjection;
use Surikat\DependencyInjection\MutatorMagic;
use Surikat\DependencyInjection\Facade;
class Container{
	use MutatorMagic;
	use Facade;
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