<?php namespace Surikat\Dependency;
use ReflectionClass;
use ReflectionProperty;
use BadMethodCallException;
use Dependency\Container;
trait Injector{
	private $__dependenciesRegistry = [];
	private $__metaRegistry = [];
	private function __dependencyMixedToObject($value){
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
		$this->__dependenciesRegistry[$key] = $this->__dependencyMixedToObject($value);
		return $this;
	}
	function getDependency($key){
		if(array_key_exists($key,$this->__dependenciesRegistry))
			return $this->__dependenciesRegistry[$key];
		$method = str_replace('\\','_',$key);
		if(method_exists($this,$method))
			$value = $this->$method();
		else
			$value = $this->defaultDependency($key);
		$this->setDependency($key,$value);
		return $this->getDependency($key);
	}
	function getNew($key){
		$method = str_replace('\\','_',$key);
		if(method_exists($this,$method))
			$new = $this->$method();
		else
			$new = $this->defaultNew($key);
		return $this->__dependencyMixedToObject($new);
	}
	function defaultNew($key){
		$key = str_replace('_','\\',$key);
		if(strpos($key,'\\')===false)
			$key = $key.'\\'.$key;
		return Container::factory($key);
	}
	function defaultDependency($key){
		return Container::get($key);
	}
	function __get($k){
		if(ctype_upper($k{0}))
			return $this->getDependency($k);
		elseif(is_callable('parent::__get'))
			return parent::__get($k);
		else
			return isset($this->__metaRegistry[$k])?$this->__metaRegistry[$k]:null;
	}
	function __set($k,$v){
		if(ctype_upper($k{0}))
			$this->setDependency($k,$v);
		elseif(is_callable('parent::__set'))
			parent::__set($k,$v);
		else
			$this->__metaRegistry[$k] = $v;
	}
}