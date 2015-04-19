<?php
namespace Surikat\Component\Config;
class DependencyInjection{
	public $dirPath = 'config';
	function getPath($c,$args=null){
		$path = $this->dirPath.'/';
		if(method_exists($c,'getConfigFilename')){
			$path .= $c::getConfigFilename($args);
		}
		else{
			$path .= str_replace('\\','_',$c);
		}
		$path .= '.php';
		return $path;
	}
	function objectFactory($c,$args,$new,$mutator){
		$path = $this->getPath($c,$args);
		if(!method_exists($c,'setConfig')||!is_file($path))
			return $mutator->makeDependency($c,$args,$new);
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
	function includeFree(){
		$args = func_get_arg(1);
		return include(func_get_arg(0));
	}
}