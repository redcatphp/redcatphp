<?php
namespace KungFu\Cms\FrontController;
use Authentic\Auth;
use Authentic\Session;
use Unit\Autoloader;
use Unit\Router;
use Unit\Di;
class Backoffice extends \Unit\FrontController{
	public $pathFS = 'plugin/backoffice';
	function __construct(Router $router,Di $di){
		parent::__construct($router,$di);
		$this
			->append(['new:Unit\RouteMatch\Extension','css|js|png|jpg|jpeg|gif'],['new:KungFu\Cms\FrontController\Synaptic',$this->pathFS])
			->append(['new:KungFu\Cms\RouteMatch\ByTml','',$this->pathFS],function(){
				$this->lock();
				$this->template();
			})
			->append(['new:KungFu\Cms\RouteMatch\ByPhpX','',$this->pathFS],function($paths){
				$this->lock();
				list($dir,$file,$adir,$afile) = $paths;
				chdir($adir);
				include $file;
			})
		;
	}
	function lock(){
		$Session = new Session();
		$Session->setName('surikat_backoffice');
		$Auth = new Auth($Session);
		$Auth->lockServer(Auth::RIGHT_MANAGE);
	}
	function __invoke(){
		Autoloader::getInstance()->addNamespace('',SURIKAT.$this->pathFS.'/php');
		return $this->run(func_get_arg(0));
	}
}