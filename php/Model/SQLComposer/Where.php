<?php namespace Surikat\Model\SQLComposer;
use Surikat\Model\SQLComposer;
abstract class Where extends Base {
	function unWhere($where=null,$params=null){
		$this->remove_property('where',$where,$params);
		return $this;
	}
	function unWith($with=null,$params=null){
		$this->remove_property('with',$with,$params);
		return $this;
	}
	function unWhereIn($where,$params=null){
		list($where, $params) = SQLComposer::in($where, $params);
		$this->remove_property('where',$where,$params);
		return $this;
	}
	function unWhereOp($column, $op,  array $params=null){
		list($where, $params) = SQLComposer::applyOperator($column, $op, $params);
		$this->remove_property('where',$where,$params);
		return $this;
	}
	function unOpenWhereAnd() {
		$this->remove_property('where',[ '(', 'AND' ]);
		return $this;
	}
	function unOpenWhereOr() {
		$this->remove_property('where',[ '(', 'OR' ]);
		return $this;
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
		$this->remove_property('where',[')']);
		return $this;
	}
	
	protected $where = [];
	protected $with = [];
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
	protected function _render_where($removeUnbinded=true){
		$where = $this->where;
		if($removeUnbinded)
			$where = $this->removeUnbinded($where);
		return Base::_render_bool_expr($where);
	}


}