<?php namespace Surikat\Core;
use ReflectionClass;
use ReflectionProperty;
use BadMethodCallException;
use Surikat\Core\DependencyRegistry;
trait DependencyInjector{
	protected $__dependenciesRegistry = [];
	protected $mapDependency = [];
	protected $__protectedIsReadOnly;
	function setDependency($key,$value){
		if($value&&!is_object($value)){
			if(is_array($value)&&!empty($value)){
				$value = (new ReflectionClass(array_shift($value)))->newInstanceArgs($value);
			}
			else{
				$value = new $value();
			}
		}
		$this->__dependenciesRegistry[$key] = $value;
		return $this;
	}
	function getDependency($key){
		if(array_key_exists($key,$this->__dependenciesRegistry[$key]))
			return $this->__dependenciesRegistry[$key];
		$method = 'getDependency'.ucfirst($key);
		if(method_exist($this,$method)){
			return $this->setDependency($key,$this->$method())
						->getDependency($key);
		}
		if(isset($this->mapDependency[$key])){
			return $this->setDependency($key,$this->mapDependency[$key])
						->getDependency($key);
		}
		return $this->defaultDependency($key);
	}
	function defaultDependency($key){
		return $this->setDependency($key,$this->getDependencyInjector())
					->getDependency($key);
	}
	function getDependencyInjector(){
		return DependencyRegistry::instance();
	}
	function __call($f,$args){
		if(strpos($f,'getDependency')===0&&ctype_upper(substr($f,13,1))){
			return $this->getDependency(lcfirst(substr($f,13)));
		}
		if(strpos($f,'setDependency')===0&&ctype_upper(substr($f,13,1))){
			return $this->setDependency(lcfirst(substr($f,13)),$args);
		}
		if(strpos($f,'get')===0&&ctype_upper(substr($f,3,1))){
			if(property_exists($this,$p=substr($f,3)){
				$r = new ReflectionProperty(get_class($this),$p);
				if($r->isPublic()||($this->__protectedIsReadOnly&&$r->isProtected()))
					return $this->$p;
			}
			return $this->getDependency(lcfirst(substr($f,3)));
		}
		if(strpos($f,'set')===0&&ctype_upper(substr($f,3,1))){
			if(property_exists($this,$p=substr($f,3)){
				$r = new ReflectionProperty(get_class($this),$p);
				if($r->isPublic()){
					$this->$p = count($args)>1?$args:array_shift($args);
					return $this;
				}
			}
			return $this->setDependency(lcfirst(substr($f,3)),$args);
		}
		if(method_exists(get_parent_class($this),__FUNCTION__))
			return parent::__call($f,$args);
		throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()',get_class($this),$f));
	}
	
	private static $__instance;
	static function getSelf(){
		return isset(self::$__instance)?self::$__instance:self::setSelf();
	}
	static function setSelf(){
		$args = func_get_args();
		array_unshift($args,get_called_class());
		return self::$__instance = call_user_func_array(['Core\DependencyRegistry','instance'],$args);
	}
	static function __callStatic($f,$args){
		if(strpos($f,'self')===0&&ctype_upper(substr($f,4,1))){
			return call_user_func_array([self::getSelf(),lcfirst(substr($f,4))],$args);
		}
		if(method_exists(get_called_class(),__FUNCTION__))
			return parent::__callStatic($f,$args);
		throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()',get_called_class(),$f));
	}
}