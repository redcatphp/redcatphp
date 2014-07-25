<?php namespace surikat\model\QueryWriter;
use surikat\model\RedBeanPHP\Adapter as Adapter;
class PostgreSQL extends \surikat\model\RedBeanPHP\QueryWriter\PostgreSQL {
	use AQueryWriter;
	const C_DATATYPE_FULLTEXT             = 20;
	protected $separator = ',';
	protected $agg = 'string_agg';
	protected $aggCaster = '::text';
	protected $sumCaster = '::int';
	protected $concatenator = 'chr(29)';
	function __construct(Adapter $adapter){
		parent::__construct($adapter);
		$this->addSqlColumnType(self::C_DATATYPE_FULLTEXT,' tsvector ');
	}
	function addColumnFulltext($table, $col){
		$this->addColumn($table,$col,$this->code('tsvector'));
	}
	function addIndexFullText($table, $col, $name=null ){
		if(!isset($name))
			$name = $table.'_'.$col.'_fulltext';
		$col  = $this->esc($col);
		$table  = $this->esc( $table );
		$name   = preg_replace( '/\W/', '', $name );
		if($this->adapter->getCell( "SELECT COUNT(*) FROM pg_class WHERE relname = '$name'" ))
			return;
		try{
			$this->adapter->exec("CREATE INDEX $name ON $table USING gin($col) ");
		}
		catch (\Exception $e ) {
		}
	}
}