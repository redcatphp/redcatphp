<?php
namespace RedBase\DataSource\Relational;
use RedBase\AbstractTable;
class Table extends AbstractTable{
	private $stmt;
	private $row = [];
	function rewind(){
		$this->stmt = $this->dataSource->getPDO()->fetch('SELECT '.$this->name.'.* FROM '.$this->name);
		reset($this->data);
		$this->next();
	}
	function current(){
		return $this->row;
	}
	function key(){
		return $this->row[$this->primaryKey];
	}
	function valid(){
		return false!==$this->row;
	}
	function next(){
		$row = $this->stmt->fetch();
		if($this->useCache&&$row)
			$this->data[$row[$this->primaryKey]] = $row;
		$this->row = $row;
	}
}