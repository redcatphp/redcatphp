<?php namespace Surikat\DependencyInjection;
use BadMethodCallException;
use ReflectionMethod;
trait Facade{	
	protected static $__instances = [];
	protected static $__instance;
	static function getStatic(){
		return isset(self::$__instance)?self::$__instance:self::setStatic();
	}
	static function setStatic(){
		return self::$__instance = self::registry(get_called_class(),func_get_args());
	}
	static function registry($class,$args=null){
		$key = empty($args)?0:sha1(serialize($args));
		if(!isset(self::$__instances[$key])){
			self::$__instances[$key] = self::factory($class,$args);
		}
		return self::$__instances[$key];
	}
	static function factory($value){
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
	function __call($f,$args){
		$method = '_'.$f;
		if(method_exists($this,$method)&&(new ReflectionMethod($this, $method))->isPublic())
			return call_user_func_array([$this,$method],$args);
		elseif(is_callable('parent::__call'))
			return parent::__call($f,$args);
		elseif(method_exists($this,'___call'))
			return parent::___call($f,$args);
		else
			throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()',get_class($this),$f));
	}
	static function __callStatic($f,$args){
		$method = '_'.$f;
		$c = get_called_class();
		if(method_exists($c,$method)&&(new ReflectionMethod($c, $method))->isPublic())
			return call_user_func_array([$c::getStatic(),$method],$args);
		elseif(is_callable('parent::__callStatic'))
			return parent::__callStatic($f,$args);
		elseif(method_exists($c,'___callStatic'))
			return parent::___callStatic($f,$args);
		else
			throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()',$c,$f));
	}
}