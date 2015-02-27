<?php namespace Surikat\Dispatcher;
use Surikat\User\Auth;
use Surikat\User\Session;
use Surikat\DependencyInjection\Container;
class Backoffice extends ViewController{
	protected $pathFS = 'backoffice';
	function __construct(){
		$this->setHooks();
	}
	function setHooks(){
		Container::get('User\Session')->setName('surikat_backoffice');
		$this
			->append(['Route_Extension','css|js|png|jpg|jpeg|gif'],
						['Dispatcher_Synaptic',$this->pathFS])
			->append(['Route_ByTml','',$this->pathFS],function(){
				Auth::lockServer(Auth::RIGHT_MANAGE);
				return call_user_func_array($this,func_get_args());
			})
			->append(['Route_ByPhp','',$this->pathFS],function($paths){
				Auth::lockServer(Auth::RIGHT_MANAGE);
				list($dir,$file,$adir,$afile) = $paths;
				chdir($adir);
				include $file;
			})
		;		
	}
}