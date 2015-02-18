<?php namespace Surikat\Templator;
use ArrayIterator;
class Iterator implements \ArrayAccess,\IteratorAggregate{
	private $__dataNodes;
	function __construct( array $nodes){
		$this->__dataNodes = $nodes;
	}
	function getIterator(){
		return new ArrayIterator($this->__dataNodes);
	}
	function offsetExists($k){
		return isset($this->__dataNodes[$k]);
	}
	function offsetSet($k,$v){
		if($k===null)
			$this->__dataNodes[] = $v;
		else
			$this->__dataNodes[$k] = $v;
	}
	function offsetUnset($k){
		unset($this->__dataNodes[$k]);
	}
	function offsetGet($k){
		return isset($this->__dataNodes[$k])?$this->__dataNodes[$k]:null;
	}
	function __toString(){
		return implode('',$this->__dataNodes);
	}
	function apply($callback){
		foreach($this->__dataNodes as $node)
			$callback($node);
	}
	function __call($func, array $args=[]){
		$r = [];
		foreach($this->__dataNodes as $node)
			$r[] = call_user_func_array([$node,$func],$args);
		return $r;		
	}
	function count(){
		return count($this->__dataNodes);
	}
}
