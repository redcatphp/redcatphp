<?php
namespace Surikat\Component\Config;
class DependencyInjection{
	public $dirPath = 'config';
	function objectFactory($c,$args,$new,$mutator){
		if(method_exists($c,'setConfig')){
			$inc = $this->dirPath.'/';
			if(method_exists($c,'getConfigFilename')){
				$inc .= $c::getConfigFilename();
			}
			else{
				$inc .= str_replace('\\','_',$c);
			}
			$inc .= '.php';
			if(is_file($inc)){
				$config = $this->includeFree($inc,$args);
				if($config instanceof ConfigMethods){
					if(isset($config['__construct'])){
						$args = array_merge($config['__construct'],(array)$args);
						unset($config['__construct']);
					}
					$obj = $mutator->factoryDependency($c,$args);
					foreach($config as $method=>$arg){
						$obj->$method($arg);
					}
				}
				else{
					$obj = $mutator->factoryDependency($c,$args);
					$obj->setConfig($config);
				}
			}
		}
		else{
			$obj = $mutator->factoryDependency($c,$args);
		}
		return $obj;
	}
	function includeFree(){
		$args = func_get_arg(1);
		return include(func_get_arg(0));
	}
}