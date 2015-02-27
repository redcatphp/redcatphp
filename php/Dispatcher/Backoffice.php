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
			//->append(['new','Surikat\Route\Extension','css|js|png|jpg|jpeg|gif'],
						//['new','Surikat\Dispatcher\Synaptic',$this->pathFS])
			//->append(['new','Surikat\Route\ByTml','',$this->pathFS],function(){
				//Auth::lockServer(Auth::RIGHT_MANAGE);
				//return call_user_func_array($this,func_get_args());
			//})
			//->append(['new','Surikat\Route\ByPhp','',$this->pathFS],function($paths){
				//Auth::lockServer(Auth::RIGHT_MANAGE);
				//list($dir,$file,$adir,$afile) = $paths;
				//chdir($adir);
				//include $file;
			//})
			
			//->append(Container::get('Route_Extension','css|js|png|jpg|jpeg|gif'),
						//Container::get('Dispatcher_Synaptic',$this->pathFS))
			//->append(Container::get('Route_ByTml','',$this->pathFS),function(){
				//Auth::lockServer(Auth::RIGHT_MANAGE);
				//return call_user_func_array($this,func_get_args());
			//})
			//->append(Container::get('Route_ByPhp','',$this->pathFS),function($paths){
				//Auth::lockServer(Auth::RIGHT_MANAGE);
				//list($dir,$file,$adir,$afile) = $paths;
				//chdir($adir);
				//include $file;
			//})
			
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