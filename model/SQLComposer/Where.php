<?php namespace surikat\model\SQLComposer;
use surikat\model\SQLComposer;
abstract class Where extends Base {
	protected $where = array( );
	function where($where, array $params = null, $mysqli_types = "") {
		$this->where[] = $where;
		$this->_add_params('where', $params, $mysqli_types);
		return $this;
	}
	function where_in($where, array $params, $mysqli_types = "") {
		list($where, $params, $mysqli_types) = SQLComposer::in($where, $params, $mysqli_types);
		return $this->where($where, $params, $mysqli_types);
	}
	function where_op($column, $op, array $params=null, $mysqli_types="") {
		list($where, $params, $mysqli_types) = SQLComposer::applyOperator($column, $op, $params, $mysqli_types);
		return $this->where($where, $params, $mysqli_types);
	}
	function open_where_and() {
		$this->where[] = array( '(', 'AND' );
		return $this;
	}
	function open_where_or() {
		$this->where[] = array( '(', 'OR' );
		return $this;
	}
	function open_where_not_and() {
		$this->where[] = array('(', 'NOT');
		$this->open_where_and();
		return $this;
	}
	function open_where_not_or() {
		$this->where[] = array( '(', 'NOT' );
		$this->open_where_or();
		return $this;
	}
	function close_where() {
		$this->where[] = array( ')' );
		return $this;
	}
	protected function _render_where() {
		return SQLComposerBase::_render_bool_expr($this->where);
	}


}