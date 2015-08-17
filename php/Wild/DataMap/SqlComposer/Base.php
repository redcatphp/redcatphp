<?php
namespace Wild\DataMap\SqlComposer;
abstract class Base {
	protected static $operators = ['>','>=','<','<=','=','!=','between','in'];
	private static $__apiProp = [
		'select'=>'columns',
		'join'=>'tables',
		'from'=>'tables',
	];
	protected $columns = [];
	protected $tables = [];
	protected $params = [];
	protected $paramsAssoc = [];
	protected $quoteCharacter;
	protected $tablePrefix;
	protected $mainTable;	
	function __construct($mainTable = null,$quoteCharacter = '"', $tablePrefix = ''){
		$this->mainTable = $mainTable;
		$this->quoteCharacter = $quoteCharacter;
		$this->tablePrefix = $tablePrefix;
		if($this->mainTable)
			$this->from($this->mainTable);
	}
	function getMainTable(){
		return $this->mainTable;
	}
	function debug() {
		return $this->getQuery() . "\n\n" . print_r($this->getParams(), true);
	}
	function quote($v){
		if($v=='*')
			return $v;
		return $this->quoteCharacter.$this->unQuote($v).$this->quoteCharacter;
	}
	function unQuote($v){
		return trim($v,$this->quoteCharacter);
	}
	function getQuery($removeUnbinded=true){
		$q = $this->render($removeUnbinded);
		$q = str_replace('{#prefix}',$this->tablePrefix,$q);
		return $q;
	}
	function __get($k){
		if(isset(self::$__apiProp[$k]))
			$k = self::$__apiProp[$k];
		if(property_exists($this,$k))
			return $this->$k;
	}
	function hasColumn(){
		return !empty($this->columns);
	}
	function getColumn(){
		return $this->columns;
	}
	function hasTable(){
		return !empty($this->tables);
	}
	function getTable(){
		return $this->tables;
	}
	function hasJoin(){
		foreach($this->tables as $table){
			if(is_array($table))
				return true;
		}
		return false;
	}
	function getJoin(){
		$joins = [];
		foreach($this->tables as $table){
			if(is_array($table))
				$joins[] = $table;
		}
		return $joins;
	}
	function hasFrom(){
		foreach($this->tables as $table){
			if(!is_array($table))
				return true;
		}
		return false;
	}
	function getFrom(){
		$froms = [];
		foreach($this->tables as $table){
			if(!is_array($table))
				$froms[] = $table;
		}
		return $froms;
	}
	function formatColumnName($v){
		if($this->mainTable&&strpos($v,'(')===false&&strpos($v,')')===false&&strpos($v,' as ')===false&&strpos($v,'.')===false)
			$v = $this->quote($this->tablePrefix.$this->mainTable).'.'.$this->quote($v);
		return $v;
	}
	function add_table($table,  array $params = null) {
		if(!empty($params)||!in_array($table,$this->tables))
			$this->tables[] = $table;
		$this->_add_params('tables', $params);
		return $this;
	}
	function tableJoin($table,$join,array $params = null) {
		return $this->add_table([$table,$join], $params);
	}
	function joinAdd($join,array $params = null) {
		return $this->add_table((array)$join, $params);
	}
	function join($join,array $params = null){
		return $this->joinAdd('JOIN '.$join,$params);
	}
	function joinLeft($join,array $params = null){
		return $this->joinAdd('LEFT JOIN '.$join,$params);
	}
	function joinRight($join,array $params = null){
		return $this->joinAdd('RIGHT JOIN '.$join,$params);
	}
	function joinOn($join,array $params = null){
		return $this->joinAdd('ON '.$join,$params);
	}
	function from($table,  array $params = null) {
		return $this->add_table($table, $params);
	}
	function unTableJoin($table=null,$join=null,$params=null){
		$this->remove_property('tables',[$table,$join],$params);
		return $this;
	}
	function unJoin($join=null,$params=null){
		$this->remove_property('tables',$join,$params);
		return $this;
	}
	function unFrom($table=null,$params=null){
		$this->remove_property('tables',$table,$params);
		return $this;
	}
	protected function _add_params($clause,  array $params = null) {
		if (isset($params)){
			if (!isset($this->params[$clause]))
				$this->params[$clause] = [];
			$addParams = [];
			foreach($params as $k=>$v){
				if(is_integer($k))
					$addParams[] = $v;
				else
					$this->set($k,$v);
			}
			if(!empty($addParams))
				$this->params[$clause][] = $addParams;
		}
		return $this;
	}
	protected function _get_params($order) {
		if (!is_array($order))
			$order = func_get_args();
		$params = [];
		foreach ($order as $clause) {
			if(empty($this->params[$clause]))
				continue;
			foreach($this->params[$clause] as $p)
				$params = array_merge($params, $p);
		}
		foreach($this->paramsAssoc as $k=>$v)
			$params[$k] = $v;
		return $params;
	}
	function set($k,$v){
		$k = ':'.ltrim($k,':');
		$this->paramsAssoc[$k] = $v;
	}
	function get($k){
		return $this->paramsAssoc[$k];
	}
	function remove_property($k,$v=null,$params=null,$once=null){
		if($params===false){
			$params = null;
			$once = true;
		}
		$r = null;
		foreach(array_keys($this->$k) as $i){
			if(!isset($v)||$this->{$k}[$i]==$v){
				$found = $this->_remove_params($k,$i,$params);
				if(!isset($params)||$found)
					unset($this->{$k}[$i]);
				if((isset($params)&&$found)||(!isset($params)&&$once)){
					$r = $i;
					break;
				}
			}
		}
		if(isset($this->params[$k]))
			$this->params[$k] = array_values($this->params[$k]);
		$this->{$k} = array_values($this->{$k});
		return $r;
	}
	function removeUnbinded($a){
		foreach(array_keys($a) as $k){
			if(is_array($a[$k]))
				continue;
			$e = str_replace('::','',$a[$k]);
			if(strpos($e,':')!==false){
				preg_match_all('/:((?:[a-z][a-z0-9_]*))/is',$e,$match);
				if(isset($match[0])){
					foreach($match[0] as $m){
						if(!isset($this->paramsAssoc[$m])){
							unset($a[$k]);
							break;
						}
					}
				}
			}
		}
		return $a;
	}
	private function _remove_params($clause,$i=null,$params=null){
		if($clause=='columns')
			$clause = 'select';
		if(isset($this->params[$clause])){
			if(!isset($i))
				$i = count($this->params[$clause])-1;
			if(isset($this->params[$clause][$i])&&(!isset($params)||$params==$this->params[$clause][$i])){
				unset($this->params[$clause][$i]);
				return true;
			}
		}
	}
	static function render_bool_expr(array $expression){
		$str = "";
		$stack = [ ];
		$op = "AND";
		$first = true;
		foreach ($expression as $expr) {
			if (is_array($expr)) {
				if ($expr[0] == '(') {
					array_push($stack, $op);
					if (!$first)
						$str .= " " . $op;
					if ($expr[1] == "NOT") {
						$str .= " NOT";
					} else {
						$str .= " (";
						$op = $expr[1];
					}
					$first = true;
					continue;
				}
				elseif ($expr[0] == ')') {
					$op = array_pop($stack);
					$str .= " )";
				}
			}
			else {
				if (!$first)
					$str .= " " . $op;
				$str .= " (" . $expr . ")";
			}
			$first = false;
		}
		$str .= str_repeat(" )", count($stack));
		return $str;
	}
	abstract function render();
	function __toString(){
		$str = $this->getQuery();
		return $str;
	}
	function getClone(){
		return clone $this;
	}

	static function in($sql, array $params){
		$given_params = $params;
		$placeholders = [ ];
		$params = [];
		foreach($given_params as $p){
			$placeholders[] = "?";
			$params[] = $p;
		}
		$placeholders = implode(", ", $placeholders);
		$sql = str_replace("?", $placeholders, $sql);
		return [$sql, $params];
	}
	static function is_assoc($array){
		return (array_keys($array) !== range(0, count($array) - 1));
	}
	static function isValidOperator($op){
		return in_array($op, self::$operators);
	}
	static function applyOperator($column, $op, array $params=null){
		switch ($op) {
			case '>': case '>=':
			case '<': case '<=':
			case '=': case '!=':
				return ["{$column} {$op} ?", $params];
			case 'in':
				return self::in("{$column} in (?)", $params);
			case 'between':
				$sql = "{$column} between ";
				$p = array_shift($params);
				$sql .= "?";
				array_push($params, $p);
				$sql .= " and ";
				$p = array_shift($params);
				$sql .= "?";
				array_push($params, $p);
				return [$sql, $params];
			default:
				throw new Exception('Invalid operator: '.$op);
		}
	}
}