<?php
namespace RedBase;
use RedBase\Helper\Pagination;
abstract class DataTable implements \ArrayAccess,\Iterator,\Countable{
	protected $name;
	protected $primaryKey;
	protected $uniqTextKey;
	protected $dataSource;
	protected $data = [];
	protected $useCache = true;
	function __construct($name,$primaryKey='id',$uniqTextKey='uniq',$dataSource){
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
		if(is_array($obj)){
			$c = $this->dataSource->findEntityClass($this->name);
			$tmp = $obj;
			$obj = new $c();
			foreach($tmp as $k=>$v)
				$obj->$k = $v;
			unset($tmp);
		}
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
	function count(){
		return count($this->data);
	}
	function paginate($page,$limit=2,$href='',$prefix='?page=',$maxCols=6){
		$pagination = new Pagination();
		$pagination->setLimit($limit);
		$pagination->setMaxCols($maxCols);
		$pagination->setHref($href);
		$pagination->setPrefix($prefix);
		$pagination->setCount($this->count());
		$pagination->setPage($page);
		if($pagination->resolve($page)){
			$this->limit($pagination->limit);
			$this->offset($pagination->offset);
			return $pagination;
		}
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
	
	function loadOne($obj){
		return $obj->{'_one_'.$this->name} = $this->one($obj)->getRow();
	}
	function loadMany($obj){
		return $obj->{'_many_'.$this->name} = $this->many($obj)->getAll();
	}
	function loadMany2many($obj){
		return $obj->{'_many2many_'.$this->name} = $this->many2many($obj)->getAll();
	}
	function one($obj){
		return $this->dataSource->many2one($obj,$this->name);
	}
	function many($obj){
		return $this->dataSource->one2many($obj,$this->name);
	}
	function many2many($obj){
		return $this->dataSource->many2many($obj,$this->name);
	}
	
	//abstract function getAll();
	//abstract function getRow();
}