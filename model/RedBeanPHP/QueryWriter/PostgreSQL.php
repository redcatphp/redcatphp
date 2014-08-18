<?php

namespace surikat\model\RedBeanPHP\QueryWriter;

use surikat\model\RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;
use surikat\model\RedBeanPHP\QueryWriter as QueryWriter;
use surikat\model\RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use surikat\model\RedBeanPHP\Adapter as Adapter;

use surikat\model\RedBeanPHP\QueryWriter\XQueryWriter;
use surikat\model\R;
use surikat\model\Table;
use surikat\model\Query;
use surikat\control;

use surikat\control\str;

/**
 * RedBean PostgreSQL Query Writer
 *
 * @file    RedBean/QueryWriter/PostgreSQL.php
 * @desc    QueryWriter for the PostgreSQL database system.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class PostgreSQL extends AQueryWriter implements QueryWriter
{
	use XQueryWriter;
	
	/**
	 * Data types
	 */
	const C_DATATYPE_INTEGER          = 1;
	const C_DATATYPE_BIGINT           = 2;
	const C_DATATYPE_DOUBLE           = 3;
	const C_DATATYPE_TEXT             = 4;
	const C_DATATYPE_FULLTEXT         = 20;
	const C_DATATYPE_SPECIAL_DATE     = 80;
	const C_DATATYPE_SPECIAL_DATETIME = 81;
	//const C_DATATYPE_SPECIAL_BOOL     = 85;
	const C_DATATYPE_SPECIAL_POINT    = 90;
	const C_DATATYPE_SPECIAL_LSEG     = 91;
	const C_DATATYPE_SPECIAL_CIRCLE   = 92;
	const C_DATATYPE_SPECIAL_MONEY    = 93;
	const C_DATATYPE_SPECIAL_POLYGON  = 94;
	const C_DATATYPE_SPECIFIED        = 99;

	/**
	 * @var DBAdapter
	 */
	protected $adapter;

	/**
	 * @var string
	 */
	protected $quoteCharacter = '"';

	/**
	 * @var string
	 */
	protected $defaultValue = 'DEFAULT';

	/**
	 * Returns the insert suffix SQL Snippet
	 *
	 * @param string $table table
	 *
	 * @return  string $sql SQL Snippet
	 */
	protected function getInsertSuffix( $table )
	{
		return 'RETURNING id ';
	}

	/**
	 * Add the constraints for a specific database driver: PostgreSQL.
	 *
	 * @param string $table     table to add fk constraints to
	 * @param string $table1    first reference table
	 * @param string $table2    second reference table
	 * @param string $property1 first reference column
	 * @param string $property2 second reference column
	 *
	 * @return boolean
	 */
	protected function constrain( $table, $table1, $table2, $property1, $property2 )
	{
		try {
			$adapter = $this->adapter;

			$fkCode  = 'fk' . md5( $table . $property1 . $property2 );

			$sql = "SELECT c.oid, n.nspname, c.relname,
				n2.nspname, c2.relname, cons.conname
				FROM pg_class c
				JOIN pg_namespace n ON n.oid = c.relnamespace
				LEFT OUTER JOIN pg_constraint cons ON cons.conrelid = c.oid
				LEFT OUTER JOIN pg_class c2 ON cons.confrelid = c2.oid
				LEFT OUTER JOIN pg_namespace n2 ON n2.oid = c2.relnamespace
				WHERE c.relkind = 'r'
					AND n.nspname IN ('public')
					AND (cons.contype = 'f' OR cons.contype IS NULL)
					AND (  cons.conname = '{$fkCode}a'	OR  cons.conname = '{$fkCode}b' )
			";

			$rows    = $adapter->get( $sql );
			if ( !count( $rows ) ) {
				$sql1 = "ALTER TABLE \"$table\" ADD CONSTRAINT
					{$fkCode}a FOREIGN KEY ($property1)
					REFERENCES \"$table1\" (id) ON DELETE CASCADE ON UPDATE CASCADE ";

				$sql2 = "ALTER TABLE \"$table\" ADD CONSTRAINT
					{$fkCode}b FOREIGN KEY ($property2)
					REFERENCES \"$table2\" (id) ON DELETE CASCADE ON UPDATE CASCADE ";

				$adapter->exec( $sql1 );

				$adapter->exec( $sql2 );
				return TRUE;
			}

			return FALSE;
		} catch (\Exception $e ) {
			return FALSE;
		}
	}

	/**
	 * Constructor
	 *
	 * @param Adapter $adapter Database Adapter
	 */
	public function __construct( Adapter $adapter, $prefix=false )
	{
		$this->prefix = $prefix;
		$this->typeno_sqltype = array(
			self::C_DATATYPE_INTEGER          => 'integer',
			self::C_DATATYPE_BIGINT           => 'bigint',
			self::C_DATATYPE_DOUBLE           => 'double precision',
			self::C_DATATYPE_TEXT             => 'text',
			self::C_DATATYPE_FULLTEXT		  => 'tsvector',
			//self::C_DATATYPE_SPECIAL_BOOL     => 'boolean',
			self::C_DATATYPE_SPECIAL_DATE     => 'date',
			self::C_DATATYPE_SPECIAL_DATETIME => 'timestamp without time zone',
			self::C_DATATYPE_SPECIAL_POINT    => 'point',
			self::C_DATATYPE_SPECIAL_LSEG     => 'lseg',
			self::C_DATATYPE_SPECIAL_CIRCLE   => 'circle',
			self::C_DATATYPE_SPECIAL_MONEY    => 'money',
			self::C_DATATYPE_SPECIAL_POLYGON  => 'polygon',
		);

		$this->sqltype_typeno = array();

		foreach ( $this->typeno_sqltype as $k => $v ) {
			$this->sqltype_typeno[trim( strtolower( $v ) )] = $k;
		}

		$this->adapter = $adapter;
	}

	/**
	 * This method returns the datatype to be used for primary key IDS and
	 * foreign keys. Returns one if the data type constants.
	 *
	 * @return integer $const data type to be used for IDS.
	 */
	public function getTypeForID()
	{
		//return self::C_DATATYPE_INTEGER;
		return self::C_DATATYPE_BIGINT;
	}

	/**
	 * @see QueryWriter::getTables
	 */
	public function getTables()
	{
		return $this->adapter->getCol( "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'" );
	}

	/**
	 * @see QueryWriter::createTable
	 */
	public function createTable( $table )
	{
		$table = $this->esc( $table );

		$this->adapter->exec( " CREATE TABLE $table (id SERIAL PRIMARY KEY); " );
	}

	/**
	 * @see QueryWriter::getColumns
	 */
	public function getColumns( $table )
	{
		$table      = $this->esc( $table, TRUE );

		$columnsRaw = $this->adapter->get( "SELECT column_name, data_type FROM information_schema.columns WHERE table_name='$table'" );

		$columns = array();
		foreach ( $columnsRaw as $r ) {
			$columns[$r['column_name']] = trim($r['data_type']);
		}

		return $columns;
	}

	/**
	 * @see QueryWriter::scanType
	 */
	public function scanType( $value, $flagSpecial = FALSE ){
		$this->svalue = $value;
		if( $flagSpecial && $value ) {
			//if(is_bool($value)||$value==='f'||||$value==='t')
				//return self::C_DATATYPE_SPECIAL_BOOL;
			if ( preg_match( '/^\d{4}\-\d\d-\d\d$/', $value ) )
				return PostgreSQL::C_DATATYPE_SPECIAL_DATE;
			if ( preg_match( '/^\d{4}\-\d\d-\d\d\s\d\d:\d\d:\d\d(\.\d{1,6})?$/', $value ) )
				return PostgreSQL::C_DATATYPE_SPECIAL_DATETIME;
			if ( preg_match( '/^\([\d\.]+,[\d\.]+\)$/', $value ) )
				return PostgreSQL::C_DATATYPE_SPECIAL_POINT;
			if ( preg_match( '/^\[\([\d\.]+,[\d\.]+\),\([\d\.]+,[\d\.]+\)\]$/', $value ) )
				return PostgreSQL::C_DATATYPE_SPECIAL_LSEG;
			if ( preg_match( '/^\<\([\d\.]+,[\d\.]+\),[\d\.]+\>$/', $value ) )
				return PostgreSQL::C_DATATYPE_SPECIAL_CIRCLE;

			if ( preg_match( '/^\((\([\d\.]+,[\d\.]+\),?)+\)$/', $value ) )
				return PostgreSQL::C_DATATYPE_SPECIAL_POLYGON;
			if ( preg_match( '/^\-?\$[\d,\.]+$/', $value ) )
				return PostgreSQL::C_DATATYPE_SPECIAL_MONEY;
		}
		if($this->startsWithZeros($value))
			return self::C_DATATYPE_TEXT;

		if ( $value === NULL || ( $value instanceof NULL ))
			return self::C_DATATYPE_INTEGER;
		
		if ( is_numeric( $value )){
			if(floor( $value )==$value){
				if($value < 2147483648 && $value > -2147483648)
					return self::C_DATATYPE_INTEGER;
				return self::C_DATATYPE_BIGINT;
			}
			return self::C_DATATYPE_DOUBLE;
		}
		return self::C_DATATYPE_TEXT;
	}

	/**
	 * @see QueryWriter::code
	 */
	public function code( $typedescription, $includeSpecials = FALSE )
	{

		$r = ( isset( $this->sqltype_typeno[$typedescription] ) ) ? $this->sqltype_typeno[$typedescription] : 99;

		if ( $includeSpecials ) return $r;

		if ( $r >= QueryWriter::C_DATATYPE_RANGE_SPECIAL ) {
			return self::C_DATATYPE_SPECIFIED;
		}

		return $r;
	}

	/**
	 * @see QueryWriter::widenColumn
	 */
	public function widenColumn( $type, $column, $datatype )
	{
		$table   = $type;
		$type    = $datatype;

		$table   = $this->esc( $table );
		$column  = $this->esc( $column );

		$newtype = $this->typeno_sqltype[$type];

		$this->adapter->exec( "ALTER TABLE $table \n\t ALTER COLUMN $column TYPE $newtype " );
	}

	/**
	 * @see QueryWriter::addUniqueIndex
	 */
	public function addUniqueIndex( $table, $columns )
	{
		$table = $this->esc( $table, TRUE );

		sort( $columns ); //else we get multiple indexes due to order-effects

		foreach ( $columns as $k => $v ) {
			$columns[$k] = $this->esc( $v );
		}

		$r = $this->adapter->get( "SELECT i.relname AS index_name
			FROM pg_class t,pg_class i,pg_index ix,pg_attribute a
			WHERE t.oid = ix.indrelid
				AND i.oid = ix.indexrelid
				AND a.attrelid = t.oid
				AND a.attnum = ANY(ix.indkey)
				AND t.relkind = 'r'
				AND t.relname = '$table'
			ORDER BY t.relname, i.relname;" );

		$name = "UQ_" . sha1( $table . implode( ',', $columns ) );

		if ( $r ) {
			foreach ( $r as $i ) {
				if ( strtolower( $i['index_name'] ) == strtolower( $name ) ) {
					return;
				}
			}
		}

		$sql = "ALTER TABLE \"$table\"
                ADD CONSTRAINT $name UNIQUE (" . implode( ',', $columns ) . ")";

		$this->adapter->exec( $sql );
	}

	/**
	 * @see QueryWriter::sqlStateIn
	 */
	public function sqlStateIn( $state, $list )
	{
		$stateMap = array(
			'42P01' => QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
			'42703' => QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			'23505' => QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION
		);

		return in_array( ( isset( $stateMap[$state] ) ? $stateMap[$state] : '0' ), $list );
	}

	/**
	 * @see QueryWriter::addIndex
	 */
	public function addIndex( $type, $name, $column )
	{
		$table  = $type;
		$table  = $this->esc( $table );

		$name   = preg_replace( '/\W/', '', $name );
		$column = $this->esc( $column );

		if ( $this->adapter->getCell( "SELECT COUNT(*) FROM pg_class WHERE relname = '$name'" ) ) {
			return;
		}

		try {
			$this->adapter->exec( "CREATE INDEX $name ON $table ($column) " );
		} catch (\Exception $e ) {
			//do nothing
		}
	}

	/**
	 * @see QueryWriter::addFK
	 */
	public function addFK( $type, $targetType, $field, $targetField, $isDep = FALSE )
	{
		$db = $this->adapter->getCell( 'SELECT current_database()' );
		$cfks = $this->adapter->getCell('
			SELECT constraint_name
				FROM information_schema.KEY_COLUMN_USAGE
			WHERE
				table_catalog = ?
				AND table_schema = \'public\'
				AND table_name = ?
				AND column_name = ?
		', array($db, $type, $field));

		try{
			if (!$cfks) {
				$delRule = ( $isDep ? 'CASCADE' : 'SET NULL' );
				$this->adapter->exec( "ALTER TABLE  {$this->esc($type)}
					ADD FOREIGN KEY ( {$this->esc($field)} ) REFERENCES  {$this->esc($targetType)} (
					{$this->esc($targetField)}) ON DELETE $delRule ON UPDATE $delRule DEFERRABLE ;" );
			}
		} catch (\Exception $e ) {
			return FALSE;
		}
	}

	/**
	 * @see QueryWriter::wipeAll
	 */
	public function wipeAll()
	{
		$this->adapter->exec( 'SET CONSTRAINTS ALL DEFERRED' );
		foreach ( $this->getTables() as $t ) {
			$t = $this->esc( $t );
			$this->adapter->exec( "DROP TABLE IF EXISTS $t CASCADE " );
		}
		$this->adapter->exec( 'SET CONSTRAINTS ALL IMMEDIATE' );
	}
	
	public function drop($t){
		$this->adapter->exec('SET CONSTRAINTS ALL DEFERRED');
		$t = $this->esc($t);
		$this->adapter->exec("DROP TABLE IF EXISTS $t CASCADE ");
		$this->adapter->exec('SET CONSTRAINTS ALL IMMEDIATE');
	}

	

	protected $separator = ',';
	protected $agg = 'string_agg'; //as of pgsql 9
	protected $aggCaster = '::text';
	protected $sumCaster = '::int';
	protected $concatenator = 'chr(29)';
	function addColumnFulltext($table, $col){
		$this->addColumn($table,$col,$this->code('tsvector'));
	}
	function buildColumnFulltext($table, $col, $cols ,$lang=''){
		$sqlUpdate = $this->buildColumnFulltextSQL($table, $col, $cols ,$lang);
		if(control::devHas(control::dev_model_sql))
			print(str_replace(',',",\n",$sqlUpdate).'<br>');
		$this->adapter->exec($sqlUpdate);
	}
	function buildColumnFulltextSQL($table, $col, $cols ,$lang=''){
		$agg = $this->agg;
		$aggc = $this->aggCaster;
		$sep = $this->separator;
		$cc = "' '";
		$q = $this->quoteCharacter;
		$id = $this->esc('id');
		$tb = $this->esc($table);
		$_tb = $this->esc('_'.$table);
		$groupBy = array();
		$columns = array();
		$tablesJoin = array();
		if($lang)
			$lang = "'$lang',";
		foreach($cols as $select){
			$shareds = array();
			$typeParent = $table;
			$aliasParent = $table;
			$type = '';
			$l = strlen($select);
			$weight = '';
			$relation = null;
			for($i=0;$i<$l;$i++){
				switch($select[$i]){
					case '/':
						$i++;
						while(isset($select[$i])){
							$weight .= $select[$i];
							$i++;
						}
						$weight = trim($weight);
					break;
					case '.':
					case '>': //own
						list($type,$alias) = Query::typeAliasExtract($type,$superalias);
						if($superalias)
							$alias = $superalias.'__'.$alias;
						$joint = $type!=$alias?"{$q}$type{$q} as {$q}$alias{$q}":$q.$alias.$q;
						if($this->tableExists($type))
							$tablesJoin[] = "LEFT OUTER JOIN $joint ON {$q}$aliasParent{$q}.{$q}id{$q}={$q}$alias{$q}.{$q}{$typeParent}_id{$q}";
						$typeParent = $type;
						$aliasParent = $alias;
						$type = '';
						$relation = '>';
					break;
					case '<':
						list($type,$alias) = Query::typeAliasExtract($type,$superalias);
						if(isset($select[$i+1])&&$select[$i+1]=='>'){ //shared
							$i++;
							if($superalias)
								$alias = $superalias.'__'.($alias?$alias:$type);
							$rels = array($typeParent,$type);
							sort($rels);
							$imp = implode('_',$rels);
							$join[$imp][] = $alias;
							if($this->tableExists($type)&&$this->tableExists($imp)){
								$tablesJoin[] = "LEFT OUTER JOIN $q$imp$q ON {$q}$typeParent{$q}.{$q}id{$q}={$q}$imp{$q}.{$q}{$typeParent}_id{$q}";
								$joint = $type!=$alias?"{$q}$type{$q} as {$q}$alias{$q}":$q.$alias.$q;
								$tablesJoin[] = "LEFT OUTER JOIN $joint ON {$q}$alias{$q}.{$q}id{$q}={$q}$imp{$q}.{$q}{$type}".(in_array($type,$shareds)?2:'')."_id{$q}";
							}
							$shareds[] = $type;
							$typeParent = $type;
							$relation = '<>';
						}
						else{ //parent
							if($superalias)
								$alias = $superalias.'__'.$alias;
							$join[$type][] = ($alias?array($typeParent,$alias):$typeParent);
							$joint = $type!=$alias?"{$q}$type{$q} as {$q}$alias{$q}":$q.$alias.$q;
							if($this->tableExists($type))
								$tablesJoin[] = "LEFT OUTER JOIN $joint ON {$q}$alias{$q}.{$q}id{$q}={$q}$typeParent{$q}.{$q}{$type}_id{$q}";
							$typeParent = $type;
							$relation = '<';
						}
						$type = '';
					break;
					default:
						$type .= $select[$i];
					break;
				}
			}
			if($this->tableExists($typeParent)){
				$localTable = $typeParent;
				$localCol = trim($type);
				switch($relation){
					default:
					case '<':
						$c = 'COALESCE('.Query::autoWrapCol($q.$localTable.$q.'.'.$q.$localCol.$q,$localTable,$localCol).",''{$aggc})";
						$gb = $q.$localTable.$q.'.'.$q.'id'.$q;
						if(!in_array($gb,$groupBy))
							$groupBy[] = $gb;
					break;
					case '>':
						$c = "{$agg}(COALESCE(".Query::autoWrapCol("{$q}{$localTable}{$q}.{$q}{$localCol}{$q}",$localTable,$localCol)."{$aggc},''{$aggc}) {$sep} {$cc})";
					break;
					case '<>':
						$c = "{$agg}(COALESCE(".Query::autoWrapCol("{$q}{$localTable}{$q}.{$q}{$localCol}{$q}",$localTable,$localCol)."{$aggc},''{$aggc}) {$sep} {$cc})";
					break;
				}
				$c = "to_tsvector($lang$c)";
				if($weight)
					$c = "setweight($c,'$weight')";
				$columns[] = $c;
			}
		}
		$sqlUpdate = 'UPDATE '.$tb.' as '.$_tb;
		$sqlUpdate .= ' SET '.$col.'=(SELECT '.implode("||",$columns);
		$sqlUpdate .= ' FROM '.$tb;
		$sqlUpdate .= implode(" \n",$tablesJoin);
		$sqlUpdate .= ' WHERE '.$tb.'.'.$id.'='.$_tb.'.'.$id;
		if(!empty($groupBy))
			$sqlUpdate .= ' GROUP BY '.implode(',',$groupBy);
		$sqlUpdate .= ')';
		return $sqlUpdate;
	}
	function addIndexFullText($table, $col, $name = null, $lang=null ){
		if(!isset($name))
			$name = $table.'_'.$col.'_fulltext';
		$col  = $this->esc($col);
		$table  = $this->esc( $table );
		$name   = preg_replace( '/\W/', '', $name );
		if($this->adapter->getCell( "SELECT COUNT(*) FROM pg_class WHERE relname = '$name'" ))
			return;
		try{
			$this->adapter->exec("CREATE INDEX $name ON $table USING gin($col) ");
			//if($lang)
				//$this->adapter->exec("ALTER TABLE $table ADD language text NOT NULL DEFAULT('$lang');");
		}
		catch (\Exception $e ) {
		}
	}

	private static $FulltextHeadlineDefaultConfig = array(
		'MaxFragments'=>2,
		'MaxWords'=>25,
		'MinWords'=>20,
		'ShortWord'=>3,
		'FragmentDelimiter'=>' ... ',
		'StartSel'=>'<b>',
		'StopSel'=>'</b>',
		'HighlightAll'=>'FALSE',
	);
	function getFulltextHeadlineDefaultConfig(){
		return self::$FulltextHeadlineDefaultConfig;
	}
	function setFulltextHeadlineDefaultConfig($config){
		if(func_num_args()>1)
			$config = array($config=>func_get_arg(1));
		self::$FulltextHeadlineDefaultConfig = array_merge(self::$FulltextHeadlineDefaultConfig,$config);
	}

}