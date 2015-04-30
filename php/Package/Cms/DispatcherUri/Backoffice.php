<?php namespace Surikat\Package\Cms\DispatcherUri;
use Surikat\Component\Dispatcher\Uri as Dispatcher_Uri;
class Backoffice extends Dispatcher_Uri{
	protected $pathFS = 'plugin/backoffice';
	function __construct(){
		$this->setHooks();
	}
	function __invoke(){
		return $this->run(func_get_arg(0));
	}
	function setHooks(){
		$this->User_Session->setName('surikat_backoffice');
		$this
			->append(['Route_Extension','css|js|png|jpg|jpeg|gif'],
						['_Surikat_Package_Cms_DispatcherUri_Synaptic',$this->pathFS])
			->append(['Route_ByTml','',$this->pathFS],function(){
				$this->User_Auth->lockServer($this->User_Auth->constant('RIGHT_MANAGE'));
				return $this->Mvc_Controller();
			})
			->append(['Route_ByPhpX','',$this->pathFS],function($paths){
				$this->User_Auth->lockServer($this->User_Auth->constant('RIGHT_MANAGE'));
				list($dir,$file,$adir,$afile) = $paths;
				chdir($adir);
				include $file;
			})
		;		
	}
}