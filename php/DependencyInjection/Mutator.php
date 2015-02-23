<?php namespace Surikat\DependencyInjection;
use Exception;
use Surikat\DependencyInjection\Convention;
use Surikat\DependencyInjection\Container;
trait Mutator {
	private $__dependenciesRegistry = [];
	function setDependency($key,$value=null){
		if(is_object($key)){
			if(!isset($value))
				$value = $key;
			$key = get_class($key);
		}
		$key = Convention::toMethod($key);
		if(!is_object($value))
			$value = $this->getDependency('Dependency_Container')->factory($value);
		$c = Convention::toClass($key);
		if(interface_exists($c)){
			if(!Container::get('Autoload')->instanceOfNS($value,$c)){
				throw new Exception(sprintf('Instance of %s interface was expected, you have to implements it in %s',$c,get_class($value)));
			}
		}
		$this->__dependenciesRegistry[$key] = $value;
		return $this;
	}
	function getDependency($key){
		$key = Convention::toMethod($key);
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
		$key = Convention::toMethod($key);
		if(method_exists($this,$key))
			return $this->$key();
		else
			return $this->defaultNew($key);
	}
	function defaultNew($key){
		return $this->getDependency('Dependency_Container')->factory($key);
	}
	function defaultDependency($key){
		return $this->getDependency('Dependency_Container')->getDependency($key);
	}
	function Dependency_Container(){
		return Container::get();
	}
}