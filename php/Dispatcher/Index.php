<?php namespace Surikat\Dispatcher;
use Surikat\Core\Dev;
use Surikat\Core\ArrayObject;
use Surikat\View\View;
use Surikat\View\TML;
use Route\ByTml;
use Route\ByPhp;
use Route\I18n;
use Controller\Controller;
class Index extends Dispatcher{
	protected $Controller;
	protected $View;
	protected $useConvention = true;
	protected $i18nConvention;
	protected $backoffice = true;
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
			->prepend(new ByTml('plugin'),$this)
			->prepend('service/',['Service\\Service','method'])
			->append(new ByTml(),$this)
		;
		if($this->i18nConvention)
			$this->prepend(new I18n($this),$this);
		if($this->backoffice){
			if($this->backoffice===true)
				$this->backoffice = 'backoffice';
			$this
				->append(new ByTml($this->backoffice,'backoffice'),$this)
				->append(new ByPhp($this->backoffice,'backoffice'),function($paths){
					list($dir,$file,$adir,$afile) = $paths;
					chdir($adir);
					include $file;
				})
			;
		}
	}
	function run($path){
		if(! parent::run($path) ){
			$this->getController()->error(404);
		}
	}
	function setController($Controller){
		$this->Controller = $Controller;
	}
	function getController(){
		if(!isset($this->Controller)){
			$this->setController(new Controller());
			if(isset($this->View)){
				$this->Controller->setView($this->View);
				$this->View->setController($this->Controller);
			}
		}
		return $this->Controller;
	}
	function setView($View){
		$this->View = $View;
	}
	function getView(){
		if(!isset($this->View)){
			$this->setView(new View());
			if(isset($this->Controller)){
				$this->View->setController($this->Controller);
				$this->Controller->setView($this->View);
			}
		}
		return $this->View;
	}
}