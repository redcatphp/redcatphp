<?php namespace Surikat\Dispatcher;
use Surikat\Templator\Template;
use Controller\Controller;
class ViewController extends Dispatcher{
	protected $Controller;
	protected $View;
	function __invoke(){
		return call_user_func_array([$this,'getController'],func_get_args());
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
			$this->setView(new Template());
			if(isset($this->Controller)){
				$this->View->setController($this->Controller);
				$this->Controller->setView($this->View);
			}
		}
		return $this->View;
	}
}