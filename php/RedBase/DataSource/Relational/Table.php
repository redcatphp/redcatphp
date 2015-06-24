<?php
namespace RedBase\DataSource\Relational;
use RedBase\AbstractTable;
class Table extends AbstractTable{
	private $stmt;
	private $row = [];
	function rewind(){
		$this->stmt = $this->dataSource->getPDO()->fetch('SELECT '.$this->name.'.* FROM '.$this->name);
		$this->next();
	}
	function current(){
		return $this->row;
	}
	function key(){
		return $this->row->{$this->primaryKey};
	}
	function valid(){
		return false!==$this->row;
	}
	function next(){
		$this->row = $row = $this->stmt->fetch();
		if($row){
			$c = $this->dataSource->findEntityClass($this->name);
			$this->row = new $c();
			foreach($row as $k=>$v)
				$this->row->$k = $v;
			if($this->useCache)
				$this->data[$this->row->{$this->primaryKey}] = $this->row;
		}
	}
}