<?php namespace Surikat\Dependency;
use Surikat\Dependency\Injector;
use Surikat\Dependency\Facade;
class Container{
	use Injector;
	use Facade;
	static function get($key=null){
		return $key?self::getSelf()->getDependency($key):self::getSelf();
	}
	static function set($key,$value){
		return self::getSelf()->setDependency($key,$value);
	}
	function defaultDependency($key){
		return $this->defaultNew($key);
	}
}