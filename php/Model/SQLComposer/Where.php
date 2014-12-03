<?php namespace Surikat\Model\SQLComposer;
use Surikat\Model\SQLComposer;
abstract class Where extends Base {
	function unWhere($where=null,$params=null){
		return $this->remove_property('where',$where,$params);
	}
	function unWith($with=null,$params=null){
		return $this->remove_property('with',$with,$params);
	}
	function unWhereIn($where,$params=null){
		list($where, $params) = SQLComposer::in($where, $params);
		return $this->remove_property('where',$where,$params);
	}
	function unWhereOp($column, $op,  array $params=null){
		list($where, $params) = SQLComposer::applyOperator($column, $op, $params);
		return $this->remove_property('where',$where,$params);
	}
	function unOpenWhereAnd() {
		return $this->remove_property('where',[ '(', 'AND' ]);
	}
	function unOpenWhereOr() {
		return $this->remove_property('where',[ '(', 'OR' ]);
	}
	function unOpenWhereNotAnd() {
		$this->remove_property('where',[ '(', 'NOT' ]);
		return $this->unOpenWhereAnd();
	}
	function unOpenWhereNotOr() {
		$this->remove_property('where',[ '(', 'NOT' ]);
		return $this->unOpenWhereOr();
	}
	function unCloseWhere() {
		return $this->remove_property('where',[')']);
	}
	
	protected $where = [ ];
	protected $with = [ ];
	function where($where,  array $params = null, $mysqli_types = "") {
		$this->where[] = $where;
		$this->_add_params('where', $params, $mysqli_types);
		return $this;
	}
	function whereIn($where,  array $params, $mysqli_types = "") {
		list($where, $params, $mysqli_types) = SQLComposer::in($where, $params, $mysqli_types);
		return $this->where($where, $params, $mysqli_types);
	}
	function whereOp($column, $op,  array $params=null, $mysqli_types="") {
		list($where, $params, $mysqli_types) = SQLComposer::applyOperator($column, $op, $params, $mysqli_types);
		return $this->where($where, $params, $mysqli_types);
	}
	function openWhereAnd() {
		$this->where[] = [ '(', 'AND' ];
		return $this;
	}
	function openWhereOr() {
		$this->where[] = [ '(', 'OR' ];
		return $this;
	}
	function openWhereNotAnd() {
		$this->where[] = ['(', 'NOT'];
		$this->openWhereAnd();
		return $this;
	}
	function openWhereNotOr() {
		$this->where[] = [ '(', 'NOT' ];
		$this->openWhereOr();
		return $this;
	}
	function closeWhere() {
		$this->where[] = [ ')' ];
		return $this;
	}
	function with($with,  array $params = null, $mysqli_types = "") {
		$this->with[] = $with;
		$this->_add_params('with', $params, $mysqli_types);
		return $this;
	}
	protected function _render_where() {
		return Base::_render_bool_expr($this->where);
	}


}