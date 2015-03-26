<?php namespace Surikat\Component\Mvc;
use Surikat\Component\Templator\Template;
class View{
	private $__engine;
	private $__callbacks;
	function __construct($engine=null){
		if(!$engine)
			$engine = new Template();
		$this->setEngine($engine);
	}
	function setEngine($engine){
		$this->__engine = $engine;
	}
	function setCallback($method,$call){
		if($call instanceof \Closure){
			$call->bindTo($this);
		}
		$this->__callbacks[$method] = $call;
	}
	function __call($k,$args){
		if(isset($this->__callbacks[$k])){
			$call = $this->__callbacks[$k];
		}
		else{
			$call = [$this->__engine,$k];
		}
		return call_user_func_array($call,$args);
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