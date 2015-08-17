<?php
namespace Wild\Mvc;
use Wild\Kinetic\Di;
class Model implements \ArrayAccess,\Iterator,\Countable{
	protected $data = [];
	protected $getter;
	protected $setter;
	protected $di;
    function __construct($getter = null, $setter = null, Di $di=null){
        $this->getter = $getter;
        $this->setter = $setter;
        $this->di = $di;
    }
	function __get($k){
		if(!array_key_exists($k,$this->data)){
			$this->data[$k] = is_callable($this->getter)?call_user_func($this->getter,$k,$this):null;
		}
		return $this->data[$k];
	}
	function __set($k, $v){
		if(is_callable($this->setter))
			call_user_func($this->setter,$k,$v,$this);
		$this->data[$k] = $v;
	}
	function __isset($k){
		return isset($this->data[$k]);
	}
	function __unset($k){
		unset($this->data[$k]);
	}
	
	function offsetGet($k){
		return isset($this->data[$k])?$this->data[$k]:null;
	}
	function offsetSet($k, $v){
		$this->data[$k] = $v;
	}
	function offsetExists($k){
		return isset($this->data[$k]);
	}
	function offsetUnset($k){
		unset($this->data[$k]);
	}
	function rewind(){
		reset($this->data);
	}
	function current(){
		return current($this->data);
	}
	function key(){
		return key($this->data);
	}
	function next(){
		return next($this->data);
	}
	function valid(){
		return key($this->data)!==null;
	}
	function count(){
		return count($this->data);
	}
}