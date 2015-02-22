<?php namespace Surikat\Dependency;
use ReflectionClass;
use Surikat\Dependency\Container;
trait Mutator{
	private $__dependenciesRegistry = [];
	private $__metaRegistry = [];
	public $Dependency_Container;
	private function __dependencyMixedToObject($value){
		if($value&&!is_object($value)){
			if(is_array($value)&&!empty($value)){
				$value = (new ReflectionClass(str_replace('_','\\',array_shift($value))))->newInstanceArgs($value);
			}
			else{
				$value = str_replace('_','\\',$value);
				$value = new $value();
			}
		}
		return $value;
	}
	function setDependency($key,$value){
		$key = str_replace('\\','_',$key);
		$this->__dependenciesRegistry[$key] = $this->__dependencyMixedToObject($value);
		return $this;
	}
	function getDependency($key){
		$key = str_replace('\\','_',$key);
		if(array_key_exists($key,$this->__dependenciesRegistry))
			return $this->__dependenciesRegistry[$key];
		if(method_exists($this,$key))
			$value = $this->$key();
		else
			$value = $this->defaultDependency($key);
		$this->setDependency($key,$value);
		return $this->getDependency($key);
	}
	function getNew($key){
		$key = str_replace('\\','_',$key);
		if(method_exists($this,$key))
			$new = $this->$key();
		else
			$new = $this->defaultNew($key);
		return $this->__dependencyMixedToObject($new);
	}
	function defaultNew($key){
		$key = str_replace('_','\\',$key);
		if(strpos($key,'\\')===false)
			$key = $key.'\\'.$key;
		return $this->Dependency_Container()->factory($key);
	}
	function defaultDependency($key){
		$key = str_replace('_','\\',$key);
		return $this->Dependency_Container()->getDependency($key);
	}
	function Dependency_Container(){
		if(func_num_args()){
			$this->Dependency_Container = $this->__dependencyMixedToObject(func_get_arg(0));
		}
		else{
			if(!isset($this->Dependency_Container))
				$this->Dependency_Container = Container::getStatic();
		}
		return $this->Dependency_Container;
	}
}