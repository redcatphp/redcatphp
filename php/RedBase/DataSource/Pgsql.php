<?php
namespace RedBase\DataSource;
class Pgsql extends SQL{
	const C_DATATYPE_INTEGER          = 0;
	const C_DATATYPE_DOUBLE           = 1;
	const C_DATATYPE_TEXT             = 3;
	const C_DATATYPE_SPECIAL_DATE     = 80;
	const C_DATATYPE_SPECIAL_DATETIME = 81;
	const C_DATATYPE_SPECIAL_POINT    = 90;
	const C_DATATYPE_SPECIAL_LSEG     = 91;
	const C_DATATYPE_SPECIAL_CIRCLE   = 92;
	const C_DATATYPE_SPECIAL_MONEY    = 93;
	const C_DATATYPE_SPECIAL_POLYGON  = 94;
	const C_DATATYPE_SPECIFIED        = 99;
	protected $defaultValue = 'DEFAULT';
	protected $quoteCharacter = '"';
	function construct(array $config=[]){
		parent::construct($config);
		$this->typeno_sqltype = [
			self::C_DATATYPE_INTEGER          => ' integer ',
			self::C_DATATYPE_DOUBLE           => ' double precision ',
			self::C_DATATYPE_TEXT             => ' text ',
			self::C_DATATYPE_SPECIAL_DATE     => ' date ',
			self::C_DATATYPE_SPECIAL_DATETIME => ' timestamp without time zone ',
			self::C_DATATYPE_SPECIAL_POINT    => ' point ',
			self::C_DATATYPE_SPECIAL_LSEG     => ' lseg ',
			self::C_DATATYPE_SPECIAL_CIRCLE   => ' circle ',
			self::C_DATATYPE_SPECIAL_MONEY    => ' money ',
			self::C_DATATYPE_SPECIAL_POLYGON  => ' polygon ',
		];
		$this->sqltype_typeno = [];
		foreach( $this->typeno_sqltype as $k => $v ){
			$this->sqltype_typeno[trim( strtolower( $v ) )] = $k;
		}
	}
	function createDatabase($dbname){
		$this->pdo->exec('CREATE DATABASE "'.$dbname.'"');
	}
	protected function getInsertSuffix( $primaryKey ){
		return 'RETURNING "'.$primaryKey.'" ';
	}
	function getTablesQuery(){
		return $this->getCol( 'SELECT table_name FROM information_schema.tables WHERE table_schema = ANY( current_schemas( FALSE ) )' );
	}
	function scanType( $value, $flagSpecial = FALSE ){
		if ( $value === INF )
			return self::C_DATATYPE_TEXT;
		if ( $flagSpecial && $value ) {
			if ( preg_match( '/^\d{4}\-\d\d-\d\d$/', $value ) )
				return self::C_DATATYPE_SPECIAL_DATE;
			if ( preg_match( '/^\d{4}\-\d\d-\d\d\s\d\d:\d\d:\d\d(\.\d{1,6})?$/', $value ) )
				return self::C_DATATYPE_SPECIAL_DATETIME;
			if ( preg_match( '/^\([\d\.]+,[\d\.]+\)$/', $value ) )
				return self::C_DATATYPE_SPECIAL_POINT;
			if ( preg_match( '/^\[\([\d\.]+,[\d\.]+\),\([\d\.]+,[\d\.]+\)\]$/', $value ) )
				return self::C_DATATYPE_SPECIAL_LSEG;
			if ( preg_match( '/^\<\([\d\.]+,[\d\.]+\),[\d\.]+\>$/', $value ) )
				return self::C_DATATYPE_SPECIAL_CIRCLE;
			if ( preg_match( '/^\((\([\d\.]+,[\d\.]+\),?)+\)$/', $value ) )
				return self::C_DATATYPE_SPECIAL_POLYGON;
			if ( preg_match( '/^\-?(\$|€|¥|£)[\d,\.]+$/', $value ) )
				return self::C_DATATYPE_SPECIAL_MONEY;
		}
		if ( is_float( $value ) )
			return self::C_DATATYPE_DOUBLE;
		if ( self::startsWithZeros( $value ) )
			return self::C_DATATYPE_TEXT;
		if ( $value === FALSE || $value === TRUE || $value === NULL || ( is_numeric( $value )
				&& self::canBeTreatedAsInt( $value )
				&& $value < 2147483648
				&& $value > -2147483648 )
		)
			return self::C_DATATYPE_INTEGER;
		elseif ( is_numeric( $value ) )
			return self::C_DATATYPE_DOUBLE;
		else
			return self::C_DATATYPE_TEXT;
	}
	function getColumnsQuery($table){
		$table = $this->prefixTable($table);
		$columnsRaw = $this->getAll("SELECT column_name, data_type FROM information_schema.columns WHERE table_name='$table'");
		$columns = [];
		foreach ( $columnsRaw as $r ) {
			$columns[$r['column_name']] = $r['data_type'];
		}
		return $columns;
	}
	function createTableQuery($table,$pk='id'){
		$table = $this->escTable($table);
		$this->execute('CREATE TABLE '.$table.' ('.$pk.' SERIAL PRIMARY KEY);');
	}
	function addColumnQuery( $type, $column, $field ){
		$table  = $type;
		$type   = $field;
		$table  = $this->escTable( $table );
		$column = $this->esc( $column );
		$type = ( isset( $this->typeno_sqltype[$type] ) ) ? $this->typeno_sqltype[$type] : '';
		$this->execute("ALTER TABLE $table ADD $column $type ");
	}
	function changeColumnQuery( $type, $column, $datatype ){
		$table   = $type;
		$type    = $datatype;
		$table   = $this->escTable( $table );
		$column  = $this->esc( $column );
		$newtype = $this->typeno_sqltype[$type];
		$this->execute( "ALTER TABLE $table \n\t ALTER COLUMN $column TYPE $newtype " );
	}
	
	function getKeyMapForType($type){
		$table = $this->prefixTable( $type );
		$keys = $this->getAll( '
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
		', [$type] );
		$keyInfoList = [];
		foreach ( $keys as $k ) {
			$label = self::makeFKLabel( $k['from'], $k['table'], $k['to'] );
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
	function addFK( $type, $targetType, $property, $targetProperty, $isDep = FALSE ){
		$table = $this->escTable( $type );
		$targetTable = $this->escTable( $targetType );
		$field = $this->esc( $property );
		$targetField = $this->esc( $targetProperty );
		$tableNoQ = $this->prefixTable( $type );
		$fieldNoQ = $this->check( $property );
		
		$casc = ( $isDep ? 'CASCADE' : 'SET NULL' );
		$fk = $this->getForeignKeyForTypeProperty( $tableNoQ, $fieldNoQ );
		if ( !is_null( $fk )
			&&($fk['on_update']==$casc||$fk['on_update']=='CASCADE')
			&&($fk['on_delete']==$casc||$fk['on_update']=='CASCADE')
		)
			return false;
		try{
			$this->execute( "ALTER TABLE {$table}
				ADD FOREIGN KEY ( {$field} ) REFERENCES  {$targetTable}
				({$targetField}) ON DELETE {$casc} ON UPDATE {$casc} DEFERRABLE ;" );
			return true;
		} catch ( \PDOException $e ) {
			return false;
		}
	}
	function columnCode( $typedescription, $includeSpecials = FALSE ){
		$r = isset($this->sqltype_typeno[$typedescription])?$this->sqltype_typeno[$typedescription]:99;
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
		sort( $columns ); //else we get multiple indexes due to order-effects
		$name = "UQ_" . sha1( $table . implode( ',', $columns ) );
		$sql = "ALTER TABLE {$table}
                ADD CONSTRAINT $name UNIQUE (" . implode( ',', $columns ) . ")";
		try {
			$this->execute( $sql );
		}
		catch( \PDOException $e ) {
			return false;
		}
		return true;
	}
	function addIndex( $type, $name, $property ){
		$table  = $this->escTable( $type );
		$name   = preg_replace( '/\W/', '', $name );
		$column = $this->esc( $property );
		try{
			$this->execute( "CREATE INDEX {$name} ON $table ({$column}) " );
			return true;
		}
		catch(\PDOException $e){
			return false;
		}
	}
	
	function clear($type){
		$table = $this->escTable($type);
		$this->execute('TRUNCATE '.$table);
	}
	protected function _drop($type){
		$t = $this->escTable($type);
		$this->execute('SET CONSTRAINTS ALL DEFERRED');
		$this->execute("DROP TABLE IF EXISTS $t CASCADE ");
		$this->execute('SET CONSTRAINTS ALL IMMEDIATE');
	}
	protected function _dropAll(){
		$this->execute('SET CONSTRAINTS ALL DEFERRED');
		foreach($this->getTables() as $t){
			$this->execute('DROP TABLE IF EXISTS "'.$t.'" CASCADE ');
		}
		$this->execute('SET CONSTRAINTS ALL IMMEDIATE');
	}
	
	protected function explain($sql,$bindings=[]){
		$explain = $this->pdo->prepare('EXPLAIN '.$sql,[\PDO::PGSQL_ATTR_DISABLE_NATIVE_PREPARED_STATEMENT=>true]);
		$this->bindParams($explain,$bindings);
		$explain->execute();
		$explain = $explain->fetchAll();
		return implode("\n",array_map(function($entry){
			return implode("\n",$entry);
		}, $explain));
	}
	
	function getFkMap($type,$primaryKey='id'){
		$table = $this->prefixTable($type);
		return $this->getAll("SELECT (SELECT r.relname FROM pg_class r WHERE r.oid = c.conrelid) AS table, (SELECT trim(array_agg(attname)::text,'{}') FROM pg_attribute WHERE attrelid = c.conrelid AND ARRAY[attnum] <@ c.conkey) AS column, conname AS constraint FROM pg_constraint c WHERE c.confrelid = (SELECT oid FROM pg_class WHERE relname = '$table')");
	}
	
	function adaptPrimaryKey($type,$id,$primaryKey='id'){
		if($id<2147483647)
			return;
		$table = $this->escTable($type);
		$pk = $this->esc($primaryKey);
		$fks = $this->getFkMap($type,$primaryKey);
		debug($fks);
	}
}