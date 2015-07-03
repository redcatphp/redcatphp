<?php
namespace KungFu\Cms\FrontController;
use Unit\Router;
use Unit\Di;
class Index extends \Unit\FrontController{
	function __construct(Router $router,Di $di,$config=[]){
		parent::__construct($router,$di);
		$this->map([
			['backend/','new:KungFu\Cms\FrontController\Backoffice'],
			['service/','new:KungFu\Cms\Service\Service'],
			[['new:Unit\RouteMatch\Extension','css|js|png|jpg|jpeg|gif'],'new:KungFu\Cms\FrontController\Synaptic'],
			//[['new:KungFu\Cms\RouteMatch\ByTml','plugin'],[$this,'template']],
			//[['new:KungFu\Cms\RouteMatch\ByTml','surikat/plugin'],[$this,'template']],
			[['new:KungFu\Cms\RouteMatch\ByTmlL10n','','template'],'new:KungFu\TemplixPlugin\TemplixL10n'],
			[['new:KungFu\Cms\RouteMatch\ByTml','','template'],'new:KungFu\TemplixPlugin\Templix'],
			//[['new:KungFu\Cms\RouteMatch\ByTml','surikat/template'],[$this,'template']],
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