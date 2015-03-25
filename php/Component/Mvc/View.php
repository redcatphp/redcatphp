<?php namespace Surikat\Component\Mvc;
use Surikat\Component\Templator\Template;
class View{
	private $__engine;
	function __construct($engine=null){
		if(!$engine)
			$engine = new Template();
		$this->setEngine($engine);
	}
	function setEngine($engine){
		$this->__engine = $engine;
	}
	function __call($k,$args){
		return call_user_func_array([$this->__engine,$k],$args);
	}
	function __isset($k){
		return isset($this->__engine->$k);
	}
	function __unset($k){
		unset($this->__engine->$k);
	}
	function __get($k){
		return  $this->__engine->$k;
	}
	function __set($k,$v){
		$this->__engine->$k = $v;
	}
}