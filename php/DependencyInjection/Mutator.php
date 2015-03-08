<?php namespace Surikat\DependencyInjection;
use Exception;
use Surikat\DependencyInjection\Convention;
use Surikat\DependencyInjection\Container;
trait Mutator {
	private $__dependenciesRegistry = [];
	function setDependency($key,$value=null,$rkey=null){
		if(!isset($rkey))
			$rkey = $key;
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
		return $this->__dependenciesRegistry[$rkey] = $value;
	}
	function getDependency($key,$args=null){
		$key = Convention::toMethod($key);
		if(empty($args)){
			$rkey = $key;
		}
		else{
			if(!is_array($args))
				$args = [$args];
			$rkey = $key.'.'.sha1(json_encode($args));
		}
		if(array_key_exists($rkey,$this->__dependenciesRegistry))
			return $this->__dependenciesRegistry[$rkey];
		if(method_exists($this,$key))
			$value = $this->$key($args);
		else
			$value = $this->defaultDependency($key,$args);
		$this->setDependency($key,$value,$rkey);
		return $this->getDependency($key,$args);
	}
	function getNew($key,$args=null){
		$key = Convention::toMethod($key);
		if(method_exists($this,$key))
			return $this->$key($args);
		else
			return $this->defaultNew($key,$args);
	}
	function defaultNew($key,$args=null){
		if(!empty($args)){
			if(!is_array($args))
				$args = [$args];
			array_unshift($args,$key);
			$key = $args;
		}
		return $this->getDependency('Dependency_Container')->factory($key);
	}
	function defaultDependency($key,$args=null){
		return $this->getDependency('Dependency_Container')->getDependency($key,$args);
	}
	function Dependency_Container(){
		return Container::get();
	}
}