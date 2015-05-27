<?php
namespace KungFu\Cms\FrontController;
use Unit\Router;
use Unit\DiContainer;
class Index extends \Unit\FrontController{
	function __construct(Router $router,DiContainer $di){
		parent::__construct($router,$di);
		$this->map([
			//['backend/','new:Backoffice'],
			//['service/','new:KungFu\Cms\Service\Service'],
			[['new:Unit\RouteMatch\Extension','css|js|png|jpg|jpeg|gif'],'new:KungFu\Cms\FrontController\Synaptic'],
			//['new:KungFu\Cms\RouteMatch\L10n','new:KungFu\Cms\Controller\L10n'],
			//[['new:KungFu\Cms\RouteMatch\ByTml','plugin'],[$this,'template']],
			//[['new:KungFu\Cms\RouteMatch\ByTml','Surikat/plugin'],[$this,'template']],
			[['new:KungFu\Cms\RouteMatch\ByTml','','template'],'new:KungFu\TemplixPlugin\Templix'],
			//[['new:KungFu\Cms\RouteMatch\ByTml','Surikat/template'],[$this,'template']],
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