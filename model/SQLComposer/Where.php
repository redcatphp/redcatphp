<?php namespace surikat\model\SQLComposer;
use surikat\model\SQLComposer;
abstract class Where extends Base {
	function unWhere($where=null,$params=null){
		return $this->remove_property('where',$where,$params);
	}
	function unWhere_in($where,$params=null){
		list($where, $params) = SQLComposer::in($where, $params);
		return $this->remove_property('where',$where,$params);
	}
	function unWhere_op($column, $op,  array $params=null){
		list($where, $params) = SQLComposer::applyOperator($column, $op, $params);
		return $this->remove_property('where',$where,$params);
	}
	function unOpen_where_and() {
		return $this->remove_property('where',[ '(', 'AND' ]);
	}
	function unOpen_where_or() {
		return $this->remove_property('where',[ '(', 'OR' ]);
	}
	function unOpen_where_not_and() {
		$this->remove_property('where',[ '(', 'NOT' ]);
		return $this->unOpen_where_and();
	}
	function unOpen_where_not_or() {
		$this->remove_property('where',[ '(', 'NOT' ]);
		return $this->unOpen_where_or();
	}
	function unClose_where() {
		return $this->remove_property('where',[')']);
	}
	
	protected $where = [ ];
	function where($where,  array $params = null, $mysqli_types = "") {
		$this->where[] = $where;
		$this->_add_params('where', $params, $mysqli_types);
		return $this;
	}
	function where_in($where,  array $params, $mysqli_types = "") {
		list($where, $params, $mysqli_types) = SQLComposer::in($where, $params, $mysqli_types);
		return $this->where($where, $params, $mysqli_types);
	}
	function where_op($column, $op,  array $params=null, $mysqli_types="") {
		list($where, $params, $mysqli_types) = SQLComposer::applyOperator($column, $op, $params, $mysqli_types);
		return $this->where($where, $params, $mysqli_types);
	}
	function open_where_and() {
		$this->where[] = [ '(', 'AND' ];
		return $this;
	}
	function open_where_or() {
		$this->where[] = [ '(', 'OR' ];
		return $this;
	}
	function open_where_not_and() {
		$this->where[] = ['(', 'NOT'];
		$this->open_where_and();
		return $this;
	}
	function open_where_not_or() {
		$this->where[] = [ '(', 'NOT' ];
		$this->open_where_or();
		return $this;
	}
	function close_where() {
		$this->where[] = [ ')' ];
		return $this;
	}
	protected function _render_where() {
		return Base::_render_bool_expr($this->where);
	}


}