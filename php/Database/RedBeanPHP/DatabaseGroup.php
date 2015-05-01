<?php
namespace Database\RedBeanPHP;
use DependencyInjection\MutatorCallTrait;
use DependencyInjection\RegistryTrait;
class DatabaseGroup{
	use MutatorCallTrait;
	use RegistryTrait;
	protected $databases = [];
	protected $name = '';
	protected $prefix = '';
	protected $modelNamespaces = [''];	
	function __construct($name=null){
		if(!empty($this->name)){
			$this->name = $name;
			$this->prefix = $name.'.';
		}
	}
	function getDb($key=0){
		if(!isset($this->databases[$key])){
			$this->databases[$key] = $this->__Database($this->prefix.$key);
		}
		return $this->databases[$key];
	}
	function getModelNamespace(){
		return $this->modelNamespaces;
	}
	function setModelNamespace($namespace){
		$this->modelNamespaces = (array)$namespace;
	}
	function addModelNamespace($namespace,$prepend=null){
		if($namespace)
			$namespace = rtrim($namespace,'\\').'\\';
		if($prepend)
			array_unshift($this->modelNamespaces,$namespace);
		else
			array_push($this->modelNamespaces,$namespace);
	}
	function appendModelNamespace($namespace){
		$this->addModelNamespace($namespace,true);
	}
	function prependModelNamespace($namespace){
		$this->addModelNamespace($namespace,false);
	}
}