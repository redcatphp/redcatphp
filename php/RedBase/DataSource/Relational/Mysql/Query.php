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
	const C_DATATYPE_SPECIFIED        = 99;
	
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
	
	function createRow($type,$properies,$primaryKey='id'){
		
	}
	function readRow($type,$id,$primaryKey='id'){
		
	}
	function updateRow($type,$obj,$id=null,$primaryKey='id'){
		
	}
	function deleteRow($type,$id,$primaryKey='id'){
		
	}
	
	function scanType( $value, $flagSpecial = FALSE ){

		if ( is_null( $value ) ) return self::C_DATATYPE_BOOL;
		if ( $value === INF ) return self::C_DATATYPE_TEXT7;

		if ( $flagSpecial ) {
			if ( preg_match( '/^\d{4}\-\d\d-\d\d$/', $value ) ) {
				return self::C_DATATYPE_SPECIAL_DATE;
			}
			if ( preg_match( '/^\d{4}\-\d\d-\d\d\s\d\d:\d\d:\d\d$/', $value ) ) {
				return self::C_DATATYPE_SPECIAL_DATETIME;
			}
			if ( preg_match( '/^POINT\(/', $value ) ) {
				return self::C_DATATYPE_SPECIAL_POINT;
			}
			if ( preg_match( '/^LINESTRING\(/', $value ) ) {
				return self::C_DATATYPE_SPECIAL_LINESTRING;
			}
			if ( preg_match( '/^POLYGON\(/', $value ) ) {
				return self::C_DATATYPE_SPECIAL_POLYGON;
			}
		}

		//setter turns TRUE FALSE into 0 and 1 because database has no real bools (TRUE and FALSE only for test?).
		if ( $value === FALSE || $value === TRUE || $value === '0' || $value === '1' ) {
			return self::C_DATATYPE_BOOL;
		}

		if ( is_float( $value ) ) return self::C_DATATYPE_DOUBLE;

		if ( !$this->startsWithZeros( $value ) ) {

			if ( is_numeric( $value ) && ( floor( $value ) == $value ) && $value >= 0 && $value <= 4294967295 ) {
				return self::C_DATATYPE_UINT32;
			}

			if ( is_numeric( $value ) ) {
				return self::C_DATATYPE_DOUBLE;
			}
		}

		if ( mb_strlen( $value, 'UTF-8' ) <= 191 ) {
			return self::C_DATATYPE_TEXT7;
		}

		if ( mb_strlen( $value, 'UTF-8' ) <= 255 ) {
			return self::C_DATATYPE_TEXT8;
		}

		if ( mb_strlen( $value, 'UTF-8' ) <= 65535 ) {
			return self::C_DATATYPE_TEXT16;
		}

		return self::C_DATATYPE_TEXT32;
	}
	function createTable($table){
		$table = $this->escTable($table);
		$pdo = $this->dataSource->getPDO();
		$encoding = $pdo->getEncoding();
		$pdo->execute("CREATE TABLE {$table} (id INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY ( id )) ENGINE = InnoDB DEFAULT CHARSET={$encoding} COLLATE={$encoding}_unicode_ci ");
	}
}