<?php namespace Unit\Route;
use ObjexLoader\MutatorTrait;
class Route implements RouteInterface{
	use MutatorTrait;
	protected $path;
	protected $uriParams = [];
	function getPath(){
		return $this->path;
	}
	function getParams(){
		return $this->uriParams;
	}
	function count(){
		return count($this->uriParams);
	}
	function __set($k,$v){
		$this->uriParams[$k] = $v;
	}
	function __get($k){
		return isset($this->uriParams[$k])?$this->uriParams[$k]:null;
	}
	function __isset($k){
		return isset($this->uriParams[$k]);
	}
	function __unset($k){
		if(isset($this->uriParams[$k]))
			unset($this->uriParams[$k]);
	}
	function offsetSet($k,$v){
		return $this->__set($k,$v);
	}
	function offsetGet($k){
		return $this->__get($k);
	}
	function offsetExists($k){
		return $this->__isset($k);
	}
	function offsetUnset($k){
		return $this->__unset($k);
	}
	static function __set_state($a){
		$uri = new static();
		foreach($a as $k=>$v)
			$uri->$k = $v;
		return $uri;
	}
}