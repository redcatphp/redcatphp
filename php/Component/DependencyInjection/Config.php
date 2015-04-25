<?php
namespace Surikat\Component\DependencyInjection;
class Config{
	public static $dirRoots = [];
	public $dirPath = 'config';
	static function intialize(){
		self::$dirRoots[] = SURIKAT_PATH;
		self::$dirRoots[] = SURIKAT_SPATH;
	}
	function getConfigFilename($c,$args=null){
		return strtolower(str_replace('\\','.',$c));
	}
	function getConfigPath($c,$args=null){
		$paths = [];
		foreach(self::$dirRoots as $dir){
			$path = $dir.$this->dirPath.'/';
			if(method_exists($c,'getConfigFilename'))
				$path .= $c::getConfigFilename($args);
			else
				$path .= $this->getConfigFilename($c,$args);
			$path .= '.php';
			$paths[] = $path;
		}
		return $paths;
	}
	function objectFactory($c,$args,$new,$mutator){
		if(method_exists($c,'setConfig')){
			if(method_exists($c,'getConfigPath'))
				$paths = $c::getConfigPath($args);
			else
				$paths = $this->getConfigPath($c,$args);
			foreach((array)$paths as $path){
				if(is_file($path)){
					$config = $this->includeFree($path,$args);
					if($config instanceof ConfigMethods){
						if(isset($config['__construct'])){
							$args = array_merge($config['__construct'],(array)$args);
							unset($config['__construct']);
						}
						$obj = $mutator->makeDependency($c,$args,$new);
						foreach($config as $method=>$arg){
							$obj->$method($arg);
						}
					}
					else{
						$obj = $mutator->makeDependency($c,$args,$new);
						$obj->setConfig($config);
					}
					return $obj;
				}
			}
			$obj = $mutator->makeDependency($c,$args,$new);
			$obj->setConfig(null);
			return $obj;
		}
		return $mutator->makeDependency($c,$args,$new);
	}
	function includeFree(){
		$args = func_get_arg(1);
		return include(func_get_arg(0));
	}
}
Config::intialize();