<?php

namespace Surikat\Model\RedBeanPHP\QueryWriter;

use Surikat\Model\RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;
use Surikat\Model\RedBeanPHP\QueryWriter as QueryWriter;
use Surikat\Model\RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use Surikat\Model\RedBeanPHP\Adapter as Adapter;

use Surikat\Model\RedBeanPHP\Database;
use Surikat\Model\R;
use Surikat\Model\Table;
use Surikat\Model\Query;
use Surikat\Core\Dev;

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
		if ( !is_null( $this->getForeignKeyForTableColumn( $table, $property1 ) ) ) return FALSE;
		$adapter = $this->adapter;
		$fkCode  = 'fk' . md5( $table . $property1 . $property2 );
		$sql1 = "ALTER TABLE \"$table\" ADD CONSTRAINT
			{$fkCode}a FOREIGN KEY ($property1)
			REFERENCES \"$table1\" (id) ON DELETE CASCADE ON UPDATE CASCADE ";
		$sql2 = "ALTER TABLE \"$table\" ADD CONSTRAINT
			{$fkCode}b FOREIGN KEY ($property2)
			REFERENCES \"$table2\" (id) ON DELETE CASCADE ON UPDATE CASCADE ";
		try {
			$adapter->exec( $sql1 );
			$adapter->exec( $sql2 );
			return TRUE;
		} catch (\Exception $e ) {
			return FALSE;
		}
	}

	/**
	 * Constructor
	 *
	 * @param Adapter $adapter Database Adapter
	 */
	public function __construct( Adapter $a, Database $db, $prefix='', $case=true )
	{
		parent::__construct($a,$db,$prefix,$case);
		$this->typeno_sqltype = [
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
		];
		foreach ( $this->typeno_sqltype as $k => $v ) {
			$this->sqltype_typeno[trim( strtolower( $v ) )] = $k;
		}
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
	public function _getTables()
	{
		return $this->adapter->getCol( 'SELECT table_name FROM information_schema.tables WHERE table_schema = ANY( current_schemas( FALSE ) )' );
	}

	/**
	 * @see QueryWriter::createTable
	 */
	public function _createTable( $table )
	{
		$table = $this->safeTable( $table );

		$this->adapter->exec( "CREATE TABLE $table (id SERIAL PRIMARY KEY); " );
	}

	/**
	 * @see QueryWriter::getColumns
	 */
	public function _getColumns( $table )
	{
		$table      = $this->safeTable( $table, TRUE );

		$columnsRaw = $this->adapter->get( "SELECT column_name, data_type FROM information_schema.columns WHERE table_name='$table'" );

		$columns = [];
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
		
		if ( $value === INF ) return self::C_DATATYPE_TEXT;
		
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
			if ( preg_match( '/^\-?(\$|€|¥|£)[\d,\.]+$/', $value ) )
				return PostgreSQL::C_DATATYPE_SPECIAL_MONEY;
		}
		if($this->startsWithZeros($value))
			return self::C_DATATYPE_TEXT;

		if ( $value === NULL || ( $value instanceof NULL ) || $value === TRUE || $value === FALSE)
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
	public function _widenColumn( $type, $column, $datatype )
	{
		$table   = $type;
		$type    = $datatype;

		$table   = $this->safeTable( $table );
		$column  = $this->safeColumn( $column );

		$newtype = $this->typeno_sqltype[$type];

		$this->adapter->exec( "ALTER TABLE $table \n\t ALTER COLUMN $column TYPE $newtype " );
	}

	/**
	 * @see QueryWriter::addUniqueIndex
	 */
	public function addUniqueIndex( $table, $columns )
	{
		$table = $this->safeTable( $table, TRUE );

		sort( $columns ); //else we get multiple indexes due to order-effects

		foreach ( $columns as $k => $v ) {
			$columns[$k] = $this->safeColumn( $v );
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
		$stateMap = [
			'42P01' => QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
			'42703' => QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			'23505' => QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION
		];

		return in_array( ( isset( $stateMap[$state] ) ? $stateMap[$state] : '0' ), $list );
	}

	/**
	 * @see QueryWriter::addIndex
	 */
	public function addIndex( $type, $name, $column )
	{
		$table  = $this->safeTable( $type );

		$name   = preg_replace( '/\W/', '', $name );
		$column = $this->safeColumn( $column );

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
	public function addFK( $table, $targetTable, $field, $targetField, $isDep = FALSE )
	{
		$db = $this->adapter->getCell( 'SELECT current_database()' );
		
		$table = $this->safeTable( $table, TRUE );
		$field = $this->safeColumn( $field, TRUE );
		$targetTable = $this->safeTable( $targetTable, TRUE );
		$targetField = $this->safeColumn( $targetField, TRUE );
		$foreignKeys = $this->getKeyMapForTable( $table );
		foreach( $foreignKeys as $foreignKey ) {
			if ( $foreignKey['from'] === $field ) return FALSE; //return, field has already fk
		}
		try{
			$delRule = ( $isDep ? 'CASCADE' : 'SET NULL' );
			$this->adapter->exec( "ALTER TABLE  {$this->esc($table)}
				ADD FOREIGN KEY ( {$this->esc($field)} ) REFERENCES  {$this->esc($targetTable)} (
				{$this->esc($targetField)}) ON DELETE $delRule ON UPDATE $delRule DEFERRABLE ;" );
			return TRUE;
		} catch (\Exception $e ) {
			return FALSE;
		}
	}

	/**
	 * @see QueryWriter::wipeAll
	 */
	public function _wipeAll()
	{
		$this->adapter->exec( 'SET CONSTRAINTS ALL DEFERRED' );
		foreach ( $this->getTables() as $t ) {
			$t = $this->safeTable( $t );
			$this->adapter->exec( "DROP TABLE IF EXISTS $t CASCADE " );
		}
		$this->adapter->exec( 'SET CONSTRAINTS ALL IMMEDIATE' );
	}
	
	public function _drop($t){
		$this->adapter->exec('SET CONSTRAINTS ALL DEFERRED');
		$t = $this->safeTable($t);
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
		$this->adapter->exec($this->buildColumnFulltextSQL($table, $col, $cols ,$lang));
	}
	function buildColumnFulltextSQL($table, $col, $cols ,$lang=''){
		$agg = $this->agg;
		$aggc = $this->aggCaster;
		$sep = $this->separator;
		$cc = "' '";
		$q = $this->quoteCharacter;
		$id = $this->safeColumn('id');
		$tb = $this->safeTable($table);
		$_tb = $this->esc('_'.$table);
		$groupBy = [];
		$columns = [];
		$tablesJoin = [];
		if($lang)
			$lang = "'$lang',";
		foreach($cols as $select){
			$shareds = [];
			$typeParent = $table;
			$aliasParent = $table;
			$type = '';
			$l = strlen($select);
			$weight = '';
			$relation = null;
			$exist = true;
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
						list($type,$alias) = $this->specialTypeAliasExtract($type,$superalias);
						if($superalias)
							$alias = $superalias.'__'.$alias;
						$joint = $type!=$alias?"{$q}{$this->prefix}$type{$q} as {$q}$alias{$q}":$q.$this->prefix.$alias.$q;
						if($exist=($this->tableExists($type)&&$this->columnExists($type,$typeParent.'_id')))
							$tablesJoin[] = "LEFT JOIN $joint ON {$q}{$this->prefix}$aliasParent{$q}.{$q}id{$q}={$q}{$this->prefix}$alias{$q}.{$q}{$typeParent}_id{$q}";
						$typeParent = $type;
						$aliasParent = $alias;
						$type = '';
						$relation = '>';
					break;
					case '<':
						list($type,$alias) = $this->specialTypeAliasExtract($type,$superalias);
						if(isset($select[$i+1])&&$select[$i+1]=='>'){ //shared
							$i++;
							if($superalias)
								$alias = $superalias.'__'.($alias?$alias:$type);
							$rels = [$typeParent,$type];
							sort($rels);
							$imp = implode('_',$rels);
							$join[$imp][] = $alias;
							if($exist=($this->tableExists($type)&&$this->tableExists($imp))){
								$tablesJoin[] = "LEFT JOIN $q{$this->prefix}$imp$q ON {$q}{$this->prefix}$typeParent{$q}.{$q}id{$q}={$q}{$this->prefix}$imp{$q}.{$q}{$typeParent}_id{$q}";
								$joint = $type!=$alias?"{$q}{$this->prefix}$type{$q} as {$q}$alias{$q}":$q.$this->prefix.$alias.$q;
								$tablesJoin[] = "LEFT JOIN $joint ON {$q}{$this->prefix}$alias{$q}.{$q}id{$q}={$q}{$this->prefix}$imp{$q}.{$q}{$type}".(in_array($type,$shareds)?2:'')."_id{$q}";
							}
							$shareds[] = $type;
							$typeParent = $type;
							$relation = '<>';
						}
						else{ //parent
							if($superalias)
								$alias = $superalias.'__'.$alias;
							$join[$type][] = ($alias?[$typeParent,$alias]:$typeParent);
							$joint = $type!=$alias?"{$q}{$this->prefix}$type{$q} as {$q}$alias{$q}":$q.$this->prefix.$alias.$q;
							if($exist=($this->tableExists($typeParent)&&$this->columnExists($typeParent,$type.'_id')))
								$tablesJoin[] = "LEFT JOIN $joint ON {$q}{$this->prefix}$alias{$q}.{$q}id{$q}={$q}{$this->prefix}$typeParent{$q}.{$q}{$type}_id{$q}";
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
			$writer = $this->database->getWriter();
			if($this->tableExists($typeParent)){
				$localTable = $typeParent;
				$localCol = trim($type);
				switch($relation){
					default:
						$c = 'COALESCE('.$writer->autoWrapCol($q.$this->prefix.$localTable.$q.'.'.$q.$localCol.$q,$localTable,$localCol).",''{$aggc})";
						$gb = $q.$this->prefix.$localTable.$q.'.'.$q.$localCol.$q;
						if(!in_array($gb,$groupBy))
							$groupBy[] = $gb;
					break;
					case '<':
						$c = 'COALESCE('.$writer->autoWrapCol($q.$this->prefix.$localTable.$q.'.'.$q.$localCol.$q,$localTable,$localCol).",''{$aggc})";
						$gb = $q.$this->prefix.$localTable.$q.'.'.$q.$localCol.$q;
						if($this->columnExists($localTable,$localCol.'_id')){
							if(!in_array($gb,$groupBy))
								$groupBy[] = $gb;
							$gb = $q.$this->prefix.$localTable.$q.'.'.$q.'id'.$q;
							if(!in_array($gb,$groupBy))
								$groupBy[] = $gb;
						}
					break;
					case '>':
						$c = "{$agg}(COALESCE(".$writer->autoWrapCol("{$q}{$this->prefix}{$localTable}{$q}.{$q}{$localCol}{$q}",$localTable,$localCol)."{$aggc},''{$aggc}) {$sep} {$cc})";
					break;
					case '<>':
						$c = "{$agg}(COALESCE(".$writer->autoWrapCol("{$q}{$this->prefix}{$localTable}{$q}.{$q}{$localCol}{$q}",$localTable,$localCol)."{$aggc},''{$aggc}) {$sep} {$cc})";
					break;
				}
				$c = "to_tsvector($lang$c)";
				if($weight)
					$c = "setweight($c,'$weight')";
				if($exist)
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
		$col  = $this->safeColumn($col);
		$table  = $this->safeTable( $table );
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

	private static $FulltextHeadlineDefaultConfig = [
		'MaxFragments'=>2,
		'MaxWords'=>25,
		'MinWords'=>20,
		'ShortWord'=>3,
		'FragmentDelimiter'=>' ... ',
		'StartSel'=>'<b>',
		'StopSel'=>'</b>',
		'HighlightAll'=>'FALSE',
	];
	function getFulltextHeadlineDefaultConfig(){
		return self::$FulltextHeadlineDefaultConfig;
	}
	function setFulltextHeadlineDefaultConfig($config){
		if(func_num_args()>1)
			$config = [$config=>func_get_arg(1)];
		self::$FulltextHeadlineDefaultConfig = array_merge(self::$FulltextHeadlineDefaultConfig,$config);
	}

	function orderByFullTextRank($query,$col,$t,$lang=null,$alias=null){
		if($t){
			$c = $query->formatColumnName($col);
			if($lang)
				$lang = "'$lang',";
			$query->orderBy("ts_rank({$c}, plainto_tsquery({$lang}?))",$t);
		}
	}
	function selectFullTextRank($query,$col,$t,$lang=null,$alias=null){
		if($t){
			$c = $this->formatColumnName($col);
			if($lang)
				$lang = "'$lang',";
			if(!$alias)
				$alias = $col.'_rank';
			$query->select("ts_rank({$c}, plainto_tsquery({$lang}?)) as $alias",$t);
		}
	}
	function selectFullTextHighlite($query,$col,$t,$truncation=369,$lang=null,$config=[],$getl=true){
		if(!$t)
			return $query->selectTruncation($col,$truncation,$getl);
		if($lang)
			$lang = "'$lang',";
		$config = array_merge($this->getFulltextHeadlineDefaultConfig(),$config);
		$conf = '';
		foreach($config as $k=>$v){
			if($k=='FragmentDelimiter')
				$conf .= $k.'="'.$v.'",';
			else
				$conf .= $k.'='.$v.',';
		}
		$conf = rtrim($conf,',');
		$c = $query->formatColumnName($col);
		$q = $this->quoteCharacter;
		$query->select("SUBSTRING(ts_headline({$lang}$c,plainto_tsquery($lang?),?),1,?) as $q$col$q",$t,$conf,$truncation);
		if($getl)
			$query->select("LENGTH($c) as $q{$col}_length$q");
	}
	function selectFullTextHighlight($query,$col,$t,$lang=null,$config=[]){
		if(!$t)
			return $query->select($col);
		$c = $query->formatColumnName($col);
		$lang = $lang?"'$lang',":'';
		$config = array_merge($this->getFulltextHeadlineDefaultConfig(),$config);
		$conf = '';
		foreach($config as $k=>$v){
			if($k=='FragmentDelimiter')
				$conf .= $k.'="'.$v.'",';
			else
				$conf .= $k.'='.$v.',';
		}
		$q = $this->quoteCharacter;
		$conf = rtrim($conf,',');
		$query->select("ts_headline($col,plainto_tsquery($lang?),?) as $q$col$q",$t,$conf);
	}
	function whereFullText($query,$cols,$t,$lang=null,$toVector=null){
		if(!is_array($cols))
			$cols = (array)$cols;
		foreach(array_keys($cols) as $k){
			$cols[$k] = $query->formatColumnName($cols[$k]);
			if($toVector)
				$cols[$k] = 'to_tsvector('.$cols[$k].')';
		}
		if($lang)
			$lang = "'$lang',";
		$query->where(implode('||',$cols).' @@ plainto_tsquery('.$lang.'?)',$t);
	}
	
	/**
	 * @see QueryWriter::getKeyMapForTable
	 */
	public function getKeyMapForTable( $type )
	{
		$table = $this->safeTable( $type, TRUE );
		$keys = $this->adapter->get( '
			SELECT 
			information_schema.key_column_usage.constraint_name AS "name",
			information_schema.key_column_usage.column_name AS "from",
			information_schema.constraint_table_usage.table_name AS "table",
			information_schema.constraint_column_usage.column_name AS "to",
			information_schema.referential_constraints.update_rule AS "on_update",
			information_schema.referential_constraints.delete_rule AS "on_delete"
				FROM information_schema.key_column_usage
			INNER JOIN information_schema.constraint_table_usage
				ON (
					information_schema.key_column_usage.constraint_name = information_schema.constraint_table_usage.constraint_name
					AND information_schema.key_column_usage.constraint_schema = information_schema.constraint_table_usage.constraint_schema
					AND information_schema.key_column_usage.constraint_catalog = information_schema.constraint_table_usage.constraint_catalog 
				)
			INNER JOIN information_schema.constraint_column_usage
				ON (
					information_schema.key_column_usage.constraint_name = information_schema.constraint_column_usage.constraint_name
					AND information_schema.key_column_usage.constraint_schema = information_schema.constraint_column_usage.constraint_schema
					AND information_schema.key_column_usage.constraint_catalog = information_schema.constraint_column_usage.constraint_catalog 
				)
			INNER JOIN information_schema.referential_constraints
				ON (
					information_schema.key_column_usage.constraint_name = information_schema.referential_constraints.constraint_name
					AND information_schema.key_column_usage.constraint_schema = information_schema.referential_constraints.constraint_schema
					AND information_schema.key_column_usage.constraint_catalog = information_schema.referential_constraints.constraint_catalog 
				)
			WHERE
				information_schema.key_column_usage.table_catalog = current_database()
				AND information_schema.key_column_usage.table_schema = ANY( current_schemas( FALSE ) )
				AND information_schema.key_column_usage.table_name = ?
		', [$table] );
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
}