<?php namespace surikat\model\QueryWriter;
trait AQueryWriter{
	private static $_allTables = null;
	private static $_allColumns = array();
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

	function addColumn( $table, $column, $datatype ){
		if(isset(self::$_allColumns[$table])){
			$newtype = isset($this->typeno_sqltype[$datatype])?$this->typeno_sqltype[$datatype]:'';
			self::$_allColumns[$table][$column] = $newtype;
		}
		return parent::addColumn($table, $column, $datatype);
	}
	function widenColumn( $table, $column, $datatype ){
		if(!isset($this->typeno_sqltype[$datatype]))
			return;
		if(isset(self::$_allColumns[$table])){
			$newtype = isset($this->typeno_sqltype[$datatype])?$this->typeno_sqltype[$datatype]:'';
			self::$_allColumns[$table][$column] = $newtype;
		}
		return parent::widenColumn($table, $column, $datatype);
	}
	function getColumns($table){
		if(!isset(self::$_allColumns[$table]))
			self::$_allColumns[$table] = parent::getColumns($table);
		return self::$_allColumns[$table];
	}
}