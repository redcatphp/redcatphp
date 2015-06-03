<?php namespace Unit;
class Autoloader{
	protected $namespaces = [];
	protected $checked = [];
	private static $instance;
	static function register($base_dir,$prefix=''){
		return self::getInstance()->addNamespace($prefix,$base_dir)->splRegister();
	}
	static function getInstance(){
		if(!isset(self::$instance))
			self::$instance = new self;
		return self::$instance;
	}
	function addNamespaces($a){
		foreach($a as $prefix=>$base_dir){
			$this->addNamespace($prefix,$base_dir);
		}
		return $this;
	}
	function addNamespace($prefix, $base_dir, $prepend = false){
		if(is_array($base_dir)){
			foreach($base_dir as $dir){
				$this->addNamespace($prefix, $dir, $prepend);
			}
		}
		else{
			$prefix = trim($prefix, '\\').'\\';
			$base_dir = rtrim($base_dir, '/').'/';
			if(!isset($this->namespaces[$prefix]))
				$this->namespaces[$prefix] = [];
			if ($prepend)
				array_unshift($this->namespaces[$prefix], $base_dir);
			else
				array_push($this->namespaces[$prefix], $base_dir);
		}
		return $this;
	}
	protected function loadFile($file,$class){
		if(file_exists($file)){
			require $file;
			if(!class_exists($class,false)&&!interface_exists($class,false)&&!trait_exists($class,false))
				throw new \Exception('Class "'.$class.'" not found as expected in "'.$file.'"');
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
	}
	function __invoke($class){
		return $this->classLoad($class);
	}
	function splRegister(){
		spl_autoload_register($this);
		return $this;
	}
}