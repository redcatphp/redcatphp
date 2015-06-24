<?php
namespace RedBase\DataSource\Relational;
use RedBase\AbstractTable;
class Table extends AbstractTable{
	private $stmt;
	private $next = [];
	function rewind(){
		$this->stmt = $this->dataSource->getPDO()->fetch('SELECT '.$this->name.'.* FROM '.$this->name);
		reset($this->data);
		$this->next();
	}
	function current(){
		return $this->next[1];
	}
	function key(){
		return $this->next[0];
	}
	function valid(){
		return false!==$this->next;
	}
	function next(){
        $this->next = each($this->data);
        if (false===$this->next){
            $row = $this->stmt->fetch();
            if($row&&$this->useCache)
                $this->data[$row[$this->primaryKey]] = $row;
            $this->next = each($this->data);
        }
	}
}