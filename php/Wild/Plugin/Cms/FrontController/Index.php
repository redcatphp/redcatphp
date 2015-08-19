<?php
namespace Wild\Plugin\Cms\FrontController;
use Wild\Route\Router;
use Wild\Kinetic\Di;
class Index extends \Wild\Route\FrontController{
	protected $l10n;
	function __construct(Router $router,Di $di,$l10n=null){
		$this->l10n = $l10n;
		parent::__construct($router,$di);
		$this->map([
			['backend/','new:Wild\Plugin\Cms\FrontController\Backoffice'],
			[['new:Wild\Route\Match\Extension','css|js|png|jpg|jpeg|gif'],'new:Wild\Plugin\Cms\FrontController\Synaptic'],
			[['new:Wild\Plugin\Cms\RouteMatch\ByTml'.($this->l10n?'L10n':''),'','template'],'new:Wild\Plugin\Templix\Templix'.($this->l10n?'L10n':'')],
		]);
	}
	function run($path,$domain=null){
		if(!parent::run($path,$domain)){
			$this->di->create('Wild\Plugin\Templix\Templix')->query(404);
			exit;
		}
		return true;
	}
}