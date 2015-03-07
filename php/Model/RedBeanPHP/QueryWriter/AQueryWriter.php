<?php

namespace Surikat\Model\RedBeanPHP\QueryWriter;

use Surikat\Model\RedBeanPHP\Adapter;
use Surikat\Model\RedBeanPHP\Database;
use Surikat\Model\RedBeanPHP\Adapter\DBAdapter;
use Surikat\Model\RedBeanPHP\RedException;
use Surikat\Model\RedBeanPHP\QueryWriter;
use Surikat\Model\RedBeanPHP\OODBBean;
use Surikat\Model\RedBeanPHP\RedException\SQL as SQLException;

/**
 * RedBean Abstract Query Writer
 *
 * @file    RedBeanPHP/QueryWriter/AQueryWriter.php
 * @desc    Query Writer (abstract class)
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * Represents an abstract Database to RedBean
 * To write a driver for a different database for RedBean
 * Contains a number of functions all implementors can
 * inherit or override.
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
abstract class AQueryWriter {
	/**
	 * @var DBAdapter
	 */
	protected $adapter;
	protected $database;
	
	/**
	 * @var string
	 */
	protected $defaultValue = 'NULL';

	/**
	 * @var string
	 */
	protected $quoteCharacter = '';

	/**
	 * @var boolean
	 */
	protected $flagUseCache = TRUE;

	/**
	 * @var array
	 */
	protected $cache = [];

	/**
	 * @var array
	 */
	public static $renames = [];

	/**
	 * @var array
	 */
	public static $sqlFilters = [];
	
	/**
	* @var boolean
	*/
	private static $flagSQLFilterSafeMode = false;
	
	/**
	* @var boolean
	*/
	private static $flagNarrowFieldMode = true;

	/**
	 * @var array
	 */
	public $typeno_sqltype = [];
	public $sqltype_typeno = [];
	
	function __get($k){
		if(property_exists($this,$k))
			return $this->$k;
	}
	
	protected $caseSensitive;
	protected $prefix;
	protected $prefixL;
	function __construct(Adapter $a, Database $db, $prefix='', $case=true){
		$this->adapter = $a;
		$this->database = $db;
		$this->prefix = $prefix;
		$this->prefixL = strlen($prefix);
		$this->caseSensitive = $case;
	}
	function getDatabase(){
		return $this->database;
	}
	
	private static $_allTables = null;
	private static $_allColumns = [];
	function unPrefix($v,$check=true){
		if(!$check||substr($v,0,$this->prefixL)==$this->prefix)
			return substr($v,$this->prefixL);
		else
			return $v;
	}
	function getTables(){
		if(!isset(self::$_allTables)){
			self::$_allTables = $this->_getTables();
			if($this->prefix){
				foreach(array_keys(self::$_allTables) as $k){
					if(substr(self::$_allTables[$k],0,$this->prefixL)==$this->prefix)
						self::$_allTables[$k] = $this->unPrefix(self::$_allTables[$k],false);
					else
						unset(self::$_allTables[$k]);
				}
			}
		}
		return self::$_allTables;
	}
	function createTable($table){
		if(isset(self::$_allTables)&&!in_array($table,self::$_allTables))
			self::$_allTables[] = $table;
		return $this->_createTable($table);
	}
	function addColumn( $table, $column, $datatype ){
		$datatype = trim($datatype);
		if(isset(self::$_allColumns[$table])){
			$newtype = isset($this->typeno_sqltype[$datatype])?$this->typeno_sqltype[$datatype]:'';
			self::$_allColumns[$table][$column] = $newtype;
		}
		return $this->_addColumn($table, $column, $datatype);
	}
	function widenColumn( $table, $column, $datatype ){
		$datatype = trim($datatype);
		if(!isset($this->typeno_sqltype[$datatype]))
			return;
		if(isset(self::$_allColumns[$table])){
			$newtype = isset($this->typeno_sqltype[$datatype])?$this->typeno_sqltype[$datatype]:'';
			self::$_allColumns[$table][$column] = $newtype;
		}
		return $this->_widenColumn($table, $column, $datatype);
	}
	function getColumns($table){
		if(!isset(self::$_allColumns[$table]))
			self::$_allColumns[$table] = $this->_getColumns($table);
		return self::$_allColumns[$table];
	}
	function wipeAll(){
		$this->_wipeAll();
		self::$_allTables = [];
		self::$_allColumns = [];
	}
	function drop($t){
		if(isset(self::$_allTables)&&($i=array_search($t,self::$_allTables))!==false)
			unset(self::$_allTables[$i]);
		if(isset(self::$_allColumns[$t]))
			unset(self::$_allColumns[$t]);
		$this->_drop($t);
	}
	function columnExists($table,$column){
		return in_array($column,array_keys($this->getColumns( $table )));
	}
	
	function adaptCase($str){
		if($this->caseSensitive)
			return $str;
		return self::snakeCase($str);
	}
	function reverseCase($str){
		if($this->caseSensitive)
			return $str;
		return self::camelCase($str);
	}
	static function snakeCase($str){
        return str_replace(' ', '_', strtolower(preg_replace('/([a-z])([A-Z])/', '$1 $2', $str))); 
		//return strtolower(preg_replace('/(?<=[a-z])([A-Z])|([A-Z])(?=[a-z])/', '-$1$2', $str ));
	}
	static function camelCase($str){
		$str = ucwords(str_replace('_', ' ', $str));
        $str = str_replace(' ', '', $str);
        $str = lcfirst($str);
		return $str;
	}
	static function camelsSnakeCase(){
		if(func_num_args())
			self::$camelsSnakeCase = func_get_arg(0);
		else
			return self::$camelsSnakeCase;
	}
	
	/**
	 * Clears renames.
	 *
	 * @return void
	 */
	public static function clearRenames()
	{
		self::$renames = [];
	}
	
	/**
	* Toggles 'Narrow Field Mode'.
	* In Narrow Field mode the queryRecord method will
	* narrow its selection field to
	*
	* SELECT {table}.*
	*
	* instead of
	*
	* SELECT *
	*
	* This is a better way of querying because it allows
	* more flexibility (for instance joins). However if you need
	* the wide selector for backward compatibility; use this method
	* to turn OFF Narrow Field Mode by passing FALSE.
	*
	* @param boolean $narrowField TRUE = Narrow Field FALSE = Wide Field
	*
	* @return void
	*/
	public static function setNarrowFieldMode( $narrowField )
	{
		self::$flagNarrowFieldMode = (boolean) $narrowField;
	}
	
	/**
	 * Sets SQL filters.
	 * This is a lowlevel method to set the SQL filter array.
	 * The format of this array is:
	 *
	 * array(
	 * 		'<MODE, i.e. 'r' for read, 'w' for write>' => array(
	 * 			'<TABLE NAME>' => array(
	 * 				'<COLUMN NAME>' => '<SQL>'
	 * 			)
	 * 		)
	 * )
	 *
	 * Example:
	 *
	 * array(
	 * QueryWriter::C_SQLFILTER_READ => array(
	 * 	'book' => array(
	 * 		'title' => ' LOWER(book.title) '
	 * 	)
	 * )
	 *
	 * Note that you can use constants instead of magical chars
	 * as keys for the uppermost array.
	 * This is a lowlevel method. For a more friendly method
	 * please take a look at the facade: R::bindFunc().
	 *
	 * @param array
	 */
	public static function setSQLFilters( $sqlFilters, $safeMode = false )
	{
		self::$flagSQLFilterSafeMode = (boolean) $safeMode;
		self::$sqlFilters = $sqlFilters;
	}

	/**
	 * Returns current SQL Filters.
	 * This method returns the raw SQL filter array.
	 * This is a lowlevel method. For a more friendly method
	 * please take a look at the facade: R::bindFunc().
	 *
	 * @return array
	 */
	public static function getSQLFilters()
	{
		return self::$sqlFilters;
	}

	/**
	 * Returns an SQL Filter snippet for reading.
	 *
	 * @param string $type type of bean
	 *
	 * @return string
	 */
	protected function getSQLFilterSnippet( $type )
	{
		$existingCols = array();
		if (self::$flagSQLFilterSafeMode) {
			$existingCols = $this->getColumns( $type );
		}
		
		$sqlFilters = [];
		if ( isset( self::$sqlFilters[QueryWriter::C_SQLFILTER_READ][$type] ) ) {
			foreach( self::$sqlFilters[QueryWriter::C_SQLFILTER_READ][$type] as $property => $sqlFilter ) {
				if ( !self::$flagSQLFilterSafeMode || isset( $existingCols[$property] ) ) {
					$sqlFilters[] = $sqlFilter.' AS '.$property.' ';
				}
			}
		}
		$sqlFilterStr = ( count($sqlFilters) ) ? ( ','.implode( ',', $sqlFilters ) ) : '';
		return $sqlFilterStr;
	}

	/**
	 * Generates a list of parameters (slots) for an SQL snippet.
	 * This method calculates the correct number of slots to insert in the
	 * SQL snippet and determines the correct type of slot. If the bindings
	 * array contains named parameters this method will return named ones and
	 * update the keys in the value list accordingly (that's why we use the &).
	 *
	 * If you pass an offset the bindings will be re-added to the value list.
	 * Some databases cant handle duplicate parameter names in queries.
	 *
	 * @param array   &$valueList     list of values to generate slots for (gets modified if needed)
	 * @param array   $otherBindings  list of additional bindings
	 * @param integer $offset         start counter at...
	 *
	 * @return string
	 */
	protected function getParametersForInClause( &$valueList, $otherBindings, $offset = 0 )
	{
		if ( is_array( $otherBindings ) && count( $otherBindings ) > 0 ) {
			reset( $otherBindings );

			$key = key( $otherBindings );

			if ( !is_numeric($key) ) {
				$filler  = [];
				$newList = (!$offset) ? [] : $valueList;
				$counter = $offset;

				foreach( $valueList as $value ) {
					$slot           = ':slot' . ( $counter++ );
					$filler[]       = $slot;
					$newList[$slot] = $value;
				}

				// Change the keys!
				$valueList = $newList;

				return implode( ',', $filler );
			}
		}

		return implode( ',', array_fill( 0, count( $valueList ), '?' ) );
	}

	/**
	 * Returns a cache key for the cache values passed.
	 * This method returns a fingerprint string to be used as a key to store
	 * data in the writer cache.
	 *
	 * @param array $keyValues key-value to generate key for
	 *
	 * @return string
	 */
	private function getCacheKey( $keyValues )
	{
		return json_encode( $keyValues );
	}

	/**
	 * Returns the values associated with the provided cache tag and key.
	 *
	 * @param string $cacheTag cache tag to use for lookup
	 * @param string $key      key to use for lookup
	 *
	 * @return mixed
	 */
	private function getCached( $cacheTag, $key )
	{
		$sql = $this->adapter->getSQL();

		if ($this->updateCache()) {
			if ( isset( $this->cache[$cacheTag][$key] ) ) {
				return $this->cache[$cacheTag][$key];
			}
		}

		return NULL;
	}

	/**
	 * Checks if the previous query had a keep-cache tag.
	 * If so, the cache will persist, otherwise the cache will be flushed.
	 *
	 * Returns TRUE if the cache will remain and FALSE if a flush has
	 * been performed.
	 *
	 * @return boolean
	 */
	private function updateCache()
	{
		$sql = $this->adapter->getSQL();
		if ( strpos( $sql, '-- keep-cache' ) !== strlen( $sql ) - 13 ) {
			// If SQL has been taken place outside of this method then something else then
			// a select query might have happened! (or instruct to keep cache)
			$this->cache = [];
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Stores data from the writer in the cache under a specific key and cache tag.
	 * A cache tag is used to make sure the cache remains consistent. In most cases the cache tag
	 * will be the bean type, this makes sure queries associated with a certain reference type will
	 * never contain conflicting data.
	 * Why not use the cache tag as a key? Well
	 * we need to make sure the cache contents fits the key (and key is based on the cache values).
	 * Otherwise it would be possible to store two different result sets under the same key (the cache tag).
	 *
	 * @param string $cacheTag cache tag (secondary key)
	 * @param string $key      key
	 * @param array  $values   content to be stored
	 *
	 * @return void
	 */
	private function putResultInCache( $cacheTag, $key, $values )
	{
		if (!isset($this->cache[$cacheTag]))
			$this->cache[$cacheTag] = array();
		$this->cache[$cacheTag][$key] = $values;
	}

	/**
	 * Creates an SQL snippet from a list of conditions of format:
	 *
	 * array(
	 *    key => array(
	 *           value1, value2, value3 ....
	 *        )
	 * )
	 *
	 * @param array  $conditions list of conditions
	 * @param array  $bindings   parameter bindings for SQL snippet
	 * @param string $addSql     SQL snippet
	 *
	 * @return string
	 */
	private function makeSQLFromConditions( $conditions, &$bindings, $addSql = '' )
	{
		reset( $bindings );
		$firstKey       = key( $bindings );
		$paramTypeIsNum = ( is_numeric( $firstKey ) );
		$counter        = 0;

		$sqlConditions = [];
		foreach ( $conditions as $column => $values ) {
			if ( !count( $values ) ) continue;

			$sql = $this->safeColumn( $column );
			$sql .= ' IN ( ';

			if ( !is_array( $values ) ) $values = [ $values ];

			// If it's safe to skip bindings, do so...
			if ( ctype_digit( implode( '', $values ) ) ) {
				$sql .= implode( ',', $values ) . ' ) ';

				// only numeric, cant do much harm
				$sqlConditions[] = $sql;
			} else {

				if ( $paramTypeIsNum ) {
					$sql .= implode( ',', array_fill( 0, count( $values ), '?' ) ) . ' ) ';

					array_unshift($sqlConditions, $sql);

					foreach ( $values as $k => $v ) {
						$values[$k] = strval( $v );

						array_unshift( $bindings, $v );
					}
				} else {

					$slots = [];

					foreach( $values as $k => $v ) {
						$slot            = ':slot'.$counter++;
						$slots[]         = $slot;
						$bindings[$slot] = strval( $v );
					}

					$sql .= implode( ',', $slots ).' ) ';
					$sqlConditions[] = $sql;
				}
			}
		}

		$sql = '';
		if ( is_array( $sqlConditions ) && count( $sqlConditions ) > 0 ) {
			$sql = implode( ' AND ', $sqlConditions );
			$sql = " WHERE ( $sql ) ";

			if ( $addSql ) $sql .= $addSql;
		} elseif ( $addSql ) {
			$sql = $addSql;
		}

		return $sql;
	}

	/**
	 * Returns the table names and column names for a relational query.
	 *
	 * @param string  $sourceType type of the source bean
	 * @param string  $destType   type of the bean you want to obtain using the relation
	 * @param boolean $noQuote    TRUE if you want to omit quotes
	 *
	 * @return array
	 */
	private function getRelationalTablesAndColumns( $sourceType, $destType, $noQuote = FALSE )
	{
		$linkTable   = $this->safeTable( $this->getAssocTable( [ $sourceType, $destType ] ), $noQuote );
		$sourceCol   = $this->safeColumn( $sourceType . '_id', $noQuote );

		if ( $sourceType === $destType ) {
			$destCol = $this->safeColumn( $destType . '2_id', $noQuote );
		} else {
			$destCol = $this->safeColumn( $destType . '_id', $noQuote );
		}

		$sourceTable = $this->safeTable( $sourceType, $noQuote );
		$destTable   = $this->safeTable( $destType, $noQuote );

		return [ $sourceTable, $destTable, $linkTable, $sourceCol, $destCol ];
	}
	
	/**
	* Adds a data type to the list of data types.
	* Use this method to add a new column type definition to the writer.
	* Used for UUID support.
	*
	* @param integer $dataTypeID magic number constant assigned to this data type
	* @param string $SQLDefinition SQL column definition (i.e. INT(11))
	*
	* @return self
	*/
	protected function addDataType( $dataTypeID, $SQLDefinition )
	{
		$this->typeno_sqltype[ $dataTypeID ] = $SQLDefinition;
		$this->sqltype_typeno[ $SQLDefinition ] = $dataTypeID;
		return $this;
	}
	
	/**
	 * Returns the sql that should follow an insert statement.
	 *
	 * @param string $table name
	 *
	 * @return string
	 */
	protected function getInsertSuffix( $table )
	{
		return '';
	}

	/**
	 * Checks whether a value starts with zeros. In this case
	 * the value should probably be stored using a text datatype instead of a
	 * numerical type in order to preserve the zeros.
	 *
	 * @param string $value value to be checked.
	 *
	 * @return boolean
	 */
	protected function startsWithZeros( $value )
	{
		$value = strval( $value );

		if ( strlen( $value ) > 1 && strpos( $value, '0' ) === 0 && strpos( $value, '0.' ) !== 0 ) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Inserts a record into the database using a series of insert columns
	 * and corresponding insertvalues. Returns the insert id.
	 *
	 * @param string $table         table to perform query on
	 * @param array  $insertcolumns columns to be inserted
	 * @param array  $insertvalues  values to be inserted
	 *
	 * @return integer
	 */
	protected function insertRecord( $type, $insertcolumns, $insertvalues )
	{
		$default = $this->defaultValue;
		$suffix  = $this->getInsertSuffix( $type );
		$table   = $this->safeTable( $type );

		if ( count( $insertvalues ) > 0 && is_array( $insertvalues[0] ) && count( $insertvalues[0] ) > 0 ) {

			$insertSlots = [];
			foreach ( $insertcolumns as $k => $v ) {
				$insertcolumns[$k] = $this->safeColumn( $v );

				if (isset(self::$sqlFilters['w'][$type][$v])) {
					$insertSlots[] = self::$sqlFilters['w'][$type][$v];
				} else {
					$insertSlots[] = '?';
				}
			}

			$insertSQL = "INSERT INTO $table ( id, " . implode( ',', $insertcolumns ) . " ) VALUES
			( $default, " . implode( ',', $insertSlots ) . " ) $suffix";

			$ids = [];
			foreach ( $insertvalues as $i => $insertvalue ) {
				$ids[] = $this->adapter->getCell( $insertSQL, $insertvalue, $i );
			}

			$result = count( $ids ) === 1 ? array_pop( $ids ) : $ids;
		} else {
			$result = $this->adapter->getCell( "INSERT INTO $table (id) VALUES($default) $suffix" );
		}

		if ( $suffix ) return $result;

		$last_id = $this->adapter->getInsertID();

		return $last_id;
	}

	/**
	 * Checks table name or column name.
	 *
	 * @param string $table table string
	 *
	 * @return string
	 *
	 * @throws Security
	 */
	protected function check( $struct )
	{
		//if ( !preg_match( '/^[a-zA-Z0-9_]+$/', $struct ) ) {
		if ( !is_string( $struct ) || !preg_match( '/^[a-zA-Z0-9_-]+$/', $struct ) ) {
			throw new RedException( 'Identifier "'.$struct.'" does not conform to RedBeanPHP security policies.' );
		}

		return $struct;
	}

	/**
	 * Checks whether a number can be treated like an int.
	 *
	 * @param  string $value string representation of a certain value
	 *
	 * @return boolean
	 */
	public static function canBeTreatedAsInt( $value )
	{
		return (bool) ( ctype_digit( strval( $value ) ) && strval( $value ) === strval( intval( $value ) ) );
	}

	/**
	 * @see QueryWriter::getAssocTableFormat
	 */
	public static function getAssocTableFormat( $types )
	{
		sort( $types );

		$assoc = implode( '_', $types );

		return ( isset( self::$renames[$assoc] ) ) ? self::$renames[$assoc] : $assoc;
	}

	/**
	 * @see QueryWriter::renameAssociation
	 */
	public static function renameAssociation( $from, $to = NULL )
	{
		if ( is_array( $from ) ) {
			foreach ( $from as $key => $value ) self::$renames[$key] = $value;

			return;
		}

		self::$renames[$from] = $to;
	}

	/**
	 * Checks whether the specified type (i.e. table) already exists in the database.
	 * Not part of the Object Database interface!
	 *
	 * @param string $table table name
	 *
	 * @return boolean
	 */
	public function tableExists( $table )
	{
		$tables = $this->getTables();
		$table = $this->unPrefix($table);
		return in_array( $table, $tables );
	}

	/**
	 * @see QueryWriter::glueSQLCondition
	 */
	public function glueSQLCondition( $sql, $glue = NULL )
	{
		static $snippetCache = [];

		if ( trim( $sql ) === '' ) {
			return $sql;
		}

		$key = $glue . '|' . $sql;

		if ( isset( $snippetCache[$key] ) ) {
			return $snippetCache[$key];
		}

		$lsql = ltrim( $sql );

		if ( preg_match( '/^(INNER|LEFT|RIGHT|JOIN|AND|OR|WHERE|ORDER|GROUP|HAVING|LIMIT|OFFSET)\s+/i', $lsql ) ) {
			if ( $glue === QueryWriter::C_GLUE_WHERE && stripos( $lsql, 'AND' ) === 0 ) {
				$snippetCache[$key] = ' WHERE ' . substr( $lsql, 3 );
			} else {
				$snippetCache[$key] = $sql;
			}
		} else {
			$snippetCache[$key] = ( ( $glue === QueryWriter::C_GLUE_AND ) ? ' AND ' : ' WHERE ') . $sql;
		}

		return $snippetCache[$key];
	}
	
	/**
	* @see QueryWriter::glueLimitOne
	*/
	public function glueLimitOne( $sql = '')
	{
		return ( strpos( $sql, 'LIMIT' ) === FALSE ) ? ( $sql . ' LIMIT 1 ' ) : $sql;
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

		$type = ( isset( $this->typeno_sqltype[$type] ) ) ? $this->typeno_sqltype[$type] : '';

		$this->adapter->exec( "ALTER TABLE $table ADD $column $type " );
	}

	/**
	 * @see QueryWriter::updateRecord
	 */
	public function updateRecord( $type, $updatevalues, $id = NULL )
	{
		$table = $type;

		if ( !$id ) {
			$insertcolumns = $insertvalues = [];

			foreach ( $updatevalues as $pair ) {
				$insertcolumns[] = $pair['property'];
				$insertvalues[]  = $pair['value'];
			}

			//Otherwise psql returns string while MySQL/SQLite return numeric causing problems with additions (array_diff)
			return (string) $this->insertRecord( $table, $insertcolumns, [ $insertvalues ] );
		}

		if ( $id && !count( $updatevalues ) ) {
			return $id;
		}

		$table = $this->safeTable( $table );
		$sql   = "UPDATE $table SET ";

		$p = $v = [];

		foreach ( $updatevalues as $uv ) {

			if ( isset( self::$sqlFilters['w'][$type][$uv['property']] ) ) {
				$p[] = " {$this->safeColumn( $uv["property"] )} = ". self::$sqlFilters['w'][$type][$uv['property']];
			} else {
				$p[] = " {$this->safeColumn( $uv["property"] )} = ? ";
			}

			$v[] = $uv['value'];
		}

		$sql .= implode( ',', $p ) . ' WHERE id = ? ';

		$v[] = $id;

		$this->adapter->exec( $sql, $v );

		return $id;
	}
	
	/**
	* @see QueryWriter::writeJoin
	*/
	public function writeJoin( $type, $targetType, $leftRight = 'LEFT' )
	{
		if ( $leftRight !== 'LEFT' && $leftRight !== 'RIGHT' && $leftRight !== 'INNER' )
			throw new RedException( 'Invalid JOIN.' );
		$table = $this->safeTable( $type );
		$targetTable = $this->safeTable( $targetType );
		$field = $this->safeColumn( $targetType, TRUE );
		return " {$leftRight} JOIN {$targetTable} ON {$targetTable}.id = {$table}.{$field}_id ";
	}
	
	/**
	 * @see QueryWriter::queryRecord
	 */
	public function queryRecord( $type, $conditions = [], $addSql = NULL, $bindings = [] )
	{
		$addSql = $this->glueSQLCondition( $addSql, ( count($conditions) > 0) ? QueryWriter::C_GLUE_AND : NULL );

		$key = NULL;
		if ( $this->flagUseCache ) {
			$key = $this->getCacheKey( [ $conditions, $addSql, $bindings, 'select' ] );

			if ( $cached = $this->getCached( $type, $key ) ) {
				return $cached;
			}
		}

		$table = $this->safeTable( $type );

		$sqlFilterStr = '';
		if ( count( self::$sqlFilters ) ) {
			$sqlFilterStr = $this->getSQLFilterSnippet( $type );
		}

		$sql   = $this->makeSQLFromConditions( $conditions, $bindings, $addSql );
		
		$fieldSelection = ( self::$flagNarrowFieldMode ) ? "{$table}.*" : '*';
		$sql = "SELECT {$fieldSelection} {$sqlFilterStr} FROM {$table} {$sql} -- keep-cache";

		$rows  = $this->adapter->get( $sql, $bindings );


		if ( $this->flagUseCache && $key ) {
			$this->putResultInCache( $type, $key, $rows );
		}

		return $rows;
	}
	
	/**
	 * @see QueryWriter::queryRecordWithCursor
	 */
	public function queryRecordWithCursor( $type, $addSql = NULL, $bindings = array() )
	{
		$sql = $this->glueSQLCondition( $addSql, NULL );
		$table = $this->esc( $type );
		$sql   = "SELECT {$table}.* FROM {$table} {$sql}";
		return $this->adapter->getCursor( $sql, $bindings );
	}

	/**
	 * @see QueryWriter::queryRecordRelated
	 */
	public function queryRecordRelated( $sourceType, $destType, $linkIDs, $addSql = '', $bindings = [] )
	{
		$addSql = $this->glueSQLCondition( $addSql, QueryWriter::C_GLUE_WHERE );

		list( $sourceTable, $destTable, $linkTable, $sourceCol, $destCol ) = $this->getRelationalTablesAndColumns( $sourceType, $destType );

		$key = $this->getCacheKey( [ $sourceType, $destType, implode( ',', $linkIDs ), $addSql, $bindings ] );

		if ( $this->flagUseCache && $cached = $this->getCached( $destType, $key ) ) {
			return $cached;
		}

		$inClause = $this->getParametersForInClause( $linkIDs, $bindings );

		$sqlFilterStr = '';
		if ( count( self::$sqlFilters ) ) {
			$sqlFilterStr = $this->getSQLFilterSnippet( $destType );
		}

		if ( $sourceType === $destType ) {
			$inClause2 = $this->getParametersForInClause( $linkIDs, $bindings, count( $bindings ) ); //for some databases
			$sql = "
			SELECT
				{$destTable}.* {$sqlFilterStr} ,
				COALESCE(
				NULLIF({$linkTable}.{$sourceCol}, {$destTable}.id),
				NULLIF({$linkTable}.{$destCol}, {$destTable}.id)) AS linked_by
			FROM {$linkTable}
			INNER JOIN {$destTable} ON
			( {$destTable}.id = {$linkTable}.{$destCol} AND {$linkTable}.{$sourceCol} IN ($inClause) ) OR
			( {$destTable}.id = {$linkTable}.{$sourceCol} AND {$linkTable}.{$destCol} IN ($inClause2) )
			{$addSql}
			-- keep-cache";

			$linkIDs = array_merge( $linkIDs, $linkIDs );
		} else {
			$sql = "
			SELECT
				{$destTable}.* {$sqlFilterStr},
				{$linkTable}.{$sourceCol} AS linked_by
			FROM {$linkTable}
			INNER JOIN {$destTable} ON
			( {$destTable}.id = {$linkTable}.{$destCol} AND {$linkTable}.{$sourceCol} IN ($inClause) )
			{$addSql}
			-- keep-cache";
		}

		$bindings = array_merge( $linkIDs, $bindings );

		$rows = $this->adapter->get( $sql, $bindings );

		$this->putResultInCache( $destType, $key, $rows );

		return $rows;
	}

	/**
	 * @see QueryWriter::queryRecordLink
	 */
	public function queryRecordLink( $sourceType, $destType, $sourceID, $destID )
	{
		list( $sourceTable, $destTable, $linkTable, $sourceCol, $destCol ) = $this->getRelationalTablesAndColumns( $sourceType, $destType );

		$key = $this->getCacheKey( [ $sourceType, $destType, $sourceID, $destID ] );

		if ( $this->flagUseCache && $cached = $this->getCached( $linkTable, $key ) ) {
			return $cached;
		}

		$sqlFilterStr = '';
		if ( count( self::$sqlFilters ) ) {
			$sqlFilterStr = $this->getSQLFilterSnippet( $destType );
		}

		if ( $sourceTable === $destTable ) {
			$sql = "SELECT {$linkTable}.* {$sqlFilterStr} FROM {$linkTable}
				WHERE ( {$sourceCol} = ? AND {$destCol} = ? ) OR
				 ( {$destCol} = ? AND {$sourceCol} = ? ) -- keep-cache";
			$row = $this->adapter->getRow( $sql, [ $sourceID, $destID, $sourceID, $destID ] );
		} else {
			$sql = "SELECT {$linkTable}.* {$sqlFilterStr} FROM {$linkTable}
				WHERE {$sourceCol} = ? AND {$destCol} = ? -- keep-cache";
			$row = $this->adapter->getRow( $sql, [ $sourceID, $destID ] );
		}

		$this->putResultInCache( $linkTable, $key, $row );

		return $row;
	}

	/**
	* @see QueryWriter::queryTagged
	*/
	public function queryTagged( $type, $tagList, $all = FALSE, $addSql = '', $bindings = array() )
	{
		$assocType = $this->getAssocTable( array( $type, 'tag' ) );
		$assocTable = $this->safeTable( $assocType );
		$assocField = $type . '_id';
		$table = $this->safeTable( $type );
		$slots = implode( ',', array_fill( 0, count( $tagList ), '?' ) );
		$score = ( $all ) ? count( $tagList ) : 1;
		$sql = "
			SELECT {$table}.*, count({$table}.id) FROM {$table}
			INNER JOIN {$assocTable} ON {$assocField} = {$table}.id
			INNER JOIN tag ON {$assocTable}.tag_id = tag.id
			WHERE tag.title IN ({$slots})
			GROUP BY {$table}.id
			HAVING count({$table}.id) >= ?
			{$addSql}
		";
		$bindings = array_merge( $tagList, array( $score ), $bindings );
		$rows = $this->adapter->get( $sql, $bindings );
		return $rows;
	}

	/**
	 * @see QueryWriter::queryRecordCount
	 */
	public function queryRecordCount( $type, $conditions = [], $addSql = NULL, $bindings = [] )
	{
		$addSql = $this->glueSQLCondition( $addSql );

		$table  = $this->safeTable( $type );

		$this->updateCache(); //check if cache chain has been broken

		$sql    = $this->makeSQLFromConditions( $conditions, $bindings, $addSql );
		$sql    = "SELECT COUNT(*) FROM {$table} {$sql} -- keep-cache";

		return (int) $this->adapter->getCell( $sql, $bindings );
	}

	/**
	 * @see QueryWriter::queryRecordCountRelated
	 */
	public function queryRecordCountRelated( $sourceType, $destType, $linkID, $addSql = '', $bindings = [] )
	{
		list( $sourceTable, $destTable, $linkTable, $sourceCol, $destCol ) = $this->getRelationalTablesAndColumns( $sourceType, $destType );

		$this->updateCache(); //check if cache chain has been broken

		if ( $sourceType === $destType ) {
			$sql = "
			SELECT COUNT(*) FROM {$linkTable}
			INNER JOIN {$destTable} ON
			( {$destTable}.id = {$linkTable}.{$destCol} AND {$linkTable}.{$sourceCol} = ? ) OR
			( {$destTable}.id = {$linkTable}.{$sourceCol} AND {$linkTable}.{$destCol} = ? )
			{$addSql}
			-- keep-cache";

			$bindings = array_merge( [ $linkID, $linkID ], $bindings );
		} else {
			$sql = "
			SELECT COUNT(*) FROM {$linkTable}
			INNER JOIN {$destTable} ON
			( {$destTable}.id = {$linkTable}.{$destCol} AND {$linkTable}.{$sourceCol} = ? )
			{$addSql}
			-- keep-cache";

			$bindings = array_merge( [ $linkID ], $bindings );
		}

		return (int) $this->adapter->getCell( $sql, $bindings );
	}

	/**
	 * @see QueryWriter::deleteRecord
	 */
	public function deleteRecord( $type, $conditions = [], $addSql = NULL, $bindings = [] )
	{
		$addSql = $this->glueSQLCondition( $addSql );

		$table  = $this->safeTable( $type );

		$sql    = $this->makeSQLFromConditions( $conditions, $bindings, $addSql );
		$sql    = "DELETE FROM {$table} {$sql}";

		$this->adapter->exec( $sql, $bindings );
	}

	/**
	 * @see QueryWriter::deleteRelations
	 */
	public function deleteRelations( $sourceType, $destType, $sourceID )
	{
		list( $sourceTable, $destTable, $linkTable, $sourceCol, $destCol ) = $this->getRelationalTablesAndColumns( $sourceType, $destType );

		if ( $sourceTable === $destTable ) {
			$sql = "DELETE FROM {$linkTable}
				WHERE ( {$sourceCol} = ? ) OR
				( {$destCol} = ?  )
			";

			$this->adapter->exec( $sql, [ $sourceID, $sourceID ] );
		} else {
			$sql = "DELETE FROM {$linkTable}
				WHERE {$sourceCol} = ? ";

			$this->adapter->exec( $sql, [ $sourceID ] );
		}
	}

	/**
	 * @see QueryWriter::widenColumn
	 */
	public function _widenColumn( $type, $property, $dataType )
	{
		if ( !isset($this->typeno_sqltype[$dataType]) ) return FALSE;

		$table   = $this->safeTable( $type );
		$column  = $this->safeColumn( $property );

		$newType = $this->typeno_sqltype[$dataType];

		$this->adapter->exec( "ALTER TABLE $table CHANGE $column $column $newType " );

		return TRUE;
	}


	/**
	 * @see QueryWriter::wipe
	 */
	public function wipe( $type )
	{
		$table = $this->safeTable( $type );

		$this->adapter->exec( "TRUNCATE $table " );
	}

	/**
	 * @see QueryWriter::renameAssocTable
	 */
	public function renameAssocTable( $from, $to = NULL )
	{
		self::renameAssociation( $from, $to );
	}

	/**
	 * @see QueryWriter::getAssocTable
	 */
	public function getAssocTable( $types )
	{
		return self::getAssocTableFormat( $types );
	}

	/**
	 * Turns caching on or off. Default: off.
	 * If caching is turned on retrieval queries fired after eachother will
	 * use a result row cache.
	 *
	 * @param boolean
	 */
	public function setUseCache( $yesNo )
	{
		$this->flushCache();

		$this->flagUseCache = (bool) $yesNo;
	}

	/**
	 * Flushes the Query Writer Cache.
	 *
	 * @return void
	 */
	public function flushCache(){
		$this->cache = [];
	}

	public function safeColumn( $column, $noQuotes = FALSE ){
		return $this->esc( $column, $noQuotes );
	}
	public function safeTable( $table, $noQuotes = FALSE ){
		return $this->esc( $this->prefix.$table, $noQuotes );
	}
	public function esc( $dbStructure, $dontQuote = FALSE ){
		$this->check( $dbStructure );
		return ( $dontQuote ) ? $dbStructure : $this->quoteCharacter . $dbStructure . $this->quoteCharacter;
	}
		
	function autoWrapCol($s,$table,$col){
		if($func=$this->database->getTableColumnDef($table,$col,'readCol'))
			$s = $func.'('.$s.')';
		if(isset(self::$sqlFilters[QueryWriter::C_SQLFILTER_READ][$table])&&isset(self::$sqlFilters[QueryWriter::C_SQLFILTER_READ][$table][$col]))
			$s = self::$sqlFilters[QueryWriter::C_SQLFILTER_READ][$table][$col];
		return $s;
	}
	function specialTypeAliasExtract($type,&$superalias=null){
		$type = $this->adaptCase(trim($type));
		$alias = null;
		if(($p=strpos($type,':'))!==false){
			if(isset($type[$p+1])&&$type[$p+1]==':'){
				$superalias = trim(substr($type,$p+2));
				$type = trim(substr($type,0,$p));
			}
			else{
				$alias = trim(substr($type,$p+1));
				$type = trim(substr($type,0,$p));
			}
		}
		return [$type,$alias?$alias:$type];
	}
	
	/**
	* @see QueryWriter::inferFetchType
	*/
	public function inferFetchType( $type, $property ){
		$type = $this->safeTable( $type, TRUE );
		$field = $this->safeColumn( $property, TRUE ) . '_id';
		$keys = $this->getKeyMapForType( $type );
		foreach( $keys as $key ) {
			if ($key['from'] === $field)
				return $key['table'];
		}
		return NULL;
	}
	
	/**
	* This method makes a key for a foreign key description array.
	* This key is a readable string unique for every source table.
	* This uniform key is called the FKDL Foreign Key Description Label.
	* Note that the source table is not part of the FKDL because
	* this key is supposed to be 'per source table'. If you wish to
	* include a source table, prefix the key with 'on_table_<SOURCE>_'.
	*
	* @param string $from the column of the key in the source table
	* @param string $table the table where the key points to
	* @param string $to the target column of the foreign key (mostly just 'id')
	*
	* @return string
	*/
	protected function makeFKLabel($from, $type, $to){
		return "from_{$from}_to_table_{$type}_col_{$to}";
	}
	
	/**
	* @see QueryWriter::getKeyMapForType
	*/
	protected function getKeyMapForType( $type ){
		return [];
	}
	
	/**
	* @see QueryWriter::getForeignKeyForTypeProperty
	*/
	public function getForeignKeyForTypeProperty($type, $property){
		$property = $this->safeColumn( $property, TRUE );
		try {
			$map = $this->getKeyMapForType( $type );
		} catch ( SQLException $e ) {
			return NULL;
		}
		foreach( $map as $key ) {
			if ( $key['from'] === $property )
				return $key;
		}
		return NULL;
	}
	
	/**
	 * @see QueryWriter::addUniqueConstraint
	 */
	public function addUniqueIndex( $type, $properties )
	{
		return $this->addUniqueConstraint( $type, $properties );
	}
}