<?php namespace KungFu\Cms\Router;
use Unit\Router;
use KungFu\Cms\Controller\Templix;
use KungFu\Cms\Service\Service;
class Index extends Router{
	protected $Templix;
	public $i18nConvention;
	public $backoffice = 'backend/';
	function __construct($config=[]){
		foreach($config as $k=>$v){
			$this->$k = $v;
		}
		if($this->backoffice)
			$this->append($this->backoffice,'new:Backoffice');
		$this->append('service/','new:Service');
		$this->append(['new:Unit\RouteMatch\Extension','css|js|png|jpg|jpeg|gif'],'new:KungFu\Cms\Router\Synaptic');
		if($this->i18nConvention)
			$this->append('new:KungFu\Cms\RouteMatch\L10n','new:KungFu\Cms\Controller\L10n');
		$this->append(['new:KungFu\Cms\RouteMatch\ByTml','plugin'],$this);
		$this->append(['new:KungFu\Cms\RouteMatch\ByTml','template'],$this);
	}
	function Templix(){
		return $this->Templix?:$this->Templix = new Templix();
	}
	function __invoke(){
		return $this->Templix();
	}
	function run($path){
		if(!parent::run($path)){
			$this->Templix()->error(404);
			exit;
		}
	}
}