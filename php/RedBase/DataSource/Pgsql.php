<?php
namespace RedBase\DataSource;
class Pgsql extends SQL{
	const C_DATATYPE_INTEGER          = 0;
	const C_DATATYPE_DOUBLE           = 1;
	const C_DATATYPE_TEXT             = 3;
	const C_DATATYPE_SPECIAL_DATE     = 80;
	const C_DATATYPE_SPECIAL_DATETIME = 81;
	const C_DATATYPE_SPECIAL_POINT    = 90;
	const C_DATATYPE_SPECIAL_LSEG     = 91;
	const C_DATATYPE_SPECIAL_CIRCLE   = 92;
	const C_DATATYPE_SPECIAL_MONEY    = 93;
	const C_DATATYPE_SPECIAL_POLYGON  = 94;
	const C_DATATYPE_SPECIFIED        = 99;
	protected $defaultValue = 'DEFAULT';
	protected function getInsertSuffix( $primaryKey ){
		return 'RETURNING '.$primaryKey.' ';
	}
	function getTables(){
		return $this->getCol( 'SELECT table_name FROM information_schema.tables WHERE table_schema = ANY( current_schemas( FALSE ) )' );
	}
	function createTable( $table ){
		$table = $this->escTable($table);
		$this->pdo->exec(" CREATE TABLE $table (id SERIAL PRIMARY KEY); ");
	}
	function scanType( $value, $flagSpecial = FALSE ){

		if ( $value === INF ) return self::C_DATATYPE_TEXT;

		if ( $flagSpecial && $value ) {
			if ( preg_match( '/^\d{4}\-\d\d-\d\d$/', $value ) ) {
				return self::C_DATATYPE_SPECIAL_DATE;
			}

			if ( preg_match( '/^\d{4}\-\d\d-\d\d\s\d\d:\d\d:\d\d(\.\d{1,6})?$/', $value ) ) {
				return self::C_DATATYPE_SPECIAL_DATETIME;
			}

			if ( preg_match( '/^\([\d\.]+,[\d\.]+\)$/', $value ) ) {
				return self::C_DATATYPE_SPECIAL_POINT;
			}

			if ( preg_match( '/^\[\([\d\.]+,[\d\.]+\),\([\d\.]+,[\d\.]+\)\]$/', $value ) ) {
				return self::C_DATATYPE_SPECIAL_LSEG;
			}

			if ( preg_match( '/^\<\([\d\.]+,[\d\.]+\),[\d\.]+\>$/', $value ) ) {
				return self::C_DATATYPE_SPECIAL_CIRCLE;
			}

			if ( preg_match( '/^\((\([\d\.]+,[\d\.]+\),?)+\)$/', $value ) ) {
				return self::C_DATATYPE_SPECIAL_POLYGON;
			}

			if ( preg_match( '/^\-?(\$|€|¥|£)[\d,\.]+$/', $value ) ) {
				return self::C_DATATYPE_SPECIAL_MONEY;
			}
		}

		if ( is_float( $value ) ) return self::C_DATATYPE_DOUBLE;

		if ( self::startsWithZeros( $value ) ) return self::C_DATATYPE_TEXT;
		
		if ( $value === FALSE || $value === TRUE || $value === NULL || ( is_numeric( $value )
				&& self::canBeTreatedAsInt( $value )
				&& $value < 2147483648
				&& $value > -2147483648 )
		) {
			return self::C_DATATYPE_INTEGER;
		} elseif ( is_numeric( $value ) ) {
			return self::C_DATATYPE_DOUBLE;
		} else {
			return self::C_DATATYPE_TEXT;
		}
	}
}