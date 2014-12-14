<?php namespace Surikat\Model\SQLComposer;
/* SQLComposerBase - The base class that all query classes should extend.
 * @package SQLComposer
 * @author Shane Smith
 * @enhanced by surikat for unsettings queries fragments and params
 */
abstract class Base {

	private function _remove_params($clause,$i=null,$params=null){
		if($clause=='columns')
			$clause = 'select';
		if(isset($this->params[$clause])){
			if(!isset($i))
				$i = count($this->params[$clause])-1;
			if(isset($this->params[$clause][$i])&&(!isset($params)||$params==$this->params[$clause][$i])){
				if(isset($this->mysqli_types[$clause]))
					$this->mysqli_types[$clause] = substr($this->mysqli_types[$clause],count($this->params[$clause][$i])*-1);
				unset($this->params[$clause][$i]);
				$this->params[$clause] = array_values($this->params[$clause]); //reindex
				return true;
			}
		}
	}
	function remove_property($k,$v=null,$params=null,$once=null){
		if($params===false){
			$params = null;
			$once = true;
		}
		foreach(array_keys($this->$k) as $i)
			if(!isset($v)||$this->{$k}[$i]==$v){
				$found = $this->_remove_params($k,$i,$params);
				if(!isset($params)||$found)
					unset($this->{$k}[$i]);
				$this->{$k} = array_values($this->{$k}); //reindex
				if((isset($params)&&$found)||(!isset($params)&&$once))
					return $i;
			}
	}
	function unJoin($table=null,$params=null){
		$this->remove_property('tables',$table,$params);
		return $this;
	}
	function unFrom($table=null,$params=null){
		$this->remove_property('tables',$table,$params);
		return $this;
	}
	private static $__apiProp = [
		'select'=>'columns',
		'join'=>'tables',
		'from'=>'tables',
	];
	function __get($k){
		if(isset(self::$__apiProp[$k]))
			$k = self::$__apiProp[$k];
		if(property_exists($this,$k))
			return $this->$k;
	}
	
	protected $columns = [];
	protected $tables = [];
	protected $params = [];
	protected $paramsAssoc = [];
	protected $mysqli_types = [ ];
	function add_table($table,  array $params = null, $mysqli_types = "") {
		if(!empty($params)||!in_array($table,$this->tables))
			$this->tables[] = $table;
		$this->_add_params('tables', $params, $mysqli_types);
		return $this;
	}
	function join($table,  array $params = null, $mysqli_types = "") {
		return $this->add_table($table, $params, $mysqli_types);
	}
	function from($table,  array $params = null, $mysqli_types = "") {
		return $this->add_table($table, $params, $mysqli_types);
	}
	protected function _add_params($clause,  array $params = null, $mysqli_types = "") {
		if (isset($params)){
			if (!isset($this->params[$clause]))
				$this->params[$clause] = [];
			if (!empty($mysqli_types))
				$this->mysqli_types[$clause] .= $mysqli_types;
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
		$mysqli_types = "";
		foreach ($order as $clause) {
			if(empty($this->params[$clause]))
				continue;
			foreach($this->params[$clause] as $p)
				$params = array_merge($params, $p);
			if(isset($this->mysqli_types[$clause]))
				$mysqli_types .= $this->mysqli_types[$clause];
		}
		foreach($this->paramsAssoc as $k=>$v)
			$params[$k] = $v;
		if(!empty($this->mysqli_types))
			array_unshift($params, $mysqli_types);
		return $params;
	}
	function getQuery($removeUnbinded=true){
		$q = $this->render($removeUnbinded);
		if($this->writer)
			$q = str_replace('{$prefix}',$this->writer->prefix,$q);
		return $q;
	}
	abstract function render();
	function debug() {
		return $this->getQuery() . "\n\n" . print_r($this->getParams(), true);
	}
	protected static function _render_bool_expr( array $expression) {
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
	protected $writer;
	function setWriter($writer){
		$this->writer = $writer;
	}
	
	function set($k,$v){
		$k = ':'.ltrim($k,':');
		$this->paramsAssoc[$k] = $v;
	}
	function get($k){
		return $this->paramsAssoc[$k];
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
}