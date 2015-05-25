<?php
namespace KungFu\Cms\FrontController;
class Index extends \Unit\FrontController{
	function build(){
		$this->map([
			['backend/','new:Backoffice'],
			['service/','new:KungFu\Cms\Service\Service'],
			[['new:Unit\RouteMatch\Extension','css|js|png|jpg|jpeg|gif'],'new:KungFu\Cms\Router\Synaptic'],
			['new:KungFu\Cms\RouteMatch\L10n','new:KungFu\Cms\Controller\L10n'],
			[['new:KungFu\Cms\RouteMatch\ByTml','plugin'],'new:KungFu\Cms\Controller\Templix'],
			[['new:KungFu\Cms\RouteMatch\ByTml','template'],'new:KungFu\Cms\Controller\Templix'],
		]);
	}
	function run($path,$domain=null){
		if(!parent::run($path,$domain)){
			http_response_code(404);
			//$this->Templix()->error(404);
			exit;
		}
	}
}