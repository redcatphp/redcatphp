<?php
namespace RedBase;
abstract class AbstractTable implements \ArrayAccess,\Iterator{
	protected $name;
	protected $primaryKey;
	protected $dataSource;
	protected $data = [];
	protected $useCache = true;
	function __construct($name,$primaryKey='id',DataSourceInterface $dataSource){
		$this->name = $name;
		$this->primaryKey = $primaryKey;
		$this->dataSource = $dataSource;
	}
	function getPrimaryKey(){
		return $this->primaryKey;
	}
	function offsetExists($id){
		return (bool)$this->offsetGet($id);
	}
	function offsetGet($id){
		if(!$this->useCache||!array_key_exists($id,$this->data))
			$row = $this->readRow($id);
		if($this->useCache)
			$this->data[$id] = $row;
		return $row;
	}
	function offsetSet($id,$obj){
		if(is_array($obj))
			$obj = (object)$obj;
		if(!$id){
			$id = $this->createRow($obj);
			$obj->{$this->primaryKey} = $id;
		}
		else{
			$this->updateRow($obj,$id);
		}
		if($this->useCache)
			$this->data[$id] = $obj;
	}
	function offsetUnset($id){
		$this->deleteRow($id);
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
	function setCache($enable){
		$this->useCache = (bool)$enable;
	}
	function resetCache(){
		$this->data = [];
	}
	function createRow($obj){
		return $this->dataSource->createRow($this->name,$obj,$this->primaryKey);
	}
	function readRow($id){
		return $this->dataSource->readRow($this->name,$id,$this->primaryKey);
	}
	function updateRow($obj,$id=null){
		return $this->dataSource->updateRow($this->name,$obj,$id,$this->primaryKey);
	}
	function deleteRow($id){
		return $this->dataSource->deleteRow($this->name,$id,$this->primaryKey);
	}
	function update($id){
		$this[$id] = $this[$id];
	}
}