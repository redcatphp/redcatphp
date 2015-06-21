<?php
namespace RedBase\DataSource\Relational\Mysql;
class Query extends \RedBase\DataSource\Relational\AbstractQuery{
	
	const C_DATATYPE_BOOL             = 0;
	const C_DATATYPE_UINT32           = 2;
	const C_DATATYPE_DOUBLE           = 3;
	const C_DATATYPE_TEXT7            = 4; //InnoDB cant index varchar(255) utf8mb4 - so keep 191 as long as possible
	const C_DATATYPE_TEXT8            = 5;
	const C_DATATYPE_TEXT16           = 6;
	const C_DATATYPE_TEXT32           = 7;
	const C_DATATYPE_SPECIAL_DATE     = 80;
	const C_DATATYPE_SPECIAL_DATETIME = 81;
	const C_DATATYPE_SPECIAL_POINT    = 90;
	const C_DATATYPE_SPECIAL_LINESTRING = 91;
	const C_DATATYPE_SPECIAL_POLYGON    = 92;
	const C_DATATYPE_SPECIFIED          = 99;
	
	protected $quoteCharacter = '`';
	
	function __construct($pdo,$primaryKey='id',$frozen=null,DataSourceInterface $dataSource,$tablePrefix){
		parent::__construct($pdo,$primaryKey,$frozen,$dataSource,$tablePrefix);
		$this->typeno_sqltype = [
			self::C_DATATYPE_BOOL             => ' TINYINT(1) UNSIGNED ',
			self::C_DATATYPE_UINT32           => ' INT(11) UNSIGNED ',
			self::C_DATATYPE_DOUBLE           => ' DOUBLE ',
			self::C_DATATYPE_TEXT7            => ' VARCHAR(191) ',
			self::C_DATATYPE_TEXT8	           => ' VARCHAR(255) ',
			self::C_DATATYPE_TEXT16           => ' TEXT ',
			self::C_DATATYPE_TEXT32           => ' LONGTEXT ',
			self::C_DATATYPE_SPECIAL_DATE     => ' DATE ',
			self::C_DATATYPE_SPECIAL_DATETIME => ' DATETIME ',
			self::C_DATATYPE_SPECIAL_POINT    => ' POINT ',
			self::C_DATATYPE_SPECIAL_LINESTRING => ' LINESTRING ',
			self::C_DATATYPE_SPECIAL_POLYGON => ' POLYGON ',
		];
		foreach($this->typeno_sqltype as $k=>$v){
			$this->sqltype_typeno[trim(strtolower($v))] = $k;
		}
	}
	
	function create($type,$properties,$primaryKey='id'){
		
	}
	function read($type,$id,$primaryKey='id'){
		
	}
	function update($type,$properties,$id=null,$primaryKey='id'){
		
	}
	function delete($type,$id,$primaryKey='id'){
		
	}
	
	function scanType($value,$flagSpecial=false){
		if(is_null( $value ))
			return self::C_DATATYPE_BOOL;
		if($value === INF)
			return self::C_DATATYPE_TEXT7;
		if($flagSpecial){
			if(preg_match( '/^\d{4}\-\d\d-\d\d$/', $value ) )
				return self::C_DATATYPE_SPECIAL_DATE;
			if(preg_match( '/^\d{4}\-\d\d-\d\d\s\d\d:\d\d:\d\d$/', $value ) )
				return self::C_DATATYPE_SPECIAL_DATETIME;
			if(preg_match( '/^POINT\(/', $value ) )
				return self::C_DATATYPE_SPECIAL_POINT;
			if(preg_match( '/^LINESTRING\(/', $value ) )
				return self::C_DATATYPE_SPECIAL_LINESTRING;
			if(preg_match( '/^POLYGON\(/', $value ) )
				return self::C_DATATYPE_SPECIAL_POLYGON;
		}
		//setter turns TRUE FALSE into 0 and 1 because database has no real bools (TRUE and FALSE only for test?).
		if( $value === FALSE || $value === TRUE || $value === '0' || $value === '1' )
			return self::C_DATATYPE_BOOL;
		if( is_float( $value ) )
			return self::C_DATATYPE_DOUBLE;
		if( !$this->startsWithZeros( $value ) ) {
			if( is_numeric( $value ) && ( floor( $value ) == $value ) && $value >= 0 && $value <= 4294967295 )
				return self::C_DATATYPE_UINT32;
			if( is_numeric( $value ) )
				return self::C_DATATYPE_DOUBLE;
		}
		if( mb_strlen( $value, 'UTF-8' ) <= 191 )
			return self::C_DATATYPE_TEXT7;
		if( mb_strlen( $value, 'UTF-8' ) <= 255 )
			return self::C_DATATYPE_TEXT8;
		if( mb_strlen( $value, 'UTF-8' ) <= 65535 )
			return self::C_DATATYPE_TEXT16;
		return self::C_DATATYPE_TEXT32;
	}
	function getTables(){
		return $this->pdo->getCol('show tables');
	}
	function getColumns($table){
		$columns = [];
		foreach($this->pdo->getAll('DESCRIBE '.$this->escTable($table)) as $r)
			$columns[$r['Field']] = $r['Type'];
		return $columns;
	}
	function createTable($table){
		$table = $this->escTable($table);
		$encoding = $this->pdo->getEncoding();
		$this->pdo->execute('CREATE TABLE '.$table.' (id INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY ( id )) ENGINE = InnoDB DEFAULT CHARSET='.$encoding.' COLLATE='.$encoding.'_unicode_ci ');
	}
	function addColumn($type,$column,$field){
		$table  = $type;
		$type   = $field;
		$table  = $this->escTable($table);
		$column = $this->esc($column);
		$type = ( isset( $this->typeno_sqltype[$type] ) ) ? $this->typeno_sqltype[$type] : '';
		$this->pdo->execute('ALTER TABLE '.$table.' ADD '.$column.' '.$type);
	}
	function changeColumn($type,$property,$dataType ){
		if(!isset($this->typeno_sqltype[$dataType]))
			return false;
		$table   = $this->escTable( $type );
		$column  = $this->esc( $property );
		$newType = $this->typeno_sqltype[$dataType];
		$this->pdo->execute('ALTER TABLE '.$table.' CHANGE '.$column.' '.$column.' '.$newType);
		return true;
	}
	protected function getKeyMapForType($table){
		$this->check($table);
		$keys = $this->pdo->getAll('
			SELECT
				information_schema.key_column_usage.constraint_name AS `name`,
				information_schema.key_column_usage.referenced_table_name AS `table`,
				information_schema.key_column_usage.column_name AS `from`,
				information_schema.key_column_usage.referenced_column_name AS `to`,
				information_schema.referential_constraints.update_rule AS `on_update`,
				information_schema.referential_constraints.delete_rule AS `on_delete`
				FROM information_schema.key_column_usage
				INNER JOIN information_schema.referential_constraints
					ON (
						information_schema.referential_constraints.constraint_name = information_schema.key_column_usage.constraint_name
						AND information_schema.referential_constraints.constraint_schema = information_schema.key_column_usage.constraint_schema
						AND information_schema.referential_constraints.constraint_catalog = information_schema.key_column_usage.constraint_catalog
					)
			WHERE
				information_schema.key_column_usage.table_schema IN ( SELECT DATABASE() )
				AND information_schema.key_column_usage.table_name = ?
				AND information_schema.key_column_usage.constraint_name != \'PRIMARY\'
				AND information_schema.key_column_usage.referenced_table_name IS NOT NULL
		', [$table]);
		$keyInfoList = [];
		foreach ( $keys as $k ) {
			$label = $this->makeFKLabel( $k['from'], $k['table'], $k['to'] );
			$keyInfoList[$label] = [
				'name'          => $k['name'],
				'from'          => $k['from'],
				'table'         => $k['table'],
				'to'            => $k['to'],
				'on_update'     => $k['on_update'],
				'on_delete'     => $k['on_delete']
			];
		}
		return $keyInfoList;
	}
	protected function makeFKLabel($from, $type, $to){
		return "from_{$from}_to_table_{$type}_col_{$to}";
	}
}