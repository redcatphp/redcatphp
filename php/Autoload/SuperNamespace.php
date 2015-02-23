<?php namespace Surikat\Autoload;
use Exception;
use Surikat\Autoload\PSR4;
require_once __DIR__.'/PSR4.php';
class SuperNamespace extends PSR4{
	protected $superNamespaces = [];
	function findClass($class,$relative_class,$prefix){
		if(parent::findClass($class,$relative_class,$prefix))
			return true;
		if($prefix!='\\'&&isset($this->superNamespaces[$prefix])){
			foreach($this->superNamespaces[$prefix] as $base_dir){
				$file = $base_dir.str_replace('\\', '/', $relative_class).'.php';
				if($this->loadFile($file,$class))
					return true;
			}
			return true;
		}
	}
	function instanceOfNS($o,$i){
		if($o instanceof $i)
			return true;
		foreach(array_keys($this->superNamespaces) as $ns){
			if(strpos($i,$ns)!==0&&($o instanceof $ns.$i))
				return true;
		}
		return false;
	}
	function getSuperNamespaces(){
		return $this->superNamespaces;
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