<?php
namespace RedBase\Maphper;
class Repository implements \ArrayAccess{
	private $factory;
	private $primary;
	private $registry = [];
	function __construct($factory,$primary='id'){
		$this->factory = $factory;
		$this->primary = $primary;
	}
	function getPrimaryKey(){
		return $this->primary;
	}
	private function create($table){
		return new Maphper(call_user_func($this->factory,$table,$this->primary));
	}
	function __isset($k){
		return $this->offsetExists($k);
	}
	function __get($k){
		return $this->offsetGet($k);
	}
	function __set($k,$v){
		$this->offsetSet($k,$v);
	}
	function __unset($k){
		$this->offsetUnset($k);
	}
	function offsetExists($k){
		return isset($this->registry[$k]);
	}
	function offsetGet($k){
		if(!isset($this->registry[$k]))
			$this->registry[$k] = $this->create($k);
		return $this->registry[$k];
	}
	function offsetSet($k,$v){
		$this->registry[$k] = $v;
	}
	function offsetUnset($k){
		if(isset($this->registry[$k]))
			unset($this->registry[$k]);
	}
}