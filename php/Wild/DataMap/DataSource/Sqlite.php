<?php
namespace Wild\DataMap\DataSource;
use Wild\DataMap\Exception;
class Sqlite extends SQL{
	const C_DATATYPE_INTEGER   = 0;
	const C_DATATYPE_NUMERIC   = 1;
	const C_DATATYPE_TEXT      = 2;
	const C_DATATYPE_SPECIFIED = 99;
	protected $quoteCharacter = '`';
	
	protected $separator = ',';
	protected $agg = 'GROUP_CONCAT';
	protected $aggCaster = '';
	protected $sumCaster = '';
	protected $concatenator = "cast(X'1D' as text)";
	protected $unknownDatabaseCode = 14;
	
	function construct(array $config=[]){
		parent::construct($config);
		$this->typeno_sqltype = [
			self::C_DATATYPE_INTEGER => 'INTEGER',
			self::C_DATATYPE_NUMERIC => 'NUMERIC',
			self::C_DATATYPE_TEXT    => 'TEXT',
		];
		foreach ( $this->typeno_sqltype as $k => $v )
			$this->sqltype_typeno[strtolower($v)] = $k;
	}
	function connect(){
		if($this->isConnected)
			return;
		try {
			$this->setPDO($this->dsn);
			$this->isConnected = true;
		}
		catch ( \PDOException $exception ) {
			if($this->createDb&&(!$this->unknownDatabaseCode||$this->unknownDatabaseCode==$exception->getCode())){
				$p = strpos($this->dsn,':')+1;
				$p2 = strpos($this->dsn,';',$p);
				if($p2===false){
					$dbfile = substr($this->dsn,$p);
				}
				else{
					$dbfile = substr($this->dsn,$p,$p2-$p);
				}
				$this->createDatabase($dbfile);
				$this->setPDO($this->dsn);
				$this->isConnected = true;
			}
			else{
				$this->isConnected = false;
				throw $exception;
			}
		}
	}
	function createDatabase($dbfile){
		$dir = dirname($dbfile);
		if(is_dir($dir)){
			throw new Exception('Unable to write '.$dbfile.' db file');
		}
		elseif(!mkdir($dir,0777,true)){
			throw new Exception('Unable to make '.dirname($dbfile).' directory');
		}
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
		$column = $this->esc($column);
		$table  = $this->escTable($table);
		if(is_integer($type))
			$type   = $this->typeno_sqltype[$type];
		$this->execute('ALTER TABLE '.$table.' ADD '.$column.' '.$type);
	}
	function changeColumnQuery($type, $column, $dataType){
		$t = $this->getTable( $type );
		if(is_integer($dataType))
			$dataType = $this->typeno_sqltype[$dataType];
		$t['columns'][$column] = $dataType;
		$this->putTable($t);
	}
	protected function putTable( $tableMap ){ //In SQLite we can't change columns, drop columns, change or add foreign keys so we have a table-rebuild function. You simply load your table with getTable(), modify it and then store it with putTable()
		$type = $tableMap['name'];
		$table = $this->prefixTable($type);
		$q     = [];
		$q[]   = "DROP TABLE IF EXISTS _tmp_backup;";
		$oldColumnNames = array_keys( $this->getColumns( $type ) );
		foreach($oldColumnNames as $k => $v)
			$oldColumnNames[$k] = $this->esc($v);
		$q[] = "CREATE TEMPORARY TABLE _tmp_backup(" . implode( ",", $oldColumnNames ) . ");";
		$q[] = "INSERT INTO _tmp_backup SELECT * FROM `$table`;";
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
		$q[] = "CREATE TABLE `$table` ( `id` INTEGER PRIMARY KEY AUTOINCREMENT  $newTableDefStr  $fkDef )";
		foreach ( $tableMap['indexes'] as $name => $index ) {
			if ( strpos( $name, 'uq_' ) === 0 ) {
				$cols = explode( '__', substr( $name, strlen( 'uq_' . $table ) ) );
				foreach ( $cols as $k => $v )
					$cols[$k] = "`$v`";
				$q[] = "CREATE UNIQUE INDEX $name ON `$table` (" . implode( ',', $cols ) . ")";
			}
				else $q[] = "CREATE INDEX $name ON `$table` ({$index['name']}) ";
		}
		$q[] = "INSERT INTO `$table` SELECT * FROM _tmp_backup";
		$q[] = "DROP TABLE _tmp_backup";
		$q[] = "PRAGMA foreign_keys = 1 ";
		foreach ( $q as $sq ){
			$this->execute( $sq );
		}
	}
	function getTable( $type ){
		$columns   = $this->getColumns($type);
		$indexes   = $this->getIndexes($type);
		$keys      = $this->getKeyMapForType($type);
		$table = [
			'columns' => $columns,
			'indexes' => $indexes,
			'keys' => $keys,
			'name' => $type
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
	function addFK( $type, $targetType, $property, $targetProperty, $constraint = false ){
		$table           = $this->prefixTable( $type );
		$targetTable     = $this->prefixTable( $targetType );
		$column          = $this->check( $property );
		$targetColumn    = $this->check( $targetProperty );

		$tables = $this->getTables();
		if ( !in_array( $targetTable, $tables ) )
			return false;
		
		$consSQL = $constraint ? 'CASCADE' : 'SET NULL';
		$fk = $this->getForeignKeyForTypeProperty( $type, $column );
		if ( !is_null( $fk )
			&&($fk['on_update']==$consSQL||$fk['on_update']=='CASCADE')
			&&($fk['on_delete']==$consSQL||$fk['on_update']=='CASCADE')
		)
			return false;
		$t = $this->getTable( $type );
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
		$typedescription = strtolower($typedescription);
		return  ( isset( $this->sqltype_typeno[$typedescription] ) ) ? $this->sqltype_typeno[$typedescription]:99;
	}
	function getTypeForID(){
		return self::C_DATATYPE_INTEGER;
	}
	function addUniqueConstraint( $type, $properties ){
		$name  = 'uq_' . $this->prefixTable( $type ) . implode( '__', (array)$properties );
		$t     = $this->getTable( $type );
		if(isset($t['indexes'][$name]))
			return true;
		$t['indexes'][$name] = [ 'name' => $name ];
		$this->putTable( $t );
	}
	function addIndex( $type, $name, $column ){
		$columns = $this->getColumns( $type );
		if ( !isset( $columns[$column] ) )
			return false;
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
	function getFtsTableSuffix(){
		return $this->ftsTableSuffix;
	}
	function makeFtsTable($type,$columns=[],$primaryKey='id',$uniqTextKey='uniq',$fullTextSearchLocale=null){
		$ftsTable = $this->escTable($type.$this->ftsTableSuffix);
		$table = $this->escTable($type);
		if(empty($columns)){
			$sufxL = -1*strlen($this->ftsTableSuffix);
			foreach($this->getColumns($type) as $col=>$colType){
				if(strtolower($colType)=='text'&&($col==$uniqTextKey||substr($col,$sufxL)==$this->ftsTableSuffix))
					$columns[] = $col;
			}
			if(empty($columns))
				throw new Exception('Unable to find columns from "'.$table.'" to create FTS table "'.$ftsTable.'"');
		}
		$ftsType = $type.$this->ftsTableSuffix;
		$pTable = $this->prefixTable($type);
		$exist = $this->tableExists($ftsType);
		$makeColumns = $columns;
		if($exist){
			$oldColumns = array_keys($this->getColumns($ftsType));
			foreach($columns as $col){
				if(!in_array($col,$oldColumns)){
					$this->execute('DROP TABLE '.$ftsType);
					foreach($oldColumns as $col){
						if(!in_array($col,$makeColumns))
							$makeColumns[] = $col;
					}
					$exist = false;
					break;
				}
			}
		}
		if(!$exist){
			if($fullTextSearchLocale)
				$tokenize = 'icu '.$fullTextSearchLocale;
			else
				$tokenize = 'porter';
			$pk = $this->esc($primaryKey);
			$cols = '`'.implode('`,`',$makeColumns).'`';
			$newCols = 'NEW.`'.implode('`,NEW.`',$makeColumns).'`';
			$this->execute('CREATE VIRTUAL TABLE '.$ftsTable.' USING fts4('.$cols.', tokenize='.$tokenize.')');
			$this->execute('DROP TRIGGER IF EXISTS '.$pTable.'_bu');
			$this->execute('DROP TRIGGER IF EXISTS '.$pTable.'_bd');
			$this->execute('DROP TRIGGER IF EXISTS '.$pTable.'_au');
			$this->execute('DROP TRIGGER IF EXISTS '.$pTable.'_ad');
			$this->execute("CREATE TRIGGER {$pTable}_bu BEFORE UPDATE ON {$table} BEGIN DELETE FROM {$ftsTable} WHERE docid=OLD.{$pk}; END;");
			$this->execute("CREATE TRIGGER {$pTable}_bd BEFORE DELETE ON {$table} BEGIN DELETE FROM {$ftsTable} WHERE docid=OLD.{$pk}; END;");
			$this->execute("CREATE TRIGGER {$pTable}_au AFTER UPDATE ON {$table} BEGIN INSERT INTO {$ftsTable}(docid, {$cols}) VALUES(NEW.{$pk}, {$newCols}); END;");
			$this->execute("CREATE TRIGGER {$pTable}_ad AFTER INSERT ON {$table} BEGIN INSERT INTO {$ftsTable}(docid, {$cols}) VALUES(NEW.{$pk}, {$newCols}); END;");
			$this->execute('INSERT INTO '.$ftsTable.'(docid,'.$cols.') SELECT '.$pk.','.$cols.' FROM '.$table);
		}
	}
}