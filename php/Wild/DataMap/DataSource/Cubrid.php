<?php
namespace Wild\DataMap\DataSource;
use Wild\DataMap\Exception;
class Cubrid extends SQL{
	const C_DATATYPE_INTEGER          = 0;
	const C_DATATYPE_BIGINT           = 1;
	const C_DATATYPE_DOUBLE           = 2;
	const C_DATATYPE_STRING           = 3;
	const C_DATATYPE_SPECIAL_DATE     = 80;
	const C_DATATYPE_SPECIAL_DATETIME = 81;
	const C_DATATYPE_SPECIFIED        = 99;
	protected $quoteCharacter = '`';
	protected $max = 2147483647;
	function construct(array $config=[]){
		parent::construct($config);
		$this->typeno_sqltype = [
			self::C_DATATYPE_INTEGER          => ' INTEGER ',
			self::C_DATATYPE_BIGINT           => ' BIGINT ',
			self::C_DATATYPE_DOUBLE           => ' DOUBLE ',
			self::C_DATATYPE_STRING           => ' STRING ',
			self::C_DATATYPE_SPECIAL_DATE     => ' DATE ',
			self::C_DATATYPE_SPECIAL_DATETIME => ' DATETIME ',
		];
		$this->sqltype_typeno = [];
		foreach( $this->typeno_sqltype as $k => $v ){
			$this->sqltype_typeno[strtolower(trim($v))] = $k;
		}
		$this->sqltype_typeno['string(1073741823)'] = self::C_DATATYPE_STRING;
	}
	function connect(){
		if($this->isConnected)
			return;
		parent::connect();
		if($this->loggingExplain)
			$this->pdo->exec('SET TRACE ON');
	}
	function debug($enable=true,$loggingResult=true,$loggingExplain=true){
		parent::debug($enable=true,$loggingResult=true,$loggingExplain=true);
		if($this->loggingExplain&&$this->isConnected)
			$this->pdo->exec('SET TRACE ON');
	}
	function createDatabase($dbname){
		throw new Exception('Unable to create database '.$dbname.'. CUBRID does not allow to create or drop a database from within the SQL query');
	}
	function scanType($value, $flagSpecial = false){
		if ( is_null( $value ) )
			return self::C_DATATYPE_INTEGER;
		if ( $flagSpecial ) {
			if ( preg_match( '/^\d{4}\-\d\d-\d\d$/', $value ) )
				return self::C_DATATYPE_SPECIAL_DATE;
			if ( preg_match( '/^\d{4}\-\d\d-\d\d\s\d\d:\d\d:\d\d$/', $value ) )
				return self::C_DATATYPE_SPECIAL_DATETIME;
		}
		$value = strval( $value );
		if ( !$this->startsWithZeros( $value ) ) {
			if ( is_numeric( $value ) && ( floor( $value ) == $value ) && $value >= -2147483647 && $value <= 2147483647 )
				return self::C_DATATYPE_INTEGER;
			elseif ( is_numeric( $value ) && ( floor( $value ) == $value ) && $value >= -9223372036854775807 && $value <= 9223372036854775807 )
				return self::C_DATATYPE_BIGINT;
			if ( is_numeric( $value ) )
				return self::C_DATATYPE_DOUBLE;
		}
		return self::C_DATATYPE_STRING;
	}
	function getTablesQuery(){
		return $this->getCol( "SELECT class_name FROM db_class WHERE is_system_class = 'NO';" );
	}
	function getColumnsQuery( $table ){
		$table = $this->escTable( $table );
		$columnsRaw = $this->getAll( "SHOW COLUMNS FROM $table" );
		$columns = [];
		foreach($columnsRaw as $r)
			$columns[$r['Field']] = $r['Type'];
		return $columns;
	}
	function createTableQuery($table,$pk='id'){
		$sql  = 'CREATE TABLE '.$this->escTable($table)
			.' ("'.$pk.'" integer AUTO_INCREMENT, CONSTRAINT "pk_'
			.$this->prefixTable($table)
			.'_'.$pk.'" PRIMARY KEY("'.$pk.'"))';
		$this->execute( $sql );
	}
	function addColumnQuery( $type, $column, $field ){
		$table  = $type;
		$type   = $field;
		$table  = $this->escTable( $table );
		$column = $this->esc( $column );
		if(is_integer($type))
			$type   = array_key_exists( $type, $this->typeno_sqltype ) ? $this->typeno_sqltype[$type] : '';
		$this->execute( "ALTER TABLE $table ADD COLUMN $column $type " );
	}
	function changeColumnQuery( $type, $property, $dataType ){
		$table   = $this->escTable( $type );
		$column  = $this->esc( $property );
		if(is_integer($dataType)){
			if( !isset($this->typeno_sqltype[$dataType]) )
				return false;
			$dataType = $this->typeno_sqltype[$dataType];
		}
		$this->execute( "ALTER TABLE $table CHANGE $column $column $dataType " );
		return true;
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
	function addFK( $type, $targetType, $property, $targetProperty, $isDep = FALSE ){
		$table           = $this->escTable( $type );
		$tableNoQ        = $this->prefixTable( $type );
		$targetTable     = $this->escTable( $targetType );
		$targetTableNoQ  = $this->prefixTable( $targetType );
		$column          = $this->esc( $property );
		$columnNoQ       = $this->check( $property );
		$targetColumn    = $this->esc( $targetProperty );
		$casc = ( $isDep ? 'CASCADE' : 'SET NULL' );
		$fk = $this->getForeignKeyForTypeProperty( $type, $columnNoQ );
		if ( !is_null( $fk )&&($fk['on_delete']==$casc||$fk['on_delete']=='CASCADE'))
			return false;
		$needsToDropFK   = FALSE;
		$sql  = "ALTER TABLE $table ADD CONSTRAINT FOREIGN KEY($column) REFERENCES $targetTable($targetColumn) ON DELETE $casc";
		try {
			$this->execute($sql);
		} catch( \PDOException $e ) {
			return FALSE;
		}
		return TRUE;
	}
	function getKeyMapForType( $type  ){
		$table = $this->prefixTable($type);
		$sqlCode = $this->getAll("SHOW CREATE TABLE `{$table}`");
		if(!isset($sqlCode[0]))
			return [];
		preg_match_all('/CONSTRAINT\s+\[([\w_]+)\]\s+FOREIGN\s+KEY\s+\(\[([\w_]+)\]\)\s+REFERENCES\s+\[([\w_]+)\](\s+ON\s+DELETE\s+(CASCADE|SET\sNULL|RESTRICT|NO\sACTION)\s+ON\s+UPDATE\s+(SET\sNULL|RESTRICT|NO\sACTION))?/', $sqlCode[0]['CREATE TABLE'], $matches);
		$list = [];
		if(!isset($matches[0]))
			return $list;
		$max = count($matches[0]);
		for($i = 0; $i < $max; $i++) {
			$label = self::makeFKLabel( $matches[2][$i], $matches[3][$i], 'id' );
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
	function columnCode( $typedescription, $includeSpecials = FALSE ){
		$typedescription = strtolower($typedescription);
		$r = ( ( isset( $this->sqltype_typeno[$typedescription] ) ) ? $this->sqltype_typeno[$typedescription] : self::C_DATATYPE_SPECIFIED );
		if ( $includeSpecials )
			return $r;
		if ( $r >= self::C_DATATYPE_RANGE_SPECIAL )
			return self::C_DATATYPE_SPECIFIED;
		return $r;
	}
	function getTypeForID(){
		return self::C_DATATYPE_INTEGER;
	}
	function addUniqueConstraint( $type, $properties ){
		$tableNoQ = $this->prefixTable( $type );
		$columns = [];
		foreach( (array)$properties as $key => $column )
			$columns[$key] = $this->esc( $column );
		$table = $this->escTable( $type );
		sort($columns);
		$name = 'uq_' . sha1( implode( ',', $columns ) );
		$indexMap = $this->getAll('SHOW indexes FROM '.$table);
		$exists = false;
		debug($indexMap);
		foreach($indexMap as $index){
			if($index['Key_name']==$name){
				$exists = true;
				break;
			}
		}
		if(!$exists)
			$this->execute("ALTER TABLE $table ADD CONSTRAINT UNIQUE `$name` (" . implode( ',', $columns ) . ")");
	}
	function addIndex( $type, $name, $column ){
		try {
			$table  = $this->escTable( $type );
			$name   = preg_replace( '/\W/', '', $name );
			$column = $this->esc( $column );
			$this->execute("CREATE INDEX $name ON $table ($column) ");
			return true;
		} catch ( \PDOException $e ) {
			return false;
		}
	}
	
	function clear($type){
		$table = $this->escTable($type);
		$this->execute('TRUNCATE '.$table);
	}
	protected function _drop($type){
		$t = $this->escTable($type);
		foreach($this->getKeyMapForType($type) as $k){
			$this->execute('ALTER TABLE '.$t.' DROP FOREIGN KEY "'.$k['name'].'"');
		}
		$this->execute('DROP TABLE '.$t);
	}
	protected function _dropAll(){
		foreach($this->getTables() as $t){
			$this->_drop($this->unprefixTable($t));
		}
	}
	
	protected function explain($sql,$bindings=[]){
		$explain = $this->pdo->query('SHOW TRACE')->fetchAll();
		return implode("\n",array_map(function($entry){
			return implode("\n",$entry);
		}, $explain));
	}
	
	function getFkMap($type,$primaryKey='id'){
		//foreign keys can only reference primary keys in CUBRID
		$fks = [];
		$table = $this->prefixTable($type);
		foreach($this->getTables() as $tb){
			$sqlCode = $this->getAll("SHOW CREATE TABLE `{$tb}`");
			if(!isset($sqlCode[0]))
				continue;
			preg_match_all('/CONSTRAINT\s+\[([\w_]+)\]\s+FOREIGN\s+KEY\s+\(\[([\w_]+)\]\)\s+REFERENCES\s+\[([\w_]+)\](\s+ON\s+DELETE\s+(CASCADE|SET\sNULL|RESTRICT|NO\sACTION)\s+ON\s+UPDATE\s+(SET\sNULL|RESTRICT|NO\sACTION))?/', $sqlCode[0]['CREATE TABLE'], $matches);
			if(!isset($matches[0]))
				continue;
			$list = [];
			$max = count($matches[0]);
			for($i = 0; $i < $max; $i++){
				if($matches[3][$i]==$table)
					$fks[] = [
						'table'=>$tb,
						'column'=>$matches[2][$i],
						'constraint'=>$matches[1][$i],
						'on_update'=>$matches[6][$i],
						'on_delete'=>$matches[5][$i],
					];
			}
		}
		return $fks;
	}
	
	function adaptPrimaryKey($type,$id,$primaryKey='id'){
		if($id!=2147483647)
			return;
		$cols = $this->getColumns($type);
		if($cols[$primaryKey]=='BIGINT')
			return;
		$table = $this->escTable($type);
		$pk = $this->esc($primaryKey);
		$fks = $this->getFkMap($type,$primaryKey);
		foreach($fks as $fk){
			$this->execute('ALTER TABLE `'.$fk['table'].'` DROP FOREIGN KEY `'.$fk['constraint'].'`, MODIFY `'.$fk['column'].'` BIGINT NULL');
		}
		$this->execute('ALTER TABLE '.$table.' CHANGE '.$pk.' '.$pk.' BIGINT NOT NULL AUTO_INCREMENT');
		foreach($fks as $fk){
			$this->execute('ALTER TABLE `'.$fk['table'].'` ADD FOREIGN KEY (`'.$fk['column'].'`) REFERENCES '.$table.' ('.$pk.') ON DELETE '.$fk['on_delete'].' ON UPDATE '.$fk['on_update']);
		}
	}
}