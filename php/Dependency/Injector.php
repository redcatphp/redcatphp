<?php namespace Surikat\Dependency;
use ReflectionClass;
use ReflectionProperty;
use BadMethodCallException;
use Dependency\Registry;
trait Injector{
	protected $__dependenciesRegistry = [];
	protected $__protectedIsReadOnly;
	private function dependencyMixedToObject($value){
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
	function setDependency($key,$value){
		$this->__dependenciesRegistry[$key] = $this->dependencyMixedToObject($value);
		return $this;
	}
	function getDependency($key){
		if(array_key_exists($key,$this->__dependenciesRegistry))
			return $this->__dependenciesRegistry[$key];
		$method = 'getDependency'.ucfirst($key);
		if(method_exists($this,$method)){
			return $this->setDependency($key,$this->$method())
						->getDependency($key);
		}
		if(property_exists($this,'mapDependency')&&isset($this->mapDependency[$key])){
			return $this->setDependency($key,$this->mapDependency[$key])
						->getDependency($key);
		}
		return $this->setDependency($key,$this->defaultDependency($key))
					->getDependency($key);
	}
	function getNew($key){
		$method = 'getDependency'.ucfirst($key);
		if(method_exists($this,$method))
			$new = $this->$method();
		elseif(property_exists($this,'mapDependency')&&isset($this->mapDependency[$key]))
			$new = $this->mapDependency[$key];
		else
			$new = $this->defaultNew($key);
		return $this->dependencyMixedToObject($new);
	}
	function defaultNew($key){
		if(strpos($key,'\\')===false)
			$key = ucfirst($key).'\\'.ucfirst($key);
		return Registry::instance($key);
	}
	function defaultDependency($key){
		return $this->getDependencyInjector()->getDependency($key);
	}
	function getDependencyInjector(){
		return Registry::instance();
	}
	function __call($f,$args){
		if(strpos($f,'getDependency')===0&&ctype_upper(substr($f,13,1))){
			return $this->getDependency(lcfirst(substr($f,13)));
		}
		if(strpos($f,'setDependency')===0&&ctype_upper(substr($f,13,1))){
			return $this->setDependency(lcfirst(substr($f,13)),$args);
		}
		if(strpos($f,'get')===0&&ctype_upper(substr($f,3,1))){
			if(property_exists($this,$p=substr($f,3))){
				$r = new ReflectionProperty(get_class($this),$p);
				if($r->isPublic()||($this->__protectedIsReadOnly&&$r->isProtected()))
					return $this->$p;
			}
			return $this->getDependency(lcfirst(substr($f,3)));
		}
		if(strpos($f,'set')===0&&ctype_upper(substr($f,3,1))){
			if(property_exists($this,$p=substr($f,3))){
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
}