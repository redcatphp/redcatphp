<?php namespace Surikat\Autoload;
use Exception;
class PSR4{
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
	function findClass($class,$relative_class,$prefix){
		if(isset($this->namespaces[$prefix])){
			foreach($this->namespaces[$prefix] as $base_dir){
				$file = $base_dir.str_replace('\\', '/', $relative_class).'.php';
				if($this->loadFile($file,$class))
					return true;
			}
		}		
	}
	function classLoad($class){
		if(in_array($class,$this->checked))
			return;
		$prefix = $class;
		while($prefix!='\\'){
			$prefix = rtrim($prefix, '\\');
			$pos = strrpos($prefix, '\\');
			if($pos!==false){
				$prefix = substr($class, 0, $pos + 1);
				$relative_class = substr($class, $pos + 1);
			}
			else{
				$prefix = '\\';
				$relative_class = $class;
			}
			if($this->findClass($class,$relative_class,$prefix))
				return;
		}
		$this->extendSuperClass($class);
	}
	function __invoke($class){
		return $this->classLoad($class);
	}
}