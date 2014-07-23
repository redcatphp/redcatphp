<?php namespace surikat\model\QueryWriter;
use surikat\model\RedBeanPHP\Adapter as Adapter;
class PostgreSQL extends \surikat\model\RedBeanPHP\QueryWriter\PostgreSQL {
	use AQueryWriter;
	protected $separator = ',';
	protected $agg = 'string_agg';
	protected $aggCaster = '::text';
	protected $sumCaster = '::int';
	protected $concatenator = 'chr(29)';
	/*
	function addIndexFullText( $type, $name, $cols ){
		$table  = $type;
		$table  = $this->esc( $table );
		$name   = preg_replace( '/\W/', '', $name );
		if($this->adapter->getCell( "SELECT COUNT(*) FROM pg_class WHERE relname = '$name'" ))
			return;
		$columns = '';
		foreach((array)$cols as $k=>$v){
			//$col = "to_tsvector(language, $k)";
				//$col = "setweight($col,'$v')";
			//$columns .= $col;
		}
		try{
			$this->adapter->exec("CREATE INDEX $name ON $table USING gin($columns) ");
		} catch (\Exception $e ) {
			//do nothing
		}
	}
	*/
}