<?php
namespace RedBase\DataSoure;
class Cubrid extends SQL{
	const C_DATATYPE_INTEGER          = 0;
	const C_DATATYPE_DOUBLE           = 1;
	const C_DATATYPE_STRING           = 2;
	const C_DATATYPE_SPECIAL_DATE     = 80;
	const C_DATATYPE_SPECIAL_DATETIME = 81;
	const C_DATATYPE_SPECIFIED        = 99;
	protected $quoteCharacter = '`';
	protected $max = 2147483647;
	protected $integerMax = 9223372036854775807;
	function construct(array $config=[]){
		parent::construct($config);
		$this->typeno_sqltype = [
			self::C_DATATYPE_INTEGER          => ' INTEGER ',
			self::C_DATATYPE_DOUBLE           => ' DOUBLE ',
			self::C_DATATYPE_STRING           => ' STRING ',
			self::C_DATATYPE_SPECIAL_DATE     => ' DATE ',
			self::C_DATATYPE_SPECIAL_DATETIME => ' DATETIME ',
		];
		$this->sqltype_typeno = [];
		foreach ( $this->typeno_sqltype as $k => $v ) {
			$this->sqltype_typeno[trim( ( $v ) )] = $k;
		}
		$this->sqltype_typeno['STRING(1073741823)'] = self::C_DATATYPE_STRING;
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
		$type   = array_key_exists( $type, $this->typeno_sqltype ) ? $this->typeno_sqltype[$type] : '';
		$this->execute( "ALTER TABLE $table ADD COLUMN $column $type " );
	}
	function changeColumnQuery( $type, $property, $dataType ){
		if ( !isset($this->typeno_sqltype[$dataType]) )
			return false;
		$table   = $this->escTable( $type );
		$column  = $this->esc( $property );
		$newType = $this->typeno_sqltype[$dataType];
		$this->execute( "ALTER TABLE $table CHANGE $column $column $newType " );
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
		$fk = $this->getForeignKeyForTypeProperty( $tableNoQ, $columnNoQ );
		if ( !is_null( $fk )
			&&($fk['on_update']==$casc||$fk['on_update']=='CASCADE')
			&&($fk['on_delete']==$casc||$fk['on_update']=='CASCADE')
		)
			return false;
		$needsToDropFK   = FALSE;
		$sql  = "ALTER TABLE $table ADD CONSTRAINT FOREIGN KEY($column) REFERENCES $targetTable($targetColumn) ON DELETE $casc ";
		try {
			$this->exec($sql);
		} catch( \PDOException $e ) {
			return FALSE;
		}
		return TRUE;
	}
	protected function getKeyMapForType( $type  ){
		$table = $this->prefixTable($type);
		$sqlCode = $this->getAll("SHOW CREATE TABLE `{$table}`");
		if (!isset($sqlCode[0])) return array();
		$matches = array();
		preg_match_all( '/CONSTRAINT\s+\[([\w_]+)\]\s+FOREIGN\s+KEY\s+\(\[([\w_]+)\]\)\s+REFERENCES\s+\[([\w_]+)\](\s+ON\s+DELETE\s+(CASCADE|SET\sNULL|RESTRICT|NO\sACTION)\s+ON\s+UPDATE\s+(SET\sNULL|RESTRICT|NO\sACTION))?/', $sqlCode[0]['CREATE TABLE'], $matches );
		$list = array();
		if (!isset($matches[0])) return $list;
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
		$r = ( ( isset( $this->sqltype_typeno[$typedescription] ) ) ? $this->sqltype_typeno[$typedescription] : self::C_DATATYPE_SPECIFIED );
		if ( $includeSpecials )
			return $r;
		if ( $r >= QueryWriter::C_DATATYPE_RANGE_SPECIAL )
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
		sort( $columns ); // else we get multiple indexes due to order-effects
		$name = 'UQ_' . sha1( implode( ',', $columns ) );
		$sql = "ALTER TABLE $table ADD CONSTRAINT UNIQUE $name (" . implode( ',', $columns ) . ")";
		try {
			$this->execute( $sql );
		} catch( \PDOException $e ) {
			return false;
		}
		return true;
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
		if(strpos($sql,'CREATE')!==0&&strpos($sql,'ALTER')!==0){
			$explain = $this->pdo->prepare('EXPLAIN '.$sql);
			$this->bindParams($explain,$bindings);
			$explain->execute();
			$explain = $explain->fetchAll();
			return implode("\n",array_map(function($entry){
				return implode("\n",$entry);
			}, $explain));
		}
	}
	
	protected function adaptPrimaryKey($type,$id,$primaryKey='id'){
		
	}
}