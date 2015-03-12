<?php namespace Surikat\Dispatcher;
class Backoffice extends ViewController{
	protected $pathFS = 'backoffice';
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
						['Dispatcher_Synaptic',$this->pathFS])
			->append(['Route_ByTml','',$this->pathFS],function(){
				$this->User_Auth->lockServer($this->User_Auth->constant('RIGHT_MANAGE'));
				return call_user_func_array($this->getController(),func_get_args());
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