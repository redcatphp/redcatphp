<?php namespace SimplePO;
use Core\FS;
use Model\R;
class Query{
	private $sql = "";
	private $sql_args = array();
	private $table_prefix = '';
	private $last_inserted_id = null;
	function reset() {
		$this->sql = "";
		return $this;
	}
	function sql(){
		R::selectDatabase('langs');
		$args = func_get_args();
		$sql = array_shift($args);
		// replace {} with table prefix
		$sql = preg_replace('/{([^}]*)}/',$this->table_prefix . '${1}',$sql);
		$this->sql = $sql;
		$this->sql_args = $args;
		return $this;
	}
	function appendSql() {
		$args = func_get_args();
		$sql = array_shift($args);
		foreach($args as $arg){
			$this->sql_args[] = $arg;
		}
		$this->sql .= $sql;
		return $this;
	
	}
	function fetchAll() {
		return (array)R::getAll($this->sql,$this->sql_args);
	}
	function fetch() {
		return R::getRow($this->sql,$this->sql_args);
	}
	function fetchOne() {
		return R::getAssoc($this->sql,$this->sql_args);
	}
	function count(){
		return count(R::getAll($this->sql,$this->sql_args));
	}
	function insertId(){
		return $this->last_inserted_id;
	}
	function execute() {
		$r = R::exec($this->sql,$this->sql_args);
		if(is_int($r))
			$this->last_inserted_id = $r;
	}
	function fetchRow() {
		return R::getRow($this->sql,$this->sql_args);
	}
	function fetchCol($index=0){
		$result = array();
		if(is_int($index)) {
			while($a = R::getRow($this->sql,$this->sql_args)) $result[] = $a[$index];
		} else {
			while($a = R::getRow($this->sql,$this->sql_args)) $result[] = $a[$index];
		}
		return $result;
	}
	function fetchAllKV(){
		$res = array();
		while(list($a,$b) = R::getRow($this->sql,$this->sql_args)) $r[$a] = $b;
		return $r;
	}
	function getError() {
		return "<pre>QUERY:".$this->sql."</pre>";
	}
	function getSQL() {
		return $this->sql;
	}
	public function __toString() {
		return $this->sql;
	}
}