<?php
namespace RedBase;
abstract class AbstractTable implements \ArrayAccess,\Iterator{
	protected $name;
	protected $primaryKey;
	protected $uniqTextKey;
	protected $dataSource;
	protected $data = [];
	protected $useCache = true;
	function __construct($name,$primaryKey='id',$uniqTextKey='uniq',DataSourceInterface $dataSource){
		$this->name = $name;
		$this->primaryKey = $primaryKey;
		$this->uniqTextKey = $uniqTextKey;
		$this->dataSource = $dataSource;
	}
	function getPrimaryKey(){
		return $this->primaryKey;
	}
	function getUniqTextKey(){
		return $this->uniqTextKey;
	}
	function getDataSource(){
		return $this->dataSource;
	}
	function setUniqTextKey($uniqTextKey='uniq'){
		$this->uniqTextKey = $uniqTextKey;
	}
	function offsetExists($id){
		return (bool)$this->readId($id);
	}
	function offsetGet($id){
		if(!$this->useCache||!array_key_exists($id,$this->data))
			$row = $this->readRow($id);
		else
			$row = $this->data[$id];
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
	function readId($id){
		return $this->dataSource->readId($this->name,$id,$this->primaryKey,$this->uniqTextKey);
	}
	function createRow($obj){
		return $this->dataSource->createRow($this->name,$obj,$this->primaryKey,$this->uniqTextKey);
	}
	function readRow($id){
		return $this->dataSource->readRow($this->name,$id,$this->primaryKey,$this->uniqTextKey);
	}
	function updateRow($obj,$id=null){
		return $this->dataSource->updateRow($this->name,$obj,$id,$this->primaryKey,$this->uniqTextKey);
	}
	function deleteRow($id){
		return $this->dataSource->deleteRow($this->name,$id,$this->primaryKey,$this->uniqTextKey);
	}
	function update($id){
		$this[$id] = $this[$id];
	}
	function getClone(){
		return clone $this;
	}
}