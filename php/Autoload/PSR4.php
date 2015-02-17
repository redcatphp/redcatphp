<?php namespace Surikat\Autoload;
use Exception;
use Surikat\Autoload\Abstraction;
require_once __DIR__.'/Abstraction.php';
class PSR4 extends Abstraction{
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
			if(isset($this->namespaces[$prefix])){
				foreach($this->namespaces[$prefix] as $base_dir){
					$file = $base_dir.str_replace('\\', '/', $relative_class).'.php';
					if($this->loadFile($file,$class))
						return;
				}
			}
		}
	}
}