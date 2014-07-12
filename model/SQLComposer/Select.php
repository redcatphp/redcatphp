<?php namespace surikat\model\SQLComposer;
use surikat\model\SQLComposer;
class Select extends Where {
	protected $distinct = false;
	protected $offset = null;
	protected $group_by = array( );
	protected $with_rollup = false;
	protected $having = array( );
	protected $order_by = array( );
	protected $limit = null;
	function __construct($select = null, array $params = null, $mysqli_types = "") {
		if (isset($select)) {
			$this->select($select, $params, $mysqli_types);
		}
	}
	function select($select, array $params = null, $mysqli_types = "") {
		$this->columns = array_merge($this->columns, (array)$select);
		$this->_add_params('select', $params, $mysqli_types);
		return $this;
	}
	function distinct($distinct = true) {
		$this->distinct = (bool)$distinct;
		return $this;
	}
	function group_by($group_by, array $params = null, $mysqli_types = "") {
		$this->group_by = array_merge($this->group_by, (array)$group_by);
		$this->_add_params('group_by', $params, $mysqli_types);
		return $this;
	}
	function with_rollup($with_rollup = true) {
		$this->with_rollup = $with_rollup;
		return $this;
	}
	function order_by($order_by, array $params = null, $mysqli_types = "") {
		$this->order_by = array_merge($this->order_by, (array)$order_by);
		$this->_add_params('order_by', $params, $mysqli_types);
		return $this;
	}
	function limit($limit) {
		$this->limit = (int)$limit;
		return $this;
	}
	function offset($offset) {
		$this->offset = (int)$offset;
		return $this;
	}
	function having($having, array $params = null, $mysqli_types = "") {
		$this->having = array_merge($this->having, (array)$having);
		$this->_add_params('having', $params, $mysqli_types);
		return $this;
	}
	function having_in($having, array $params, $mysqli_types = "") {
		if (!is_string($having)) throw new SQLComposerException("Method having_in must be called with a string as first argument.");
		list($having, $params, $mysqli_types) = SQLComposer::in($having, $params, $mysqli_types);
		return $this->having($having, $params, $mysqli_types);
	}
	function having_op($column, $op, array $params=null, $mysqli_types="") {
		list($where, $params, $mysqli_types) = SQLComposer::applyOperator($column, $op, $params, $mysqli_types);
		return $this->having($where, $params, $mysqli_types);
	}
	function open_having_and() {
		$this->having[] = array( '(', 'AND' );
		return $this;
	}
	function open_having_or() {
		$this->having[] = array( '(', 'OR' );
		return $this;
	}
	function open_having_not_and() {
		$this->having[] = array( '(', 'NOT' );
		$this->open_having_and();
		return $this;
	}
	function open_having_not_or() {
		$this->having[] = array( '(', 'NOT' );
		$this->open_having_or();
		return $this;
	}
	function close_having() {
		$this->having[] = array( ')' );
		return $this;
	}
	protected function _render_having() {
		return SQLComposerBase::_render_bool_expr($this->having);
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