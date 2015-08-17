<?php
namespace Wild\Plugin\Cms\FrontController;
use Unit\Router;
use Unit\Di;
class Index extends \Unit\FrontController{
	function __construct(Router $router,Di $di,$config=[]){
		parent::__construct($router,$di);
		$this->map([
			['backend/','new:Wild\Plugin\Cms\FrontController\Backoffice'],
			[['new:Unit\RouteMatch\Extension','css|js|png|jpg|jpeg|gif'],'new:Wild\Plugin\Cms\FrontController\Synaptic'],
			[['new:Wild\Plugin\Cms\RouteMatch\ByTmlL10n','','template'],'new:Wild\Plugin\Templix\TemplixL10n'],
			[['new:Wild\Plugin\Cms\RouteMatch\ByTml','','template'],'new:Wild\Plugin\Templix\Templix'],
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