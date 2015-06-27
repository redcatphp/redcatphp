<?php
namespace RedBase\DataSource\Relational;
use RedBase\DataSourceInterface;
abstract class AbstractQuery{
	const C_DATATYPE_RANGE_SPECIAL   = 80;
	protected $pdo;
	protected $primaryKey;
	protected $uniqTextKey;
	protected $frozen;
	protected $dataSource;
	protected $typeno_sqltype = [];
	protected $sqltype_typeno = [];
	protected $quoteCharacter = '"';
	protected $defaultValue = 'NULL';
	protected $tablePrefix;
	protected $sqlFiltersWrite = [];
	protected $sqlFiltersRead = [];
	function __construct($pdo,$primaryKey='id',$uniqTextKey='uniq',$frozen=null,DataSourceInterface $dataSource,$tablePrefix=''){
		$this->pdo = $pdo;
		$this->primaryKey = $primaryKey;
		$this->uniqTextKey = $uniqTextKey;
		$this->frozen = $frozen;
		$this->dataSource = $dataSource;
		$this->tablePrefix = $tablePrefix;
	}
	function adaptStructure($type,$properties){
		if($this->frozen)
			return;
		if(!$this->tableExists($type))
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
	}
	function createRow($type,$properties,$primaryKey='id',$uniqTextKey='uniq'){
		return $this->create($type,$properties,$primaryKey,$uniqTextKey);
	}
	function readRow($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		if(!$this->tableExists($type))
			return false;
		return $this->read($type,$id,$primaryKey,$uniqTextKey);
	}
	function updateRow($type,$properties,$id=null,$primaryKey='id',$uniqTextKey='uniq'){
		return $this->update($type,$properties,$id,$primaryKey,$uniqTextKey);
	}
	function deleteRow($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		if(!$this->tableExists($type))
			return false;
		return $this->delete($type,$id,$primaryKey,$uniqTextKey);
	}
	
	protected function getInsertSuffix($primaryKey){
		return '';
	}
	function setSQLFiltersRead($sqlFilters){
		$this->sqlFiltersRead = $sqlFilters;
	}
	function getSQLFiltersRead(){
		return $this->sqlFiltersRead;
	}
	function setSQLFiltersWrite($sqlFilters){
		$this->sqlFiltersWrite = $sqlFilters;
	}
	function getSQLFiltersWrite(){
		return $this->sqlFiltersWrite;
	}
	protected function getSQLFilterSnippet($type){
		$sqlFilters = [];
		if(isset($this->sqlFiltersRead[$type])){
			foreach($this->sqlFiltersRead[$type] as $property=>$sqlFilter)
				$sqlFilters[] = $sqlFilter.' AS '.$property.' ';
		}
		return !empty($sqlFilters)?','.implode(',',$sqlFilters):'';
	}
	function readId($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		if(!$this->tableExists($type)||!in_array($uniqTextKey,array_keys($this->getColumns($type))))
			return false;
		$table = $this->escTable($type);
		return $this->pdo->getCell("SELECT {$primaryKey} FROM {$table} WHERE {$uniqTextKey}=?",[$id]);
	}
	function create($type,$properties,$primaryKey='id',$uniqTextKey='uniq'){
		if($uniqTextKey&&isset($properties[$uniqTextKey]))
			return $this->update($type,$properties,$properties[$uniqTextKey],$primaryKey,$uniqTextKey);
		$insertcolumns = array_keys($properties);
		$insertvalues = array_values($properties);
		$default = $this->defaultValue;
		$suffix  = $this->getInsertSuffix($type);
		$table   = $this->escTable($type);
		$this->adaptStructure($type,$properties);
		if(!empty($insertvalues)){
			$insertSlots = [];
			foreach($insertcolumns as $k=>$v){
				$insertcolumns[$k] = $this->esc($v);
				if (isset($this->sqlFiltersWrite[$type][$v]))
					$insertSlots[] = $this->sqlFiltersWrite[$type][$v];
				else
					$insertSlots[] = '?';
			}
			$result = $this->pdo->getCell('INSERT INTO '.$table.' ( '.$primaryKey.', '.implode(',',$insertcolumns).' ) VALUES ( '.$default.', '. implode(',',$insertSlots).' ) '.$suffix,$insertvalues);
		}
		else{
			$result = $this->pdo->getCell('INSERT INTO '.$table.' ('.$primaryKey.') VALUES('.$default.') '.$suffix);
		}
		if($suffix)
			return $result;
		return $this->pdo->getInsertID();
	}
	function read($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		if($uniqTextKey&&!self::canBeTreatedAsInt($id))
			$primaryKey = $uniqTextKey;
		$table = $this->escTable($type);
		$sqlFilterStr = $this->getSQLFilterSnippet($type);
		$sql = "SELECT {$table}.* {$sqlFilterStr} FROM {$table} WHERE {$primaryKey}=? LIMIT 1";
		$row = $this->pdo->getRow($sql,[$id]);
		if($row){
			$c = $this->dataSource->findEntityClass($type);
			$obj = new $c();
			foreach($row as $k=>$v)
				$obj->$k = $v;
			return $obj;
		}
	}
	function update($type,$properties,$id=null,$primaryKey='id',$uniqTextKey='uniq'){
		$uniqTexting = false;
		if($uniqTextKey&&!self::canBeTreatedAsInt($id)){
			$uniqTexting = true;
			$properties[$uniqTextKey] = $id;
			$id = $this->readId($type,$id,$primaryKey,$uniqTextKey);
		}
		if(!$id)
			return $this->create($type,$properties,$primaryKey);
		if(!$this->tableExists($type))
			return false;
		$this->adaptStructure($type,$properties);
		$fields = [];
		$binds = [];
		foreach($properties as $k=>$v){
			if($k==$primaryKey||($uniqTexting&&$k==$uniqTextKey))
				continue;
			if(isset($this->sqlFiltersWrite[$type][$k]))
				$fields[] = ' '.$this->esc($k).' = '.$this->sqlFiltersWrite[$type][$k];
			else
				$fields[] = ' '.$this->esc($k).' = ? ';
			$binds[] = $v;
		}
		if(empty($fields))
			return $id;
		$binds[] = $id;
		$table = $this->escTable($type);
		$this->pdo->execute('UPDATE '.$table.' SET '.implode(',',$fields).' WHERE '.$primaryKey.' = ? ', $binds);
		return $id;
	}
	function delete($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		if($uniqTextKey&&!self::canBeTreatedAsInt($id))
			$primaryKey = $uniqTextKey;
		$this->pdo->execute('DELETE FROM '.$this->escTable($type).' WHERE '.$primaryKey.' = ?', [$id]);
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
		return $this->quoteCharacter.$this->tablePrefix.$table.$this->quoteCharacter;
	}
	function prefixTable($table){
		$this->check($table);
		return $this->tablePrefix.$table;
	}
	function tableExists($table,$prefix=false){
		if($prefix)
			$table = $this->prefixTable($table);
		return in_array($table, $this->getTables());
	}
	static function startsWithZeros($value){
		$value = strval($value);
		return strlen( $value ) > 1 && strpos( $value, '0' ) === 0 && strpos( $value, '0.' ) !== 0;
	}
	
	static function canBeTreatedAsInt( $value ){
		return (bool) ( strval( $value ) === strval( intval( $value ) ) );
	}
	
	protected static function makeFKLabel($from, $type, $to){
		return 'from_'.$from.'_to_table_'.$type.'_col_'.$to;
	}
	
	abstract function scanType($value,$flagSpecial=false);
	abstract function getTables();
	abstract function getColumns($table);
	abstract function createTable($table);
	abstract function addColumn($type,$column,$field);
	abstract function changeColumn($type,$property,$dataType);
}