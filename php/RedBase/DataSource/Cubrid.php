<?php
namespace RedBase\DataSoure;
class Cubrid extends SQL{
	protected $max = 2147483647;
	function getTables(){
		return $this->getCol( "SELECT class_name FROM db_class WHERE is_system_class = 'NO';" );
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
		if ( !is_null( $this->getForeignKeyForTypeProperty( $tableNoQ, $columnNoQ ) ) ) return FALSE;
		$needsToDropFK   = FALSE;
		$casc = ( $isDep ? 'CASCADE' : 'SET NULL' );
		$sql  = "ALTER TABLE $table ADD CONSTRAINT FOREIGN KEY($column) REFERENCES $targetTable($targetColumn) ON DELETE $casc ";
		try {
			$this->exec($sql);
		} catch( \PDOException $e ) {
			return FALSE;
		}
		return TRUE;
	}
	protected function getKeyMapForType( $type  ){
		$sqlCode = $this->getAll("SHOW CREATE TABLE `{$type}`");
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
	function addUniqueConstraint( $type, $properties ){
		$tableNoQ = $this->prefixTable( $type );
		$columns = [];
		foreach( $properties as $key => $column )
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
}