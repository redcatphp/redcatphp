<?php namespace Surikat\Dispatcher;
use Route\ByTml;
use Route\ByPhp;
use Route\I18n;
use Route\Extension;
use Dispatcher\Synaptic;
class Index extends ViewController{
	protected $useConvention = true;
	protected $i18nConvention;
	//protected $backoffice = true;
	protected $backoffice = 'backend';
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
		$this
			->append(new Extension('css|js|png|jpg|jpeg|gif'), [new Synaptic(),'load'])
			->append(new ByTml('plugin'),$this)
			->append('service/',['Service\\Service','method'])
			->append(new ByTml(),$this)
		;
		if($this->i18nConvention)
			$this->prepend(new I18n($this),$this);
		if($this->backoffice){
			if($this->backoffice===true)
				$this->backoffice = 'backoffice';
			$this->prepend($this->backoffice,['Dispatcher\\Backoffice','runner']);
		}
	}
	function run($path){
		if(!parent::run($path)){
			$this->getController()->error(404);
		}
	}
}