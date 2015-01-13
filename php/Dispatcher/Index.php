<?php namespace Surikat\Dispatcher;
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
		$this->append('service/',['Surikat\Service\Service','method']);
		$this->append(['new','Surikat\Route\Extension','css|js|png|jpg|jpeg|gif'], ['new','Surikat\Dispatcher\Synaptic']);
		$this->append(['new','Surikat\Route\ByTml','plugin'],$this);
		$this->append(['new','Surikat\Route\ByTml'],$this);
		if($this->i18nConvention)
			$this->prepend(['new','Surikat\Route\I18n',$this],$this);
		if($this->backoffice){
			if($this->backoffice===true)
				$this->backoffice = 'backoffice/';
			$this->prepend($this->backoffice,['Dispatcher\Backoffice','runner']);
		}
	}
	function run($path){
		if(!parent::run($path)){
			$this->getController()->error(404);
		}
	}
}