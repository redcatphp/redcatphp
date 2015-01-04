<?php namespace Surikat\Dispatcher;
use Surikat\Core\Dev;
use Surikat\Core\HTTP;
use Surikat\Core\ArrayObject;
use Surikat\View\View;
use Surikat\View\TML;
use Surikat\View\Toolbox as ViewToolbox;
use Route\ByTml;
use Controller\Controller;

class Index extends Dispatcher{
	protected $Controller;
	protected $View;
	protected $useConvention = true;
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
		$this->getView()->onCompile(function($TML){
			ViewToolbox::registerPresenter($TML);
			ViewToolbox::JsIs($TML);
			if(!Dev::has(Dev::VIEW))
				ViewToolbox::autoMIN($TML);
		});
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