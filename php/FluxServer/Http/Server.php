<?php
namespace FluxServer\Http;
use ArrayAccess;
class Server implements ArrayAccess{
	protected $data;
	function __construct($data=null){
		if(!$data)
			$data = $_SERVER;
		$this->data = $data;
	}
	function offsetExists($k){
		return isset($this->data[$k]);
	}
	function offsetUnset($k){
		if(isset($this->data[$k]))
			unset($this->data[$k]);
	}
	function offsetGet($k){
		return isset($this->data[$k])?$this->data[$k]:null;
	}
	function offsetSet($k,$v){
		$this->data[$k] = $v;
	}
	function __isset($k){
		return $this->offsetExists($this->_dolphin($k));
	}
	function __unset($k){
		return $this->offsetUnset($this->_dolphin($k));
	}
	function __get($k){
		return $this->offsetGet($this->_dolphin($k));
	}
	function __set($k,$v){
		return $this->offsetSet($this->_dolphin($k));
	}
	function overrideGlobal(){
		foreach($this->data as $k=>$v){
			$_SERVER[$k] = $v;
		}
	}
	private function _dolphin($k){
		return strtoupper(str_replace(' ', '_', preg_replace('/([a-z])([A-Z])/', '$1 $2', $k)));
	}
}