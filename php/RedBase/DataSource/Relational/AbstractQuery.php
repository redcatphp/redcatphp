<?php
namespace RedBase\DataSource\Relational;
use RedBase\DataSourceInterface;
abstract class AbstractQuery{
	protected $pdo;
	protected $primaryKey;
	protected $frozen;
	protected $dataSource;
	protected $typeno_sqltype = [];
	protected $sqltype_typeno = [];
	protected $quoteCharacter = '"';
	protected $tablePrefix;
	function __construct($pdo,$primaryKey='id',$frozen=null,DataSourceInterface $dataSource,$tablePrefix=''){
		$this->pdo = $pdo;
		$this->primaryKey = $primaryKey;
		$this->frozen = $frozen;
		$this->dataSource = $dataSource;
		$this->tablePrefix = $tablePrefix;
	}
	function createRow($type,$properties,$primaryKey='id'){
		if(!$this->frozen&&!$this->tableExists($type))
			$this->createTable($type);
		$columns = $this->getColumns($type);
		foreach($properties as $column=>$value){
			if(!isset($columns[$column])){
				$this->addColumn($type,$column,$this->scanType($value,true));
			}
		}
		return $this->create($type,$properties,$primaryKey);
	}
	function readRow($type,$id,$primaryKey='id'){
		if(!$this->tableExists($type))
			return false;
		return $this->read($type,$id,$primaryKey);
	}
	function updateRow($type,$properties,$id=null,$primaryKey='id'){
		if(!$this->tableExists($type))
			return false;
		return $this->update($type,$properties,$id,$primaryKey);
	}
	function deleteRow($type,$id,$primaryKey='id'){
		if(!$this->tableExists($type))
			return false;
		return $this->delete($type,$id,$primaryKey);
	}
	
	abstract function create($type,$properties,$primaryKey='id');
	abstract function read($type,$id,$primaryKey='id');
	abstract function update($type,$properties,$id=null,$primaryKey='id');
	abstract function delete($type,$id,$primaryKey='id');
	abstract function createTable($table);
	
	function esc($esc){
		return $this->quoteCharacter.$esc.$this->quoteCharacter;
	}
	function escTable($table){
		return $this->esc($this->tablePrefix.$table);
	}
	function tableExists($table){
		return in_array($table, $this->getTables());
	}
	function startsWithZeros($value){
		$value = strval($value);
		return strlen( $value ) > 1 && strpos( $value, '0' ) === 0 && strpos( $value, '0.' ) !== 0;
	}
	
}