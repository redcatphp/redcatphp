<?php namespace surikat\model\QueryWriter;
trait AQueryWriter{
	private static $_allTables = null;
	function __get($k){
		if(property_exists($this,$k))
			return $this->$k;
	}
	function addSqlColumnType($k,$v){
		$this->typeno_sqltype[$k] = $v;
		$this->sqltype_typeno[trim(strtolower($v))] = $k;
	}
	function createTable($table){
		if(isset(self::$_allTables)&&!in_array($table,self::$_allTables))
			self::$_allTables[] = $table;
		return parent::createTable($table);
	}
	function getTables(){
		if(!isset(self::$_allTables))
			self::$_allTables = parent::getTables();
		return self::$_allTables;
	}
}