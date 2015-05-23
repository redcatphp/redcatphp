<?php namespace KungFu\Cms\Router;
use Unit\Router;
use Unit\RouteMatch\Extension;
use KungFu\Cms\RouteMatch\ByTml;
use KungFu\Cms\RouteMatch\L10n as RouteMatch_L10n;
use KungFu\Cms\Controller\L10n as Controller_L10n;
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
			$this->append($this->backoffice,function(){
				return new Backoffice();
			});
		$this->append('service/',new Service());
		$this->append(new Extension('css|js|png|jpg|jpeg|gif'),function(){
			return new Synaptic();
		});
		if($this->i18nConvention)
			$this->append(new RouteMatch_L10n($this),function(){
				return new L10n();
			});
		$this->append(new ByTml('plugin'),$this);
		$this->append(new ByTml(),$this);
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