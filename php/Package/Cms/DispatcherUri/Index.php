<?php namespace Surikat\Package\Cms\DispatcherUri;
class Index extends ViewController{
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
		return call_user_func_array([$this,'getController'],func_get_args());
	}
	function convention(){
		$this->append('service/',['Service']);
		$this->append(['Route_Extension','css|js|png|jpg|jpeg|gif'], ['_Surikat_Package_Cms_DispatcherUri_Synaptic']);
		$this->append(['Route_ByTml','plugin'],$this);
		$this->append(['Route_ByTml'],$this);
		if($this->i18nConvention)
			$this->prepend(['Route_I18n',$this],$this);
		if($this->backoffice)
			$this->prepend($this->backoffice,['_Surikat_Package_Cms_DispatcherUri_Backoffice']);
	}
	function run($path){
		if(!parent::run($path)){
			$this->getController()->error(404);
		}
	}
}