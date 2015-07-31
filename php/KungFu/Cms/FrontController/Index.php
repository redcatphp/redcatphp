<?php
namespace KungFu\Cms\FrontController;
use Unit\Router;
use Unit\Di;
class Index extends \Unit\FrontController{
	function __construct(Router $router,Di $di,$config=[]){
		parent::__construct($router,$di);
		$this->map([
			['backend/','new:KungFu\Cms\FrontController\Backoffice'],
			[['new:Unit\RouteMatch\Extension','css|js|png|jpg|jpeg|gif'],'new:KungFu\Cms\FrontController\Synaptic'],
			[['new:KungFu\Cms\RouteMatch\ByTmlL10n','','template'],'new:KungFu\TemplixPlugin\TemplixL10n'],
			[['new:KungFu\Cms\RouteMatch\ByTml','','template'],'new:KungFu\TemplixPlugin\Templix'],
		]);
	}
	function run($path,$domain=null){
		if(!parent::run($path,$domain)){
			$this->di->create('KungFu\TemplixPlugin\Templix')->query(404);
			exit;
		}
		return true;
	}
}