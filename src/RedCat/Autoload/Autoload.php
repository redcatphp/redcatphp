<?php
/*
 * Autoload
 *
 *   Autoload - Simple and Concise PHP Autoloader
 *       PSR-4 and PSR-0 convention with classMap API and include_path support
 *   PSR-4 convention - for details: see http://www.php-fig.org/psr/psr-4/
 *   PSR-0 convention - for details: see http://www.php-fig.org/psr/psr-0/
 *
 * @package Autoload
 * @version 2.2
 * @link http://github.com/redcatphp/Autoload/
 * @author Jo Surikat <jo@surikat.pro>
 * @website http://redcatphp.com
 */
namespace RedCat\Autoload;
class Autoload{
	protected $namespaces = [];
	protected $checked = [];
	protected $classMap = [];
	protected $useCache = true;
	protected $useIncludePath = false;
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
	function addClass($class,$file){
		$this->classMap[$class] = $file;
	}
	function addClassMap(array $classMap){
		$this->classMap = array_merge($this->classMap, $classMap);
    }
	function useCache($b=true){
		$this->useCache = $b;
	}
	function useIncludePath($b=true){
		$this->useIncludePath = $b;
	}
	protected function loadFile($file,$class){
		if(file_exists($file)
			||($this->useIncludePath&&($file=stream_resolve_include_path($file))))
		{
			require $file;
			if(!class_exists($class,false)&&!interface_exists($class,false)&&!trait_exists($class,false))
				throw new \Exception('Class "'.$class.'" not found as expected in "'.$file.'"');
			if($this->useCache)
				$this->checked[] = $class;
			return true;
		}
		return false;
	}
	protected function findRelative($class,$relative_class,$prefix,$ext){		
		if(isset($this->namespaces[$prefix])){
			foreach($this->namespaces[$prefix] as $base_dir){
				$file = $base_dir.str_replace('\\', '/', $relative_class).$ext;
				if($this->loadFile($file,$class))
					return true;
			}
		}		
	}
	function findClass($class,$ext='.php',$psr0=false){
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
			if($psr0)
				$relative_class = str_replace('_','/',$relative_class);
			if($this->findRelative($class,$relative_class,$prefix,$ext))
				return true;
		}
	}
	function classLoad($class){
		if($this->useCache&&in_array($class,$this->checked))
			return;
		if(isset($this->classMap[$class])&&$this->loadFile($this->classMap[$class],$class))
			return;
		if($this->findClass($class))
			return;
		if($this->findClass($class,true))
			return;
		if(defined('HHVM_VERSION'))
			$this->findClass($class, '.hh');
	}
	function __invoke($class){
		return $this->classLoad($class);
	}
	function splRegister(){
		spl_autoload_register([$this,'classLoad']);
	}
	function splUnregister(){
		spl_autoload_unregister([$this,'classLoad']);
	}
}