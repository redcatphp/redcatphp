<?php
namespace RedBase\DataSource;
class Sqlite extends SQL{
	const C_DATATYPE_INTEGER   = 0;
	const C_DATATYPE_NUMERIC   = 1;
	const C_DATATYPE_TEXT      = 2;
	const C_DATATYPE_SPECIFIED = 99;
	protected $quoteCharacter = '`';
	function construct(array $config=[]){
		parent::construct($config);
		$this->typeno_sqltype = [
			self::C_DATATYPE_INTEGER => 'INTEGER',
			self::C_DATATYPE_NUMERIC => 'NUMERIC',
			self::C_DATATYPE_TEXT    => 'TEXT',
		];
		foreach ( $this->typeno_sqltype as $k => $v )
			$this->sqltype_typeno[$v] = $k;
	}
	function createDatabase($dbname){}
	function scanType( $value, $flagSpecial = FALSE ){
		if ( $value === NULL ) return self::C_DATATYPE_INTEGER;
		if ( $value === INF ) return self::C_DATATYPE_TEXT;

		if ( self::startsWithZeros( $value ) ) return self::C_DATATYPE_TEXT;

		if ( $value === TRUE || $value === FALSE )  return self::C_DATATYPE_INTEGER;
		
		if ( is_numeric( $value ) && ( intval( $value ) == $value ) && $value < 2147483648 && $value > -2147483648 ) return self::C_DATATYPE_INTEGER;

		if ( ( is_numeric( $value ) && $value < 2147483648 && $value > -2147483648)
			|| preg_match( '/\d{4}\-\d\d\-\d\d/', $value )
			|| preg_match( '/\d{4}\-\d\d\-\d\d\s\d\d:\d\d:\d\d/', $value )
		) {
			return self::C_DATATYPE_NUMERIC;
		}
		return self::C_DATATYPE_TEXT;
	}
	function getTablesQuery(){
		return $this->getCol("SELECT name FROM sqlite_master WHERE type='table' AND name!='sqlite_sequence';");
	}
	function getColumnsQuery($table){
		$table      = $this->prefixTable($table);
		$columnsRaw = $this->getAll("PRAGMA table_info('$table')");
		$columns    = [];
		foreach($columnsRaw as $r)
			$columns[$r['name']] = $r['type'];
		return $columns;
	}
	function createTableQuery($table,$pk='id'){
		$table = $this->escTable($table);
		$this->execute('CREATE TABLE '.$table.' ( '.$pk.' INTEGER PRIMARY KEY AUTOINCREMENT ) ');
	}
	function addColumnQuery($table, $column, $type){
		$column = $this->check($column);
		$table  = $this->check($table);
		$type   = $this->typeno_sqltype[$type];
		$this->execute('ALTER TABLE `'.$table.'` ADD `'.$column.'` '.$type);
	}
	function changeColumnQuery($type, $column, $datatype){
		$t = $this->getTable( $type );
		$t['columns'][$column] = $this->typeno_sqltype[$datatype];
		$this->putTable($t);
	}
	
	/**
	 * Puts a table. Updates the table structure.
	 * In SQLite we can't change columns, drop columns, change or add foreign keys so we
	 * have a table-rebuild function. You simply load your table with getTable(), modify it and
	 * then store it with putTable()...
	 *
	 * @param array $tableMap information array
	 */
	protected function putTable( $tableMap ){
		$table = $tableMap['name'];
		$q     = [];
		$q[]   = "DROP TABLE IF EXISTS tmp_backup;";
		$oldColumnNames = array_keys( $this->getColumns( $table ) );
		foreach($oldColumnNames as $k => $v)
			$oldColumnNames[$k] = "`$v`";
		$q[] = "CREATE TEMPORARY TABLE tmp_backup(" . implode( ",", $oldColumnNames ) . ");";
		$q[] = "INSERT INTO tmp_backup SELECT * FROM `$table`;";
		$q[] = "PRAGMA foreign_keys = 0 ";
		$q[] = "DROP TABLE `$table`;";
		$newTableDefStr = '';
		foreach ( $tableMap['columns'] as $column => $type ) {
			if ( $column != 'id' ) {
				$newTableDefStr .= ",`$column` $type";
			}
		}
		$fkDef = '';
		foreach ( $tableMap['keys'] as $key ) {
			$fkDef .= ", FOREIGN KEY(`{$key['from']}`)
						 REFERENCES `{$key['table']}`(`{$key['to']}`)
						 ON DELETE {$key['on_delete']} ON UPDATE {$key['on_update']}";
		}
		$q[] = "CREATE TABLE `$table` ( `id` INTEGER PRIMARY KEY AUTOINCREMENT  $newTableDefStr  $fkDef );";
		foreach ( $tableMap['indexes'] as $name => $index ) {
			if ( strpos( $name, 'UQ_' ) === 0 ) {
				$cols = explode( '__', substr( $name, strlen( 'UQ_' . $table ) ) );
				foreach ( $cols as $k => $v )
					$cols[$k] = "`$v`";
				$q[] = "CREATE UNIQUE INDEX $name ON `$table` (" . implode( ',', $cols ) . ")";
			}
				else $q[] = "CREATE INDEX $name ON `$table` ({$index['name']}) ";
		}
		$q[] = "INSERT INTO `$table` SELECT * FROM tmp_backup;";
		$q[] = "DROP TABLE tmp_backup;";
		$q[] = "PRAGMA foreign_keys = 1 ";
		foreach ( $q as $sq ) $this->execute( $sq );
	}
	function getTable( $type ){
		$tableName = $this->prefixTable($type);
		$columns   = $this->getColumns($type);
		$indexes   = $this->getIndexes($type);
		$keys      = $this->getKeyMapForType($type);
		$table = [
			'columns' => $columns,
			'indexes' => $indexes,
			'keys' => $keys,
			'name' => $tableName
		];
		return $table;
	}
	function getIndexes( $type ){
		$table   = $this->prefixTable( $type );
		$indexes = $this->getAll( "PRAGMA index_list('$table')" );
		$indexInfoList = [];
		foreach ( $indexes as $i ) {
			$indexInfoList[$i['name']] = $this->getRow( "PRAGMA index_info('{$i['name']}') " );
			$indexInfoList[$i['name']]['unique'] = $i['unique'];
		}
		return $indexInfoList;
	}	
	function getKeyMapForType($type){
		$table = $this->prefixTable( $type );
		$keys  = $this->getAll( "PRAGMA foreign_key_list('$table')" );
		$keyInfoList = [];
		foreach ( $keys as $k ) {
			$label = self::makeFKLabel( $k['from'], $k['table'], $k['to'] );
			$keyInfoList[$label] = array(
				'name'          => $label,
				'from'          => $k['from'],
				'table'         => $k['table'],
				'to'            => $k['to'],
				'on_update'     => $k['on_update'],
				'on_delete'     => $k['on_delete']
			);
		}
		return $keyInfoList;
	}
	/**
	 * Adds a foreign key to a type
	 *
	 * @param  string  $type        type you want to modify table of
	 * @param  string  $targetType  target type
	 * @param  string  $field       field of the type that needs to get the fk
	 * @param  string  $targetField field where the fk needs to point to
	 * @param  integer $buildopt    0 = NO ACTION, 1 = ON DELETE CASCADE
	 *
	 * @return boolean $didIt
	 *
	 * @note: cant put this in try-catch because that can hide the fact
	 *      that database has been damaged.
	 */
	function addFK( $type, $targetType, $property, $targetProperty, $constraint = false ){
		$table           = $this->prefixTable( $type );
		$targetTable     = $this->prefixTable( $targetType );
		$column          = $this->check( $property );
		$targetColumn    = $this->check( $targetProperty );

		$tables = $this->getTables();
		if ( !in_array( $targetTable, $tables ) )
			return false;
		
		$consSQL = $constraint ? 'CASCADE' : 'SET NULL';
		$fk = $this->getForeignKeyForTypeProperty( $table, $column );
		if ( !is_null( $fk )
			&&($fk['on_update']==$consSQL||$fk['on_update']=='CASCADE')
			&&($fk['on_delete']==$consSQL||$fk['on_update']=='CASCADE')
		)
			return false;
		$t = $this->getTable( $table );
		$label   = 'from_' . $column . '_to_table_' . $targetTable . '_col_' . $targetColumn;
		$t['keys'][$label] = array(
			'table'     => $targetTable,
			'from'      => $column,
			'to'        => $targetColumn,
			'on_update' => $consSQL,
			'on_delete' => $consSQL
		);
		$this->putTable( $t );
		return true;
	}
	function columnCode( $typedescription, $includeSpecials = FALSE ){
		return  ( isset( $this->sqltype_typeno[$typedescription] ) ) ? $this->sqltype_typeno[$typedescription]:99;
	}
	function getTypeForID(){
		return self::C_DATATYPE_INTEGER;
	}
	function addUniqueConstraint( $type, $properties ){
		$tableNoQ = $this->prefixTable( $type );
		$name  = 'UQ_' . $this->prefixTable( $type ) . implode( '__', (array)$properties );
		$t     = $this->getTable( $type );
		if(isset($t['indexes'][$name]))
			return true;
		$t['indexes'][$name] = [ 'name' => $name ];
		try {
			$this->putTable( $t );
		} catch( \PDOException $e ) {
			return false;
		}
		return true;
	}
	function addIndex( $type, $name, $column ){
		$columns = $this->getColumns( $type );
		if ( !isset( $columns[$column] ) )
			return false;
		$table  = $this->escTable( $type );
		$name   = preg_replace( '/\W/', '', $name );
		$column = $this->check( $column );
		try {
			$t = $this->getTable( $type );
			$t['indexes'][$name] = [ 'name' => $column ];
			$this->putTable($t);
			return true;
		} catch( \PDOException $exception ) {
			return false;
		}
	}
	
	function clear($type){
		$table = $this->escTable($type);
		$this->execute('DELETE FROM '.$table);
	}
	protected function _drop($type){
		$t = $this->escTable($type);
		$this->execute('PRAGMA foreign_keys = 0 ');
		try {
			$this->execute('DROP TABLE IF EXISTS '.$t);
		}
		catch (\PDOException $e ) {}
		$this->execute('PRAGMA foreign_keys = 1');
	}
	protected function _dropAll(){
		$this->execute('PRAGMA foreign_keys = 0');
		foreach($this->getTables() as $t){
			try{
				$this->execute("DROP TABLE IF EXISTS `$t`");
			}
			catch(\PDOException $e){}
		}
		$this->execute('PRAGMA foreign_keys = 1 ');
	}
	
	protected function explain($sql,$bindings=[]){
		$sql = ltrim($sql);
		if(!in_array(strtoupper(substr($sql,0,6)),['SELECT','DELETE','INSERT','UPDATE']))
			return false;
		$explain = $this->pdo->prepare('EXPLAIN QUERY PLAN '.$sql);
		$this->bindParams($explain,$bindings);
		$explain->execute();
		$explain = $explain->fetchAll();
		$i = 0;
		return implode("\n",array_map(function($entry)use(&$i){
			$i++;
			return str_repeat('  ',$i-1).implode('|',$entry);;
		}, $explain));
	}
	
	/*
	function fulltext($search){
		list($select,$from,$where,$orderBy) = $this->fulltextQueryParts($search);
		$this->select($select);
		$this->from($from);
		$this->where($where);
		$this->orderBy($orderBy);
	}
	function fulltextQueryParts($search){
		
		
		return [$select,$from,$where,$orderBy];
	}
	*/
}