<?php

namespace Surikat\Model\RedBeanPHP\QueryWriter;

use Surikat\Model\RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;
use Surikat\Model\RedBeanPHP\QueryWriter as QueryWriter;
use Surikat\Model\RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use Surikat\Model\RedBeanPHP\Adapter as Adapter;
use Surikat\Model\RedBeanPHP\Database;
use Surikat\Model\RedBeanPHP\RedException\SQL as SQLException;

/**
 * RedBean MySQLWriter
 *
 * @file    RedBeanPHP/QueryWriter/MySQL.php
 * @desc    Represents a MySQL Database to RedBean
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class MySQL extends AQueryWriter implements QueryWriter
{
	protected $separator = 'SEPARATOR';
	protected $agg = 'GROUP_CONCAT';
	protected $aggCaster = '';
	protected $sumCaster = '';
	protected $concatenator = '0x1D';
	
	/**
	 * Data types
	 */
	const C_DATATYPE_BOOL             = 0;
	const C_DATATYPE_UINT32           = 2;
	const C_DATATYPE_DOUBLE           = 3;
	const C_DATATYPE_TEXT7            = 4;
	const C_DATATYPE_TEXT8            = 5;
	const C_DATATYPE_TEXT16           = 6;
	const C_DATATYPE_TEXT32           = 7;
	const C_DATATYPE_SPECIAL_DATE     = 80;
	const C_DATATYPE_SPECIAL_DATETIME = 81;
	const C_DATATYPE_SPECIAL_POINT    = 90;
	const C_DATATYPE_SPECIAL_LINESTRING = 91;
	const C_DATATYPE_SPECIAL_POLYGON    = 92;

	const C_DATATYPE_SPECIFIED        = 99;
	
	/**
	 * @var string
	 */
	protected $quoteCharacter = '`';

	/**
	 * Constructor
	 *
	 * @param Adapter $adapter Database Adapter
	 */
	public function __construct( Adapter $a, Database $db, $prefix='', $case=true )
	{
		parent::__construct($a,$db,$prefix,$case);
		$this->typeno_sqltype = [
			self::C_DATATYPE_BOOL             => 'TINYINT(1) UNSIGNED',
			self::C_DATATYPE_UINT32           => 'INT(11) UNSIGNED',
			self::C_DATATYPE_DOUBLE           => 'DOUBLE',
			self::C_DATATYPE_TEXT7            => 'VARCHAR(191)',
			self::C_DATATYPE_TEXT8            => 'VARCHAR(255)',
			self::C_DATATYPE_TEXT16           => 'TEXT',
			self::C_DATATYPE_TEXT32           => 'LONGTEXT',
			self::C_DATATYPE_SPECIAL_DATE     => 'DATE',
			self::C_DATATYPE_SPECIAL_DATETIME => 'DATETIME',
			self::C_DATATYPE_SPECIAL_POINT    => 'POINT',
			self::C_DATATYPE_SPECIAL_LINESTRING => 'LINESTRING',
			self::C_DATATYPE_SPECIAL_POLYGON => 'POLYGON',
		];
		foreach ( $this->typeno_sqltype as $k => $v ) {
			$this->sqltype_typeno[trim( strtolower( $v ) )] = $k;
		}
		$this->encoding = $this->adapter->getDatabase()->getMysqlEncoding();
	}

	/**
	 * This method returns the datatype to be used for primary key IDS and
	 * foreign keys. Returns one if the data type constants.
	 *
	 * @return integer $const data type to be used for IDS.
	 */
	public function getTypeForID()
	{
		return self::C_DATATYPE_UINT32;
	}

	/**
	 * @see QueryWriter::getTables
	 */
	public function _getTables()
	{
		return $this->adapter->getCol( 'show tables' );
	}

	/**
	 * @see QueryWriter::createTable
	 */
	public function _createTable( $table )
	{
		$table = $this->safeTable( $table );

		$encoding = $this->adapter->getDatabase()->getMysqlEncoding();
		$sql   = "CREATE TABLE $table (id INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY ( id )) ENGINE = InnoDB DEFAULT CHARSET={$encoding} COLLATE={$encoding}_unicode_ci ";

		$this->adapter->exec( $sql );
	}

	/**
	 * @see QueryWriter::getColumns
	 */
	public function _getColumns( $table )
	{
		$columnsRaw = $this->adapter->get( "DESCRIBE " . $this->safeTable( $table ) );

		$columns = [];
		foreach ( $columnsRaw as $r ) {
			$columns[$r['Field']] = trim($r['Type']);
		}

		return $columns;
	}

	/**
	 * @see QueryWriter::scanType
	 */
	public function scanType( $value, $flagSpecial = FALSE )
	{
		$this->svalue = $value;

		if ( is_null( $value ) ) return self::C_DATATYPE_BOOL;
		if ( $value === INF ) return self::C_DATATYPE_TEXT8;

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

	/**
	 * @see QueryWriter::code
	 */
	public function code( $typedescription, $includeSpecials = FALSE )
	{
		if ( isset( $this->sqltype_typeno[$typedescription] ) ) {
			$r = $this->sqltype_typeno[$typedescription];
		} else {
			$r = self::C_DATATYPE_SPECIFIED;
		}

		if ( $includeSpecials ) {
			return $r;
		}

		if ( $r >= QueryWriter::C_DATATYPE_RANGE_SPECIAL ) {
			return self::C_DATATYPE_SPECIFIED;
		}

		return $r;
	}

	/**
	 * @see QueryWriter::addUniqueIndex
	 */
	public function addUniqueConstraint( $type, $properties )
	{
		$tableNoQ = $this->safeTable( $type, TRUE );
		$columns = array();
		foreach( $properties as $key => $column ) $columns[$key] = $this->safeColumn( $column );
		$table = $this->safeTable( $type );
		sort( $columns ); // Else we get multiple indexes due to order-effects
		$name = 'UQ_' . sha1( implode( ',', $columns ) );
		try {
			$sql = "ALTER TABLE $table
						 ADD UNIQUE INDEX $name (" . implode( ',', $columns ) . ")";
		} catch (SQLException $e ) {
			//do nothing, dont use alter table ignore, this will delete duplicate records in 3-ways!
		}
		$this->adapter->exec( $sql );
	}

	/**
	 * @see QueryWriter::addIndex
	 */
	public function addIndex( $type, $name, $property )
	{
		try {
			$table  = $this->safeTable( $type );
			$name   = preg_replace( '/\W/', '', $name );
			$column = $this->safeColumn( $property );
			$this->adapter->exec( "CREATE INDEX $name ON $table ($column) " );
			return TRUE;
		} catch (SQLException $e ) {
			return FALSE;
		}
	}

	/**
	 * @see QueryWriter::addFK
	 */
	public function addFK( $type, $targetType, $property, $targetProperty, $isDependent = FALSE )
	{
		$table = $this->safeTable( $type );
		$targetTable = $this->safeTable( $targetType );
		$targetTableNoQ = $this->safeTable( $targetType, TRUE );
		$field = $this->safeColumn( $property );
		$fieldNoQ = $this->safeColumn( $property, TRUE );
		$targetField = $this->safeColumn( $targetProperty );
		$targetFieldNoQ = $this->safeColumn( $targetProperty, TRUE );
		$tableNoQ = $this->safeTable( $type, TRUE );
		$fieldNoQ = $this->safeColumn( $property, TRUE );
		if ( !is_null( $this->getForeignKeyForTypeProperty( $tableNoQ, $fieldNoQ ) ) ) return FALSE;
		
		//Widen the column if it's incapable of representing a foreign key (at least INT).
		$columns = $this->getColumns( $tableNoQ );
		$idType = $this->getTypeForID();
		if ( $this->code( $columns[$fieldNoQ] ) !== $idType ) {
			$this->widenColumn( $type, $property, $idType );
		}
		
		$fkName = 'fk_'.($tableNoQ.'_'.$fieldNoQ);
		$cName = 'c_'.$fkName;
		try {
			$fkName = 'fk_'.($type.'_'.$field);
			$cName = 'c_'.$fkName;
			$this->adapter->exec( "
				ALTER TABLE {$table}
				ADD CONSTRAINT $cName
				FOREIGN KEY $fkName ( {$fieldNoQ} ) REFERENCES {$targetTableNoQ}
				({$targetFieldNoQ}) ON DELETE " . ( $isDependent ? 'CASCADE' : 'SET NULL' ) . ' ON UPDATE '.( $isDependent ? 'CASCADE' : 'SET NULL' ).';');

		} catch (SQLException $e ) {
			// Failure of fk-constraints is not a problem
		}
	}

	/**
	 * @see QueryWriter::sqlStateIn
	 */
	public function sqlStateIn( $state, $list )
	{
		$stateMap = [
			'42S02' => QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
			'42S22' => QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			'23000' => QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION
		];

		return in_array( ( isset( $stateMap[$state] ) ? $stateMap[$state] : '0' ), $list );
	}

	/**
	 * @see QueryWriter::wipeAll
	 */
	public function _wipeAll()
	{
		$this->adapter->exec( 'SET FOREIGN_KEY_CHECKS = 0;' );

		foreach ( $this->getTables() as $t ) {
			try {
				$this->adapter->exec( "DROP TABLE IF EXISTS `$t`" );
			} catch (SQLException $e ) {
			}

			try {
				$this->adapter->exec( "DROP VIEW IF EXISTS `$t`" );
			} catch (SQLException $e ) {
			}
		}

		$this->adapter->exec( 'SET FOREIGN_KEY_CHECKS = 1;' );
	}

	public function _drop($t){
		$this->adapter->exec( 'SET FOREIGN_KEY_CHECKS = 0;' );
		try {
			$this->adapter->exec( "DROP TABLE IF EXISTS `$t`" );
		} catch (SQLException $e ) {
		}

		try {
			$this->adapter->exec( "DROP VIEW IF EXISTS `$t`" );
		} catch (SQLException $e ) {
		}
		$this->adapter->exec( 'SET FOREIGN_KEY_CHECKS = 1;' );
	}
	
	
	/**
	 * Internal method, returns a map of foreign key constraints for
	 * the current database (and schema) and the specified table
	 * and field.
	 *
	 * @param string $table  table name
	 *
	 * @return array
	 * @see QueryWriter::getKeyMapForType
	 */
	protected function getKeyMapForType( $type )
	{
		$table = $this->safeTable( $type, TRUE );
		$keys = $this->adapter->get('
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
		', array($table));
		$keyInfoList = array();
		foreach ( $keys as $k ) {
			$label = $this->makeFKLabel( $k['from'], $k['table'], $k['to'] );
			$keyInfoList[$label] = array(
				'name'          => $k['name'],
				'from'          => $k['from'],
				'table'         => $k['table'],
				'to'            => $k['to'],
				'on_update'     => $k['on_update'],
				'on_delete'     => $k['on_delete']
			);
		}
		return $keyInfoList;
	}
}