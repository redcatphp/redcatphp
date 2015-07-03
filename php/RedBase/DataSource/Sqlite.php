<?php
namespace RedBase\DataSource;
class Sqlite extends SQL{
	function createDatabase($dbname){
		
	}
	
	const C_DATATYPE_INTEGER   = 0;
	const C_DATATYPE_NUMERIC   = 1;
	const C_DATATYPE_TEXT      = 2;
	const C_DATATYPE_SPECIFIED = 99;
	
	protected $quoteCharacter = '`';
	
	function __construct($pdo,$primaryKey='id',$uniqTextKey='uniq',$frozen=null,$dataSource,$tablePrefix){
		parent::__construct($pdo,$primaryKey,$uniqTextKey,$frozen,$dataSource,$tablePrefix);
		$this->typeno_sqltype = [
			self::C_DATATYPE_INTEGER => 'INTEGER',
			self::C_DATATYPE_NUMERIC => 'NUMERIC',
			self::C_DATATYPE_TEXT    => 'TEXT',
		];
		foreach ( $this->typeno_sqltype as $k => $v )
			$this->sqltype_typeno[$v] = $k;
	}
	
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
	function getTables(){
		return $this->getCol( "SELECT name FROM sqlite_master WHERE type='table' AND name!='sqlite_sequence';" );
	}
	function getColumns($table){
		$table      = $this->prefixTable($table);
		$columnsRaw = $this->getAll("PRAGMA table_info('$table')");
		$columns    = [];
		foreach($columnsRaw as $r)
			$columns[$r['name']] = $r['type'];
		return $columns;
	}
	function createTable($table){
		$table = $this->escTable($table);
		$this->execute('CREATE TABLE '.$table.' ( id INTEGER PRIMARY KEY AUTOINCREMENT ) ');
	}
	function addColumn($table, $column, $type){
		$column = $this->check($column);
		$table  = $this->check($table);
		$type   = $this->typeno_sqltype[$type];
		$this->execute('ALTER TABLE `'.$table.'` ADD `'.$column.'` '.$type);
	}
	function changeColumn($type, $column, $datatype){
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
	function getKeyMapForType( $type ){
		$table = $this->prefixTable( $type );
		$keys  = $this->getAll( "PRAGMA foreign_key_list('$table')" );
		$keyInfoList = [];
		foreach ( $keys as $k ) {
			$label = self::makeFKLabel( $k['from'], $k['table'], $k['to'] );
			$keyInfoList[$label] = [
				'name'          => $label,
				'from'          => $k['from'],
				'table'         => $k['table'],
				'to'            => $k['to'],
				'on_update'     => $k['on_update'],
				'on_delete'     => $k['on_delete']
			];
		}
		return $keyInfoList;
	}
	function fulltextQueryParts($search){
		
		
		return [$select,$from,$where,$orderBy];
	}
}