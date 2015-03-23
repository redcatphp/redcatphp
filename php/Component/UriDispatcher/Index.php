<?php namespace Surikat\Component\UriDispatcher;
class Index extends ViewController{
	protected $useConvention = true;
	protected $i18nConvention;
	//protected $backoffice = true;
	protected $backoffice = 'backend/';
	function __construct(){
		if($this->useConvention)
			$this->convention();
		$this->setHooks();
	}
	function setHooks(){
		
	}
	function __invoke(){
		return call_user_func_array([$this,'getController'],func_get_args());
	}
	function convention(){
		$this->append('service/',['Service']);
		$this->append(['Route_Extension','css|js|png|jpg|jpeg|gif'], ['UriDispatcher_Synaptic']);
		$this->append(['Route_ByTml','plugin'],$this);
		$this->append(['Route_ByTml'],$this);
		if($this->i18nConvention)
			$this->prepend(['Route_I18n',$this],$this);
		if($this->backoffice){
			if($this->backoffice===true)
				$this->backoffice = 'backoffice/';
			$this->prepend($this->backoffice,['UriDispatcher_Backoffice']);
		}
	}
	function run($path){
		if(!parent::run($path)){
			$this->getController()->error(404);
		}
	}
}