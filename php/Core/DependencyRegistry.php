<?php namespace Surikat\Core;
use Surikat\Core\DependencyInjector;
class DependencyRegistry{
	use DependencyInjector;
	protected $mapDependency = [];
	private $__objectsRegistry = [];
	static function instance(){
		$args = func_get_args();
		$class = array_shift($args);
		$key = empty($args)?0:sha1(serialize($args));
		return self::instanceKey($class,$key,$args);
	}
	static function instanceKey($class=null,$key=0,$args=null){
		if(!$class)
			$class = get_called_class();
		if(!isset(self::$__objectsRegistry[$class][$key])){
			self::$__objectsRegistry[$class][$key] = self::factory($class,$args);
		}
		return self::$__objectsRegistry[$class][$key];
	}
	static function factory($class,$args=null){
		if(is_array($args)&&!empty($args)){
			 return (new ReflectionClass($class))->newInstanceArgs($args);
		}
		else{
			return = new $class();
		}
	}
	function defaultDependency($key){
		return self::factory(ucfirst($key).'\\'.ucfirst($key));
	}
}