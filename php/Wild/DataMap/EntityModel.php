<?php
namespace Wild\DataMap;
class EntityModel implements Observer{
	protected $__data = [];
	public $_modified;
	function beforeRecursive(){}
	function beforePut(){}
	function beforeCreate(){}
	function beforeRead(){}
	function beforeUpdate(){}
	function beforeDelete(){}
	function afterPut(){}
	function afterCreate(){}
	function afterRead(){}
	function afterUpdate(){}
	function afterDelete(){}
	function afterRecursive(){}
	function __set($k,$v){
		if(!isset($this->__data[$k])||$this->__data[$k]!==$v)
			$this->_modified = true;
		$this->__data[$k] = $v;
	}
	function __get($k){
		return isset($this->__data[$k])?$this->__data[$k]:null;
	}
	function __isset($k){
		return isset($this->__data[$k]);
	}
	function __unset($k){
		unset($this->__data[$k]);
	}
}