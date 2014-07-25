<?php namespace surikat\model\QueryWriter;
trait AQueryWriter{
	function __get($k){
		if(property_exists($this,$k))
			return $this->$k;
	}
	function addSqlColumnType($k,$v){
		$this->typeno_sqltype[$k] = $v;
		$this->sqltype_typeno[trim(strtolower($v))] = $k;
	}
}