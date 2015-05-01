<?php namespace Mvc;
use Database\RedBeanPHP\R;
class Model{
	private $__engine;
	private $__engines;
	private $__callbacks;
	function __construct($engine=null){
		if(!$engine)
			$engine = R::getStatic();
		$this->setEngine($engine);
	}
	function db($dsn=null){
		if(isset($dsn))
			return $this->__engines[$dsn];
		else
			return $this->__engine;
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