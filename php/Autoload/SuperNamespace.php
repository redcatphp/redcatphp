<?php namespace Surikat\Autoload;
use Exception;
use Surikat\Autoload\Abstraction;
require_once __DIR__.'/Abstraction.php';
class SuperNamespace extends Abstraction{
	protected $superNamespaces = [];
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
				if(isset($this->superNamespaces[$prefix])){
					foreach($this->superNamespaces[$prefix] as $base_dir){
						$file = $base_dir.str_replace('\\', '/', $relative_class).'.php';
						if($this->loadFile($file,$class))
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
					if($this->loadFile($file,$class))
						return;
				}
			}
		}
		$this->extendSuperClass($class);
	}
	function addSuperNamespace($prefix, $base_dir, $prepend = false){
		$prefix = trim($prefix, '\\').'\\';
		$base_dir = rtrim($base_dir, '/').'/';
		if(!isset($this->superNamespaces[$prefix]))
			$this->superNamespaces[$prefix] = [];
		if ($prepend)
			array_unshift($this->superNamespaces[$prefix], $base_dir);
		else
			array_push($this->superNamespaces[$prefix], $base_dir);
	}
	protected function extendSuperClass($c){
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