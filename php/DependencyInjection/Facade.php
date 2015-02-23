<?php namespace Surikat\DependencyInjection;
use BadMethodCallException;
use ReflectionMethod;
use ReflectionClass;
trait Facade{	
	protected static $__instances = [];
	protected static $__instance;
	static function getStatic(){
		return isset(static::$__instance)?static::$__instance:static::setStatic();
	}
	static function setStatic(){
		return static::$__instance = static::registry(func_get_args());
	}
	static function registry($args=null){
		$key = empty($args)?0:sha1(serialize($args));
		$class = get_called_class();
		if(!isset(static::$__instances[$key])){
			if(is_array($args)&&!empty($args))
				static::$__instances[$key] = (new ReflectionClass($class))->newInstanceArgs($args);
			else
				static::$__instances[$key] = new $class();
		}
		return static::$__instances[$key];
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