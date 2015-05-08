<?php namespace KungFu\Cms\Dispatcher;
use Unit\Dispatcher;
use Unit\Route\Extension;
use Unit\Route\ByTml;
use Unit\Route\L10n as Route_L10n;
use Templix\Templix;
use KungFu\Cms\Controller\L10n as Controller_L10n;
use KungFu\Service\Service;
class Index extends Dispatcher{
	protected $Templix;
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
	function Templix(){
		return $this->Templix?:$this->Templix = new Templix();
	}
	function __invoke(){
		return $this->Templix();
	}
	function convention(){
		$this->append('service/',new Service());
		$this->append(new Extension('css|js|png|jpg|jpeg|gif'), new Synaptic());
		if($this->i18nConvention)
			$this->append(new Route_L10n($this),new L10n(['backoffice'=>$this->backoffice]));
		$this->append(new ByTml('plugin'),$this);
		$this->append(new ByTml(),$this);
		if($this->backoffice)
			$this->prepend($this->backoffice,new Backoffice());
	}
	function run($path){
		if(!parent::run($path)){
			$this->Templix()->error(404);
			exit;
		}
	}
}