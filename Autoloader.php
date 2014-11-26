<?php namespace Surikat;
class Autoloader{
	use Factory;
	private $namespaces = [];
	private $superNamespaces = [];
	function __construct($ns=[],$sns=[]){
		foreach($ns as $k=>$v)
			$this->addNamespace($k,$v);
		foreach($sns as $k=>$v)
			$this->addSuperNamespace($k,$v);
		$this->register();
	}
	protected function register(){
		spl_autoload_register([$this,'classLoad']);
		return $this;
	}
	protected function unregister(){
		spl_autoload_unregister([$this,'classLoad']);
		return $this;
	}
	protected function addNamespace($prefix, $base_dir, $prepend = false){
		$prefix = trim($prefix, '\\').'\\';
		$base_dir = rtrim($base_dir, '/').'/';
		if(!isset($this->namespaces[$prefix]))
			$this->namespaces[$prefix] = [];
		if ($prepend)
			array_unshift($this->namespaces[$prefix], $base_dir);
		else
			array_push($this->namespaces[$prefix], $base_dir);
		return $this;
	}
	protected function addSuperNamespace($prefix, $base_dir, $prepend = false){
		$prefix = trim($prefix, '\\').'\\';
		$base_dir = rtrim($base_dir, '/').'/';
		if(!isset($this->superNamespaces[$prefix]))
			$this->superNamespaces[$prefix] = [];
		if ($prepend)
			array_unshift($this->superNamespaces[$prefix], $base_dir);
		else
			array_push($this->superNamespaces[$prefix], $base_dir);
		return $this;
	}
	function classLoad($class){
		$prefix = $class;
		while($prefix!='\\'){
			$prefix = rtrim($prefix, '\\');
			$pos = strrpos($prefix, '\\');
			if($pos!==false){
				$prefix = substr($class, 0, $pos + 1);
				$relative_class = substr($class, $pos + 1);
				if(isset($this->superNamespaces[$prefix])){
					foreach($this->superNamespaces[$prefix] as $base_dir){
						$file = $base_dir.str_replace('\\', '/', $relative_class).'.php';
						if($this->requireFile($file))
							return;
					}
					return;
				}
			}
			else{
				$prefix = '\\';
				$relative_class = $class;
			}
			if(isset($this->namespaces[$prefix])){
				foreach($this->namespaces[$prefix] as $base_dir){
					$file = $base_dir.str_replace('\\', '/', $relative_class).'.php';
					if($this->requireFile($file))
						return;
				}
			}
		}
		$this->extendSuperClass($class);
	}
	private function requireFile($file){
		if(file_exists($file)){
			require $file;
			return true;
		}
		return false;
	}
	private function extendSuperClass($c){
		$pos = strrpos($c,'\\');
		$ns = 'namespace '.($pos?substr($c,0,$pos):'').'{';
		$cn = ($pos?substr($c,$pos+1):$c);
		foreach(array_keys($this->superNamespaces) as $prefix){
			$cl = $prefix.$c;
			if(class_exists($cl)){
				eval($ns.'class '.$cn.' extends \\'.$cl.'{}}');
				break;
			}
			elseif(interface_exists($cl,false)){
				eval($ns.'interface '.$cn.' extends \\'.$cl.'{}}');
				break;
			}
			elseif(trait_exists($cl,false)){
				eval($ns.'trait '.$cn.'{use \\'.$cl.';}}');
				break;
			}
		}
	}
}