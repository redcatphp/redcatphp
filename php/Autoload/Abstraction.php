<?php namespace Surikat\Autoload;
use Exception;
abstract class Abstraction{
	protected $namespaces = [];
	protected $checked = [];
	function addNamespace($prefix, $base_dir, $prepend = false){
		$prefix = trim($prefix, '\\').'\\';
		$base_dir = rtrim($base_dir, '/').'/';
		if(!isset($this->namespaces[$prefix]))
			$this->namespaces[$prefix] = [];
		if ($prepend)
			array_unshift($this->namespaces[$prefix], $base_dir);
		else
			array_push($this->namespaces[$prefix], $base_dir);
	}
	protected function loadFile($file,$class){
		if(file_exists($file)){
			require $file;
			if(!class_exists($class,false)&&!interface_exists($class,false)&&!trait_exists($class,false))
				throw new Exception('Class "'.$class.'" not found as expected in "'.$file.'"');
			$this->checked[] = $class;
			return true;
		}
		return false;
	}
	function __invoke($class){
		return $this->classLoad($class);
	}
	abstract function classLoad($class);
}