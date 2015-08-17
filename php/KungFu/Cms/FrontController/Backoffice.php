<?php
namespace KungFu\Cms\FrontController;
use Wild\Identify\Auth;
use Wild\Identify\Session;
use Unit\Autoloader;
use Unit\Router;
use Unit\Di;
class Backoffice extends \Unit\FrontController{
	public $pathFS = 'surikat/plugin/backoffice';
	function __construct(Router $router,Di $di){
		parent::__construct($router,$di);
		$this
			->append(['new:Unit\RouteMatch\Extension','css|js|png|jpg|jpeg|gif'],['new:KungFu\Cms\FrontController\Synaptic',$this->pathFS])
			->append(['new:KungFu\Cms\RouteMatch\ByTml','',$this->pathFS],function(){
				$this->lock();
				return 'new:KungFu\TemplixPlugin\Templix';
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
		$Session = $this->di->create('Wild\Identify\Session',['name'=>'surikat_backoffice']);
		$Auth = $this->di->create('Wild\Identify\Auth',[$Session]);
		$AuthServer = $this->di->create('Wild\Identify\AuthServer',[$Auth]);
		$AuthServer->htmlLock('RIGHT_MANAGE',true);
	}
	function __invoke($uri,$domain=null){
		Autoloader::getInstance()->addNamespace('',SURIKAT_CWD.$this->pathFS.'/php');
		return $this->run($uri);
	}
	function run($path,$domain=null){
		if(!parent::run($path,$domain)){
			$this->di->create('KungFu\TemplixPlugin\Templix')->query(404);
			exit;
		}
		return true;
	}
}