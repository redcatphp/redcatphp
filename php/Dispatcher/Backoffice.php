<?php namespace Surikat\Dispatcher;
class Backoffice extends ViewController{
	protected $pathFS = 'backoffice';
	function __construct(){
		$this->setHooks();
	}
	function setHooks(){
		$this
			->append(['new','Surikat\Route\Extension','css|js|png|jpg|jpeg|gif'],
						['new','Surikat\Dispatcher\Synaptic',$this->pathFS])
			->append(['new','Surikat\Route\ByTml','',$this->pathFS],$this)
			->append(['new','Surikat\Route\ByPhp','',$this->pathFS],function($paths){
				list($dir,$file,$adir,$afile) = $paths;
				chdir($adir);
				include $file;
			})
		;		
	}
}