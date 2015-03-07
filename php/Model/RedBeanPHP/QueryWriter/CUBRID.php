<?php 
namespace Surikat\Model\RedBeanPHP\QueryWriter; 
use Surikat\Model\RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;
use Surikat\Model\RedBeanPHP\QueryWriter as QueryWriter;
use Surikat\Model\RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use Surikat\Model\RedBeanPHP\Adapter as Adapter; 
use Surikat\Model\RedBeanPHP\Database;
use Surikat\Model\RedBeanPHP\RedException\SQL as SQLException;
/**
 * RedBean CUBRID Writer
 *
 * @file    RedBeanPHP/QueryWriter/CUBRID.php
 * @desc    Represents a CUBRID Database to RedBean
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class CUBRID extends AQueryWriter implements QueryWriter
{
	protected $caseSupport = false;
	/**
	 * Data types
	 */
	const C_DATATYPE_INTEGER          = 0;
	const C_DATATYPE_DOUBLE           = 1;
	const C_DATATYPE_STRING           = 2;
	const C_DATATYPE_SPECIAL_DATE     = 80;
	const C_DATATYPE_SPECIAL_DATETIME = 81;
	const C_DATATYPE_SPECIFIED        = 99;
	
	/**
	 * @var string
	 */
	protected $quoteCharacter = '`';

	/**
	 * @see AQueryWriter::getKeyMapForType
	 */
	protected function getKeyMapForType( $type)
	{
		$sqlCode = $this->adapter->get("SHOW CREATE TABLE `{$type}`");
		if (!isset($sqlCode[0])) return array();
		$matches = array();
		preg_match_all( '/CONSTRAINT\s+\[([\w_]+)\]\s+FOREIGN\s+KEY\s+\(\[([\w_]+)\]\)\s+REFERENCES\s+\[([\w_]+)\](\s+ON\s+DELETE\s+(CASCADE|SET\sNULL|RESTRICT|NO\sACTION)\s+ON\s+UPDATE\s+(SET\sNULL|RESTRICT|NO\sACTION))?/', $sqlCode[0]['CREATE TABLE'], $matches );
		$list = array();
		if (!isset($matches[0])) return $list;
		$max = count($matches[0]);
		for($i = 0; $i < $max; $i++) {
			$label = $this->makeFKLabel( $matches[2][$i], $matches[3][$i], 'id' );
			$list[ $label ] = array(
				'name' => $matches[1][$i],
				'from' => $matches[2][$i],
				'table' => $matches[3][$i],
				'to' => 'id',
				'on_update' => $matches[6][$i],
				'on_delete' => $matches[5][$i]
			);
		}
		return $list;
	}

	/**
	 * This method adds a foreign key from type and field to
	 * target type and target field.
	 * The foreign key is created without an action. On delete/update
	 * no action will be triggered. The FK is only used to allow database
	 * tools to generate pretty diagrams and to make it easy to add actions
	 * later on.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 *
	 * @param  string $type           type that will have a foreign key field
	 * @param  string $targetType     points to this type
	 * @param  string $field          field that contains the foreign key value
	 * @param  string $targetField    field where the fk points to
	 *
	 * @return void
	 */
	protected function buildFK( $type, $targetType, $property, $targetProperty, $isDep = FALSE )
	{
		$table           = $this->safeTable( $type );
		$tableNoQ        = $this->safeTable( $type, TRUE );
		$targetTable     = $this->safeTable( $targetType );
		$targetTableNoQ  = $this->safeTable( $targetType, TRUE );
		$column          = $this->safeColumn( $property );
		$columnNoQ       = $this->safeColumn( $property, TRUE );
		$targetColumn    = $this->safeColumn( $targetProperty );
		if ( !is_null( $this->getForeignKeyForTypeProperty( $tableNoQ, $columnNoQ ) ) ) return FALSE;
		$needsToDropFK   = FALSE;
		$casc = ( $isDep ? 'CASCADE' : 'SET NULL' );
		$sql  = "ALTER TABLE $table ADD CONSTRAINT FOREIGN KEY($column) REFERENCES $targetTable($targetColumn) ON DELETE $casc ";
		try {
			$this->adapter->exec( $sql );
		} catch(SQLException $e ) {
			return FALSE;
		}
	}

	/**
	 * Constructor
	 *
	 * @param Adapter $adapter Database Adapter
	 */
	public function __construct( Adapter $a, Database $db, $prefix='', $case=false )
	{
		parent::__construct($a,$db,$prefix,$case);
		$this->typeno_sqltype = [
			CUBRID::C_DATATYPE_INTEGER          => 'INTEGER',
			CUBRID::C_DATATYPE_DOUBLE           => 'DOUBLE',
			CUBRID::C_DATATYPE_STRING           => 'STRING',
			CUBRID::C_DATATYPE_SPECIAL_DATE     => 'DATE',
			CUBRID::C_DATATYPE_SPECIAL_DATETIME => 'DATETIME',
		];
		foreach ( $this->typeno_sqltype as $k => $v ) {
			$this->sqltype_typeno[trim( ( $v ) )] = $k;
		}
		$this->sqltype_typeno['STRING(1073741823)'] = self::C_DATATYPE_STRING;
	}

	/**
	 * This method returns the datatype to be used for primary key IDS and
	 * foreign keys. Returns one if the data type constants.
	 *
	 * @return integer $const data type to be used for IDS.
	 */
	public function getTypeForID()
	{
		return self::C_DATATYPE_INTEGER;
	}

	/**
	 * @see QueryWriter::getTables
	 */
	public function _getTables()
	{
		$rows = $this->adapter->getCol( "SELECT class_name FROM db_class WHERE is_system_class = 'NO';" );

		return $rows;
	}

	/**
	 * @see QueryWriter::createTable
	 */
	public function _createTable( $table )
	{
		$sql  = 'CREATE TABLE '
			. $this->safeTable( $table )
			. ' ("id" integer AUTO_INCREMENT, CONSTRAINT "pk_'
			. $this->safeTable( $table, TRUE )
			. '_id" PRIMARY KEY("id"))';

		$this->adapter->exec( $sql );
	}

	/**
	 * @see QueryWriter::getColumns
	 */
	public function _getColumns( $table )
	{
		$table = $this->safeTable( $table );

		$columnsRaw = $this->adapter->get( "SHOW COLUMNS FROM $table" );

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

		if ( is_null( $value ) ) {
			return self::C_DATATYPE_INTEGER;
		}

		if ( $flagSpecial ) {
			if ( preg_match( '/^\d{4}\-\d\d-\d\d$/', $value ) ) {
				return self::C_DATATYPE_SPECIAL_DATE;
			}
			if ( preg_match( '/^\d{4}\-\d\d-\d\d\s\d\d:\d\d:\d\d$/', $value ) ) {
				return self::C_DATATYPE_SPECIAL_DATETIME;
			}
		}

		$value = strval( $value );

		if ( !$this->startsWithZeros( $value ) ) {
			if ( is_numeric( $value ) && ( floor( $value ) == $value ) && $value >= -2147483647 && $value <= 2147483647 ) {
				return self::C_DATATYPE_INTEGER;
			}
			if ( is_numeric( $value ) ) {
				return self::C_DATATYPE_DOUBLE;
			}
		}

		return self::C_DATATYPE_STRING;
	}

	/**
	 * @see QueryWriter::code
	 */
	public function code( $typedescription, $includeSpecials = FALSE )
	{
		$r = ( ( isset( $this->sqltype_typeno[$typedescription] ) ) ? $this->sqltype_typeno[$typedescription] : self::C_DATATYPE_SPECIFIED );

		if ( $includeSpecials ) {
			return $r;
		}

		if ( $r >= QueryWriter::C_DATATYPE_RANGE_SPECIAL ) {
			return self::C_DATATYPE_SPECIFIED;
		}

		return $r;
	}

	/**
	 * @see QueryWriter::addColumn
	 */
	public function _addColumn( $type, $column, $field )
	{
		$table  = $type;
		$type   = $field;

		$table  = $this->safeTable( $table );
		$column = $this->safeColumn( $column );

		$type   = array_key_exists( $type, $this->typeno_sqltype ) ? $this->typeno_sqltype[$type] : '';

		$this->adapter->exec( "ALTER TABLE $table ADD COLUMN $column $type " );
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
		sort( $columns ); // else we get multiple indexes due to order-effects
		$name = 'UQ_' . sha1( implode( ',', $columns ) );
		$sql = "ALTER TABLE $table ADD CONSTRAINT UNIQUE $name (" . implode( ',', $columns ) . ")";
		$this->adapter->exec( $sql );
	}

	/**
	 * @see QueryWriter::sqlStateIn
	 */
	public function sqlStateIn( $state, $list )
	{
		return ( $state == 'HY000' ) ? ( count( array_diff( [
				QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION,
				QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
				QueryWriter::C_SQLSTATE_NO_SUCH_TABLE
			], $list ) ) !== 3 ) : FALSE;
	}

	/**
	 * @see QueryWriter::addIndex
	 */
	public function addIndex( $type, $name, $column )
	{
		try {
			$table  = $this->safeTable( $type );
			$name   = preg_replace( '/\W/', '', $name );
			$column = $this->safeColumn( $column );
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
		return $this->buildFK( $type, $targetType, $property, $targetProperty, $isDependent );
	}

	/**
	 * @see QueryWriter::wipeAll
	 */
	public function _wipeAll()
	{
		foreach( $this->getTables() as $t ){
			foreach ( $this->getKeyMapForType( $t ) as $k ) {
				$this->adapter->exec( "ALTER TABLE \"$t\" DROP FOREIGN KEY \"{$k['name']}\"" );
			}
		}
		foreach ( $this->getTables() as $t ) {
			$this->drop($t);
		}
	}

	public function _drop($t){
		foreach ( $this->getExportImportKeys( $t ) as $k ) {
			$this->adapter->exec( "ALTER TABLE \"{$k['FKTABLE_NAME']}\" DROP FOREIGN KEY \"{$k['FK_NAME']}\"" );
		}
		$this->adapter->exec( "DROP TABLE \"$t\"" );
	}
	
	/**
	* @see QueryWriter::inferFetchType
	*/
	public function inferFetchType( $type, $property ){
		$table = $this->safeTable( $type, TRUE );
		$field = $this->safeColumn( $property, TRUE ) . '_id';
		$keys = $this->getKeyMapForType( $table );
		foreach( $keys as $key ) {
			if($key['from'] === $field)
				return $key['table'];
		}
		return NULL;
	}
}