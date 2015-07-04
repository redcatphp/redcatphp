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
	protected function getInsertSuffix( $primaryKey ){
		return 'RETURNING '.$primaryKey.' ';
	}
	function getTables(){
		return $this->getCol( 'SELECT table_name FROM information_schema.tables WHERE table_schema = ANY( current_schemas( FALSE ) )' );
	}
	function createTable( $table ){
		$table = $this->escTable($table);
		$this->execute(" CREATE TABLE $table (id SERIAL PRIMARY KEY); ");
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
	function getTables(){
		return $this->getCol('SELECT table_name FROM information_schema.tables WHERE table_schema = ANY( current_schemas( FALSE ) )');
	}
	function getColumns($table){
		$table = $this->prefixTable($table);
		$columnsRaw = $this->getAll("SELECT column_name, data_type FROM information_schema.columns WHERE table_name='$table'");
		$columns = [];
		foreach ( $columnsRaw as $r ) {
			$columns[$r['column_name']] = $r['data_type'];
		}
		return $columns;
	}
	function createTable($table){
		$table = $this->escTable($table);
		$this->execute("CREATE TABLE $table (id SERIAL PRIMARY KEY);");
	}
	function addColumn( $type, $column, $field ){
		$table  = $type;
		$type   = $field;
		$table  = $this->escTable( $table );
		$column = $this->esc( $column );
		$type = ( isset( $this->typeno_sqltype[$type] ) ) ? $this->typeno_sqltype[$type] : '';
		$this->execute("ALTER TABLE $table ADD $column $type ");
	}
	function changeColumn( $type, $column, $datatype ){
		$table   = $type;
		$type    = $datatype;
		$table   = $this->escTable( $table );
		$column  = $this->esc( $column );
		$newtype = $this->typeno_sqltype[$type];
		$this->execute( "ALTER TABLE $table \n\t ALTER COLUMN $column TYPE $newtype " );
	}
	
	protected function getKeyMapForType( $type )
	{
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
		if ( !is_null( $this->getForeignKeyForTypeProperty( $tableNoQ, $fieldNoQ ) ) ) return FALSE;
		try{
			$delRule = ( $isDep ? 'CASCADE' : 'SET NULL' );
			$this->execute( "ALTER TABLE {$table}
				ADD FOREIGN KEY ( {$field} ) REFERENCES  {$targetTable}
				({$targetField}) ON DELETE {$delRule} ON UPDATE {$delRule} DEFERRABLE ;" );
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
		foreach( $properties as $key => $column )
			$columns[$key] = $this->esc( $column );
		$table = $this->escTable( $type );
		sort( $columns ); //else we get multiple indexes due to order-effects
		$name = "UQ_" . sha1( $table . implode( ',', $columns ) );
		$sql = "ALTER TABLE {$table}
                ADD CONSTRAINT $name UNIQUE (" . implode( ',', $columns ) . ")";
		try {
			$this->execute( $sql );
		} catch( \PDOException $e ) {
			return false;
		}
		return true;
	}
}