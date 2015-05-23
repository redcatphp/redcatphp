<?php
namespace Unit;
class Model implements \ArrayAccess{
	private $dataSource;
	private $data = [];
	private $getter;
	private $setter;
	private $dataState = [];
    function __construct($dataSource = null, $getter = null, $setter = null){
        $this->dataSource = $dataSource;
        $this->getter = $getter;
        $this->setter = $setter;
    }
    function getDataSource(){
		return $this->dataSource;
	}
	
	function set($k,$v){
		$this->dataState[$k] = $v;
	}
	function get($k){
		return isset($this->dataState[$k])?$this->dataState[$k]:null;
	}
	function __call($func,$args){
		if(strpos('get')===0){
			return $this->get(substr($func,3));
		}
		elseif(strpos('set')===0){
			return $this->set(substr($func,3),array_shift($args));
		}
		else{
			throw new \BadMethodCallException('Call to undefined method '.get_class($this).'::'.$func.'()');
		}
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
}