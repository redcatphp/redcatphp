<?php namespace surikat\model\SQLComposer;
use surikat\model\SQLComposer;
class Select extends Where {
	function unSelect($select=null,  array $params = null){
		return $this->remove_property('columns',$select,$params);
	}
	function unDistinct(){
		$this->distinct = false;
		return $this;
	}
	function unGroupBy($group_by=null,  array $params = null){
		return $this->remove_property('group_by',$group_by,$params);
	}
	function unWithRollup(){
		$this->with_rollup = false;
		return $this;
	}
	function unOrderBy($order_by=null,  array $params = null){
		return $this->remove_property('order_by',$order_by,$params);
	}
	function unLimit() {
		$this->limit = null;
		return $this;
	}
	function unOffset(){
		$this->offset = null;
		return $this;
	}
	function unHaving($having=null,  array $params = null){
		return $this->remove_property('having',$having,$params);
	}
	function unHavingIn($having,  array $params){
		if (!is_string($having)) throw new SQLComposerException("Method having_in must be called with a string as first argument.");
		list($having, $params) = SQLComposer::in($having, $params);
		return $this->unHaving($having, $params);
	}
	function unHavingOp($column, $op,  array $params=null){
		list($where, $params, $mysqli_types) = SQLComposer::applyOperator($column, $op, $params);
		return $this->unHaving($where, $params);
	}
	function unOpenHavingAnd() {
		return $this->remove_property('having',[ '(', 'AND' ]);
	}
	function unOpenHavingOr() {
		return $this->remove_property('having',[ '(', 'OR' ]);
	}
	function unOpenHavingNotAnd() {
		$this->remove_property('having',[ '(', 'NOT' ]);
		$this->unOpen_having_and();
		return $this;
	}
	function unOpen_having_not_or() {
		$this->remove_property('having',[ '(', 'NOT' ]);
		$this->unOpen_having_or();
		return $this;
	}
	function unCloseHaving() {
		return $this->remove_property('having',[ ')' ]);
	}
	
	
	protected $distinct = false;
	protected $offset = null;
	protected $group_by = [ ];
	protected $with_rollup = false;
	protected $having = [ ];
	protected $order_by = [ ];
	protected $limit = null;
	function __construct($select = null,  array $params = null, $mysqli_types = "") {
		if (isset($select))
			$this->select($select, $params, $mysqli_types);
	}
	function select($select,  array $params = null, $mysqli_types = "") {
		foreach((array)$select as $s){
			if(!empty($params)||!in_array($s,$this->columns))
				$this->columns[] = $s;
		}
		$this->_add_params('select', $params, $mysqli_types);
		return $this;
	}
	function distinct($distinct = true) {
		$this->distinct = (bool)$distinct;
		return $this;
	}
	function groupBy($group_by,  array $params = null, $mysqli_types = "") {
		if(!empty($params)||!in_array($group_by,$this->group_by))
			$this->group_by[] = $group_by;
		$this->_add_params('group_by', $params, $mysqli_types);
		return $this;
	}
	function withRollup($with_rollup = true) {
		$this->with_rollup = $with_rollup;
		return $this;
	}
	function orderBy($order_by,  array $params = null, $mysqli_types = "") {
		if(!empty($params)||!in_array($order_by,$this->order_by))
			$this->order_by[] = $order_by;
		$this->_add_params('order_by', $params, $mysqli_types);
		return $this;
	}
	function limit($limit){
		$this->limit = (int)$limit;
		if(func_num_args()>1)
			$this->offset(func_get_arg(1));
		return $this;
	}
	function offset($offset) {
		$this->offset = (int)$offset;
		return $this;
	}
	function having($having,  array $params = null, $mysqli_types = "") {
		$this->having = array_merge($this->having, (array)$having);
		$this->_add_params('having', $params, $mysqli_types);
		return $this;
	}
	function havingIn($having,  array $params, $mysqli_types = "") {
		if (!is_string($having)) throw new SQLComposerException("Method having_in must be called with a string as first argument.");
		list($having, $params, $mysqli_types) = SQLComposer::in($having, $params, $mysqli_types);
		return $this->having($having, $params, $mysqli_types);
	}
	function havingOp($column, $op,  array $params=null, $mysqli_types="") {
		list($where, $params, $mysqli_types) = SQLComposer::applyOperator($column, $op, $params, $mysqli_types);
		return $this->having($where, $params, $mysqli_types);
	}
	function openHavingAnd() {
		$this->having[] = [ '(', 'AND' ];
		return $this;
	}
	function openHavingOr() {
		$this->having[] = [ '(', 'OR' ];
		return $this;
	}
	function openHavingNotAnd() {
		$this->having[] = [ '(', 'NOT' ];
		$this->openHavingAnd();
		return $this;
	}
	function openHavingNotOr() {
		$this->having[] = [ '(', 'NOT' ];
		$this->openHavingOr();
		return $this;
	}
	function closeHaving() {
		if(is_array($e=end($this->having))&&count($e)>1)
			array_pop($this->having);
		else
			$this->having[] = [ ')' ];
		return $this;
	}
	protected function _render_having() {
		return Base::_render_bool_expr($this->having);
	}
	function render() {
		$columns = empty($this->columns) ? "*" : implode(", ", $this->columns);
		$distinct = $this->distinct ? "DISTINCT" : "";
		$from = "\nFROM " . implode("\n\t", $this->tables);
		$where = empty($this->where) ? "" : "\nWHERE " . $this->_render_where();
		$group_by = empty($this->group_by) ? "" : "\nGROUP BY " . implode(", ", $this->group_by);
		$with_rollup = $this->with_rollup ? "WITH ROLLUP" : "";
		$having = empty($this->having) ? "" : "\nHAVING " . $this->_render_having();
		$order_by = empty($this->order_by) ? "" : "\nORDER BY " . implode(", ", $this->order_by);
		$limit = "";
		if ($this->limit) {
			$limit = "\nLIMIT {$this->limit}";
			if ($this->offset) {
				$limit .= "\nOFFSET {$this->offset}";
			}
		}
		return "SELECT {$distinct} {$columns} {$from} {$where} {$group_by} {$with_rollup} {$having} {$order_by} {$limit}";
	}
	function getParams() {
		return $this->_get_params('select', 'tables', 'where', 'group_by', 'having', 'order_by');
	}

}