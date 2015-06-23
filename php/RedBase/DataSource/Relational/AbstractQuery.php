<?php
namespace RedBase\DataSource\Relational;
use RedBase\DataSourceInterface;
abstract class AbstractQuery{
	const C_DATATYPE_RANGE_SPECIAL   = 80;
	protected $pdo;
	protected $primaryKey;
	protected $frozen;
	protected $dataSource;
	protected $typeno_sqltype = [];
	protected $sqltype_typeno = [];
	protected $quoteCharacter = '"';
	protected $defaultValue = 'NULL';
	protected $tablePrefix;
	protected $sqlFilters = [];
	protected $flagSQLFilterSafeMode = false;
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
			else{
				$typeno = $this->scanType($value,false);
				$typedesc = $columns[$column];
				if(isset($this->sqltype_typeno[$typedesc])
					&&$this->sqltype_typeno[$typedesc]<self::C_DATATYPE_RANGE_SPECIAL
					&&$this->sqltype_typeno[$typedesc]<$typeno
				)
					$this->changeColumn($type,$column,$typeno);
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
	
	protected function getInsertSuffix($primaryKey){
		return '';
	}
	function setSQLFilters( $sqlFilters, $safeMode = false ){
		$this->flagSQLFilterSafeMode = (boolean) $safeMode;
		$this->sqlFilters = $sqlFilters;
	}
	function getSQLFilters(){
		return $this->sqlFilters;
	}
	function create($type,$properties,$primaryKey='id'){
		$insertcolumns = array_keys($properties);
		$insertvalues = array_values($properties);
		$default = $this->defaultValue;
		$suffix  = $this->getInsertSuffix( $type );
		$table   = $this->escTable( $type );
		if(!empty($insertvalues)){
			$insertSlots = [];
			foreach($insertcolumns as $k=>$v){
				$insertcolumns[$k] = $this->esc($v);
				if (isset($this->sqlFilters['w'][$type][$v]))
					$insertSlots[] = $this->sqlFilters['w'][$type][$v];
				else
					$insertSlots[] = '?';
			}
			$insertSQL = "INSERT INTO $table ( id, " . implode( ',', $insertcolumns ) . " ) VALUES ( $default, " . implode( ',', $insertSlots ) . " ) $suffix";
			$result = $this->pdo->getCell($insertSQL,$insertvalues);
		}
		else{
			$result = $this->pdo->getCell("INSERT INTO $table ($primaryKey) VALUES($default) $suffix");
		}
		if($suffix)
			return $result;
		return $this->pdo->getInsertID();
	}
	
	function check($struct){
		if(!preg_match('/^[a-zA-Z0-9_-]+$/',$struct))
			throw new \InvalidArgumentException('Table or Column name does not conform to RedBase security policies' );
		return $struct;
	}
	function esc($esc){
		$this->check($esc);
		return $this->quoteCharacter.$esc.$this->quoteCharacter;
	}
	function escTable($table){
		$this->check($table);
		return $this->esc($this->tablePrefix.$table);
	}
	function tableExists($table){
		return in_array($table, $this->getTables());
	}
	function startsWithZeros($value){
		$value = strval($value);
		return strlen( $value ) > 1 && strpos( $value, '0' ) === 0 && strpos( $value, '0.' ) !== 0;
	}
	
	static function canBeTreatedAsInt( $value ){
		return (bool) ( strval( $value ) === strval( intval( $value ) ) );
	}
	
	abstract function read($type,$id,$primaryKey='id');
	abstract function update($type,$properties,$id=null,$primaryKey='id');
	abstract function delete($type,$id,$primaryKey='id');
	abstract function scanType($value,$flagSpecial=false);
	abstract function getTables();
	abstract function getColumns($table);
	abstract function createTable($table);
	abstract function addColumn($type,$column,$field);
	abstract function changeColumn($type,$property,$dataType);
}