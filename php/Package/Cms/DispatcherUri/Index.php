<?php namespace Package\Cms\DispatcherUri;
use FluxServer\Dispatcher\Uri as Dispatcher_Uri;
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
		return $this->FluxServer_Mvc_Controller();
	}
	function convention(){
		$this->append('service/',['Service']);
		$this->append(['FluxServer_Route_Extension','css|js|png|jpg|jpeg|gif'], ['Package_Cms_DispatcherUri_Synaptic']);
		$this->append(['FluxServer_Route_ByTml','plugin'],$this);
		$this->append(['FluxServer_Route_ByTml'],$this);
		if($this->i18nConvention)
			$this->prepend(['FluxServer_Route_I18n',$this],$this);
		if($this->backoffice)
			$this->prepend($this->backoffice,['Package_Cms_DispatcherUri_Backoffice']);
	}
	function run($path){
		if(!parent::run($path)){
			try{
				$this->View->display($c.'.tml');
			}
			catch(DomainException $e){
				http_response_code($e->getMessage());
			}
			exit;
		}
	}
}