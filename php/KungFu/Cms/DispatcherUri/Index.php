<?php namespace KungFu\Cms\DispatcherUri;
use Unit\Dispatcher\Uri as Dispatcher_Uri;
class Index extends Dispatcher_Uri{
	protected $useConvention = true;
	public $i18nConvention;
	public $backoffice = 'backend/';
	function __construct($config=[]){
		foreach($config as $k=>$v){
			$this->$k = $v;
		}
		if($this->useConvention)
			$this->convention();
	}
	function __invoke(){
		return $this->Unit_Mvc_Controller();
	}
	function convention(){
		$this->append('service/',['KungFu_Service']);
		$this->append(['Unit_Route_Extension','css|js|png|jpg|jpeg|gif'], ['KungFu_Cms_DispatcherUri_Synaptic']);
		$this->append(['Unit_Route_ByTml','plugin'],$this);
		$this->append(['Unit_Route_ByTml'],$this);
		if($this->i18nConvention)
			$this->prepend(['Unit_Route_L10n',$this],$this);
		if($this->backoffice)
			$this->prepend($this->backoffice,['KungFu_Cms_DispatcherUri_Backoffice']);
	}
	function run($path){
		if(!parent::run($path)){
			$this->Unit_Mvc_Controller()->error(404);
			exit;
		}
	}
}