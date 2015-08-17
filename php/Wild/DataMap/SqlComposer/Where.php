<?php
namespace Wild\DataMap\SqlComposer;
abstract class Where extends Base {
	protected $where = [];
	protected $with = [];
	function hasWhere(){
		return !empty($this->where);
	}
	function hasWith(){
		return !empty($this->with);
	}
	function getWhere(){
		return $this->where;
	}
	function getWith(){
		return $this->with;
	}
	function unWhere($where=null,$params=null){
		$this->remove_property('where',$where,$params);
		return $this;
	}
	function unWith($with=null,$params=null){
		$this->remove_property('with',$with,$params);
		return $this;
	}
	function unWhereIn($where,$params=null){
		list($where, $params) = self::in($where, $params);
		$this->remove_property('where',$where,$params);
		return $this;
	}
	function unWhereOp($column, $op, array $params=null){
		list($where, $params) = self::applyOperator($column, $op, $params);
		$this->remove_property('where',$where,$params);
		return $this;
	}
	function unOpenWhereAnd() {
		$this->remove_property('where',['(', 'AND']);
		return $this;
	}
	function unOpenWhereOr() {
		$this->remove_property('where',['(', 'OR']);
		return $this;
	}
	function unOpenWhereNotAnd() {
		$this->remove_property('where',['(', 'NOT']);
		return $this->unOpenWhereAnd();
	}
	function unOpenWhereNotOr() {
		$this->remove_property('where',['(', 'NOT']);
		return $this->unOpenWhereOr();
	}
	function unCloseWhere() {
		$this->remove_property('where',[')']);
		return $this;
	}
	function where($where,  array $params = null) {
		$this->where[] = $where;
		$this->_add_params('where', $params);
		return $this;
	}
	function whereIn($where,  array $params) {
		list($where, $params) = self::in($where, $params);
		return $this->where($where, $params);
	}
	function whereOp($column, $op,  array $params=null) {
		list($where, $params) = self::applyOperator($column, $op, $params);
		return $this->where($where, $params);
	}
	function openWhereAnd() {
		$this->where[] = ['(', 'AND'];
		return $this;
	}
	function openWhereOr() {
		$this->where[] = ['(', 'OR'];
		return $this;
	}
	function openWhereNotAnd() {
		$this->where[] = ['(', 'NOT'];
		$this->openWhereAnd();
		return $this;
	}
	function openWhereNotOr() {
		$this->where[] = ['(', 'NOT'];
		$this->openWhereOr();
		return $this;
	}
	function closeWhere() {
		if(is_array($e=end($this->where))&&count($e)>1)
			array_pop($this->where);
		else
			$this->where[] = [')'];
		return $this;
	}
	function with($with,  array $params = null) {
		$this->with[] = $with;
		$this->_add_params('with', $params);
		return $this;
	}
	protected function _render_where($removeUnbinded=true){
		$where = $this->where;
		if($removeUnbinded)
			$where = $this->removeUnbinded($where);
		return self::render_bool_expr($where);
	}
}