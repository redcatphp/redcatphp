<?php namespace KungFu\Cms\Dispatcher;
use Unit\Dispatcher;
class Backoffice extends Dispatcher{
	public $pathFS = 'plugin/backoffice';
	function __construct(){
		$this->Authentic_Session->setName('surikat_backoffice');
		$this
			->append(['Unit_Route_Extension','css|js|png|jpg|jpeg|gif'],
						['KungFu_Cms_Dispatcher_Synaptic',$this->pathFS])
			->append(['Unit_Route_ByTml','',$this->pathFS],function(){
				$this->Authentic_Auth->lockServer($this->Authentic_Auth->constant('RIGHT_MANAGE'));
				return $this->Templix();
			})
			->append(['Unit_Route_ByPhpX','',$this->pathFS],function($paths){
				$this->Authentic_Auth->lockServer($this->Authentic_Auth->constant('RIGHT_MANAGE'));
				list($dir,$file,$adir,$afile) = $paths;
				chdir($adir);
				include $file;
			})
		;
	}
	function __invoke(){
		\ObjexLoader\Container::get()
			->Unit_AutoloadPsr4
			->addNamespace('',SURIKAT.$this->pathFS.'/php')
		;
		return $this->run(func_get_arg(0));
	}
}