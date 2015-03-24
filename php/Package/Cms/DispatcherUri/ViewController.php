<?php namespace Surikat\Package\Cms\DispatcherUri;
use Surikat\Component\Templator\Template;
use Surikat\Component\Mvc\Controller;
use Surikat\Component\Dispatcher\Uri as Dispatcher_Uri;
class ViewController extends Dispatcher_Uri{
	protected $Controller;
	protected $View;
	function __invoke(){
		return call_user_func_array($this->getController(),func_get_args());
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