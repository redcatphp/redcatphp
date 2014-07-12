<?php namespace surikat\model\SQLComposer;
/* SQLComposerBase - The base class that all query classes should extend.
 * @package SQLComposer
 * @author Shane Smith
 * @enhanced by surikat for unsettings queries fragments and params
 */
abstract class Base {

	function __unset($k){ //reset var
		if(property_exists($this,$k)){
			$v = get_class_vars(get_class($this));
			if(isset($this->params[$k]))
				unset($this->params[$k]);
			$this->$k = $v[$k];
		}
	}
	protected function _remove_params($clause,$i=null,$params=null){
		if(isset($this->params[$clause])){
			if(!isset($i))
				$i = count($this->params[$clause])-1;
			if(isset($this->params[$clause][$i])&&(!isset($params)||$params==$this->params[$clause][$i])){
				if(isset($this->mysqli_types[$clause]))
					$this->mysqli_types[$clause] = substr($this->mysqli_types[$clause],count($this->params[$clause][$i])*-1);
				unset($this->params[$clause][$i]);
				array_merge($this->params[$clause]); //reorder
				return true;
			}
		}
	}
	function remove_property($k,$v,$params=null,$once=null){
		if($params===false){
			$params = null;
			$once = true;
		}
		foreach(array_keys($this->$k) as $i)
			if($this->{$k}[$i]==$v){
				$found = $this->_remove_params($k,$i,$params);
				if(!isset($params)||$found)
					unset($this->{$k}[$i]);
				if((isset($params)&&$found)||(!isset($params)&&$once))
					break;
			}
		return $this;
	}
	function unJoin($table,$params=null){
		return $this->remove_property('tables',$table,$params);
	}
	function unFrom($table,$params=null){
		return $this->remove_property('tables',$table,$params);
	}
	
	protected $columns = array();
	protected $tables = array();
	protected $params = array();
	protected $mysqli_types = array( );
	function add_table($table, array $params = null, $mysqli_types = "") {
		$this->tables = array_merge($this->tables, (array)$table);
		$this->_add_params('tables', $params, $mysqli_types);
		return $this;
	}
	function join($table, array $params = null, $mysqli_types = "") {
		return $this->add_table($table, $params, $mysqli_types);
	}
	function from($table, array $params = null, $mysqli_types = "") {
		return $this->add_table($table, $params, $mysqli_types);
	}
	protected function _add_params($clause, array $params = null, $mysqli_types = "") {
		if (isset($params)){
			if (!isset($this->params[$clause]))
				$this->params[$clause] = array();
			if (!empty($mysqli_types))
				$this->mysqli_types[$clause] .= $mysqli_types;
			$this->params[$clause][] = $params;
		}
		return $this;
	}
	protected function _get_params($order) {
		if (!is_array($order))
			$order = func_get_args();
		$params = array( );
		$mysqli_types = "";
		foreach ($order as $clause) {
			if(empty($this->params[$clause]))
				continue;
			foreach($this->params[$clause] as $p)
				$params = array_merge($params, $p);
			if(isset($this->mysqli_types[$clause]))
				$mysqli_types .= $this->mysqli_types[$clause];
		}
		if(!empty($this->mysqli_types))
			array_unshift($params, $mysqli_types);
		return $params;
	}
	abstract function getParams();
	function getQuery() {
		return $this->render();
	}
	abstract function render();
	function debug() {
		return $this->getQuery() . "\n\n" . print_r($this->getParams(), true);
	}
	protected static function _render_bool_expr(array $expression) {
		$str = "";
		$stack = array( );
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
}