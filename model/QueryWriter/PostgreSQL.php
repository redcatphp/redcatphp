<?php namespace surikat\model\QueryWriter;
use surikat\model\RedBeanPHP\Adapter;
use surikat\model\R;
use surikat\model\Table;
use surikat\model\Query4D;
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
	function addIndexFullText($table, $col, $name = null ){
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
	function handleFullText($table, $col, Array $cols, Table &$model){
		$columns = array();
		$join = '';
		foreach((array)$cols as $k=>$v){
			$weight = null;
			if($pos=strpos($v,'/')!==false){
				$weight = trim(substr($v,$pos));
				$v = trim(substr($v,0,$pos));
				if(!in_array($weight,array('A','B','C','D')))
					$weight = null;
			}
			$c = $this->esc(R::toSnake($v));
			if(!is_integer($k)){ //relation
				$c = $this->esc(R::toSnake($k)).'.'.$c;
			}
			$c = "to_tsvector(language, $c)";
			if($weight)
				$c = "setweight($v,'$weight')";
		}
		$w = &$this;
		$model->onChanged(function($bean)use($col,$columns,&$w,$join){
			$model = $bean->box();
			$table = $w->esc($model->getTable());
			$col = $w->esc(R::toSnake($col));
			//$q = new Query4D($model->getTable(),'update');
			//$q->exec();
			//R::exec('UPDATE '.$table.$join.' SET '.$col.'='.implode(" || ' ' || ",$columns).' WHERE id=?',array($bean->id));
		});
		
	}
		
}