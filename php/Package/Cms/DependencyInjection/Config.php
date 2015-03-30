<?php
namespace Surikat\Package\Cms\DependencyInjection;
class Config{
	public $dirPath = 'config';
	function objectFactory($c,$args,$mutator){
		$obj = $mutator->factoryDependency($c,$args);
		if(method_exists($obj,'setConfig')){
			$this->objectConfigure($obj,$c);
		}
		return $obj;
	}
	function objectConfigure($obj,$c){
		$inc = $this->dirPath.'/'.str_replace('\\','_',$c);
		$obj->setConfig(include($inc));
	}
}