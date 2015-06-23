<?php
namespace RedBase;
class Table implements \ArrayAccess{
	private $name;
	private $primaryKey;
	private $dataSource;
	private $data = [];
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
		if(!array_key_exists($id,$this->data))
			$this->data[$id] = $this->readRow($id);
		return $this->data[$id];
	}
	function offsetSet($id,$obj){
		if(is_array($obj))
			$obj = (object)$obj;
		if(!$id)
			$id = $this->createRow($obj);
		else
			$this->updateRow($obj,$id);
		$this->data[$id] = $obj;
	}
	function offsetUnset($id){
		$this->deleteRow($id);
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
}