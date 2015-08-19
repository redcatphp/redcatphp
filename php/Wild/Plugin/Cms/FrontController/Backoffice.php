<?php
namespace Wild\Plugin\Cms\FrontController;
use Wild\Identify\Auth;
use Wild\Identify\Session;
use Wild\Autoload\Autoload;
use Wild\Route\Router;
use Wild\Kinetic\Di;
class Backoffice extends \Wild\Route\FrontController{
	public $pathFS = 'surikat/plugin/backoffice';
	function __construct(Router $router,Di $di){
		parent::__construct($router,$di);
		$this
			->append(['new:Wild\Route\Match\Extension','css|js|png|jpg|jpeg|gif'],['new:Wild\Plugin\Cms\FrontController\Synaptic',$this->pathFS])
			->append(['new:Wild\Plugin\Cms\RouteMatch\ByTmlL10n','',$this->pathFS],function(){
				$this->lock();
				return 'new:Wild\Plugin\Templix\TemplixL10n';
			})
			->append(['new:Wild\Plugin\Cms\RouteMatch\ByPhpX','',$this->pathFS],function($paths){
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
		Autoload::getInstance()->addNamespace('',SURIKAT_CWD.$this->pathFS.'/php');
		return $this->run($uri);
	}
	function run($path,$domain=null){
		if(!parent::run($path,$domain)){
			$this->di->create('Wild\Plugin\Templix\Templix')->query(404);
			exit;
		}
		return true;
	}
}