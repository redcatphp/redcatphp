<?php
namespace RedCat\Plugin\FrontController;
use RedCat\Identify\Auth;
use RedCat\Identify\Session;
use RedCat\Autoload\Autoload;
use RedCat\Route\Router;
use RedCat\Wire\Di;
class Backoffice extends \RedCat\Route\FrontController{
	public $pathFS = 'plugin/backoffice';
	function __construct(Router $router,Di $di){
		parent::__construct($router,$di);
		$this
			->append(['new:RedCat\Route\Match\Extension','css|js|png|jpg|jpeg|gif'],['new:RedCat\Plugin\FrontController\Synaptic',$this->pathFS])
			->append(['new:RedCat\Plugin\RouteMatch\ByTmlL10n','',$this->pathFS],function(){
				$this->lock();
				return 'new:RedCat\Plugin\Templix\TemplixL10n';
			})
			->append(['new:RedCat\Plugin\RouteMatch\ByPhpX','',$this->pathFS],function($paths){
				$this->lock();
				list($dir,$file,$adir,$afile) = $paths;
				chdir($adir);
				include $file;
			})
		;
	}
	function lock(){
		$Session = $this->di->create('RedCat\Identify\Session',['name'=>'redcat_backoffice']);
		$Auth = $this->di->create('RedCat\Identify\Auth',[$Session]);
		$AuthServer = $this->di->create('RedCat\Identify\AuthServer',[$Auth]);
		$AuthServer->htmlLock('RIGHT_MANAGE',true);
	}
	function __invoke($uri,$domain=null){
		Autoload::getInstance()->addNamespace('',REDCAT_CWD.$this->pathFS.'/php');
		return $this->run($uri);
	}
	function run($path,$domain=null){
		if(!parent::run($path,$domain)){
			$this->di->create('RedCat\Plugin\Templix\Templix')->query(404);
			exit;
		}
		return true;
	}
}