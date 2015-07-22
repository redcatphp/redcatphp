<?php
namespace RedBase\DataTable;
use RedBase\DataTable;
use RedBase\SqlComposer\Select;
class SQL extends DataTable{
	private $stmt;
	private $row;
	private $select;
	function __construct($name,$primaryKey='id',$uniqTextKey='uniq',$dataSource){
		parent::__construct($name,$primaryKey,$uniqTextKey,$dataSource);		
		$this->select = $this->createSelect();
	}
	function exists(){
		return $this->dataSource->tableExists($this->name);
	}
	function fetch(){
		return $this->dataSource->fetch($this->select->getQuery(),$this->select->getParams());
	}
	function getRow(){
		return $this->dataSource->getRow($this->select->getQuery(),$this->select->getParams());
	}
	function getAll(){
		$table = [];
		foreach($this->dataSource->getAll($this->select->getQuery(),$this->select->getParams()) as $row){
			if(isset($row[$this->primaryKey]))
				$table[$row[$this->primaryKey]] = $row;
			else
				$table[] = $row;
		}
		return $table;
	}
	function rewind(){
		if(!$this->exists())
			return;
		$this->stmt = $this->fetch();
		$this->next();
	}
	function current(){
		return $this->row;
	}
	function key(){
		if($this->row)
			return $this->row->{$this->primaryKey};
	}
	function valid(){
		return (bool)$this->row;
	}
	function next(){
		$this->row = $row = $this->stmt->fetch();
		if($row){
			$this->row = $this->dataSource->entityFactory($this->name);
			foreach($row as $k=>$v)
				$this->row->$k = $v;
			if($this->useCache)
				$this->data[$this->row->{$this->primaryKey}] = $this->row;
		}
	}
	function count(){
		return $this->countSimple();
	}
	function countSimple(){
		if(!$this->exists())
			return;
		$select = $this->select
			->getClone()
			->unOrderBy()
			->unSelect()
			->select('COUNT(*)')
		;
		return (int)$this->dataSource->getCell($select->getQuery(),$select->getParams());
	}
	function countNested(){
		if(!$this->exists())
			return;
		$select = $this->createSelect();
		$queryCount = $this->select
			->getClone()
			->unOrderBy()
			->unSelect()
			->select('id')
		;
		$select
			->select('COUNT(*)')
			->from('('.$queryCount->getQuery().') as TMP_count',$queryCount->getParams())
		;
		return (int)$this->dataSource->getCell($select->getQuery(),$select->getParams());
	}
	function countAll(){
		if(!$this->exists())
			return;
		$select = $this->createSelect();
		$select
			->select('COUNT(*)')
			->from($this->name)
		;
		return (int)$this->dataSource->getCell($select->getQuery(),$select->getParams());
	}
	function createSelect(){
		return new Select(
			$this->name,
			$this->dataSource->getQuoteCharacter(),
			$this->dataSource->getTablePrefix()
		);
	}
	function getClone(){
		return clone $this;
	}
	function __clone(){
		if(isset($this->select))
			$this->select = clone $this->select;
	}
	
	
	function tableJoin($table, $join, array $params = null){
		return $this->select->tableJoin($table, $join, $params);
	}
	function joinAdd($join,array $params = null){
		return $this->select->joinAdd($join, $params);
	}
	function join($join, array $params = null){
		return $this->select->join($join, $params);
	}
	function joinLeft($join, array $params = null){
		return $this->select->joinLeft($join, $params);
	}
	function joinRight($join, array $params = null){
		return $this->select->joinRight($join, $params);
	}
	function joinOn($join, array $params = null){
		return $this->select->joinOn($join, $params);
	}
	function from($table, array $params = null){
		return $this->select->from($table, $params);
	}
	function unTableJoin($table=null,$join=null,$params=null){
		return $this->select->unTableJoin($table,$join,$params);
	}
	function unJoin($join=null,$params=null){
		return $this->select->unJoin($join,$params);
	}
	function unFrom($table=null,$params=null){
		return $this->select->unFrom($table,$params);
	}
	function setParam($k,$v){
		return $this->select->set($k,$v);
	}
	function getParam($k){
		return $this->select->get($k);
	}
	function unWhere($where=null,$params=null){
		return $this->select->unWhere($where,$params);
	}
	function unWith($with=null,$params=null){
		return $this->select->unWith($with,$params);
	}
	function unWhereIn($where,$params=null){
		return $this->select->unWhereIn($where,$params);
	}
	function unWhereOp($column, $op,  array $params=null){
		return $this->select->unWhereOp($column, $op, $params);
	}
	function unOpenWhereAnd(){
		return $this->select->unOpenWhereAnd();
	}
	function unOpenWhereOr(){
		return $this->select->unOpenWhereOr();
	}
	function unOpenWhereNotAnd(){
		return $this->select->unOpenWhereNotAnd();
	}
	function unOpenWhereNotOr(){
		return $this->select->unOpenWhereNotOr();
	}
	function unCloseWhere(){
		return $this->select->unCloseWhere();
	}
	function where($where, array $params = null){
		return $this->select->where($where, $params);
	}
	function whereIn($where, array $params){
		return $this->select->whereIn($where, $params);
	}
	function whereOp($column, $op, array $params=null){
		return $this->select->whereOp($column, $op, $params);
	}
	function openWhereAnd(){
		return $this->select->openWhereAnd();
	}
	function openWhereOr(){
		return $this->select->openWhereOr();
	}
	function openWhereNotAnd(){
		return $this->select->openWhereNotAnd();
	}
	function openWhereNotOr(){
		return $this->select->openWhereNotOr();
	}
	function closeWhere(){
		return $this->select->closeWhere();
	}
	function with($with, array $params = null){
		return $this->select->with($with, $params);
	}
	function select($select, array $params = null){
		return $this->select->select($select, $params);
	}
	function distinct($distinct = true){
		return $this->select->distinct($distinct);
	}
	function groupBy($group_by, array $params = null){
		return $this->select->groupBy($group_by, $params);
	}
	function withRollup($with_rollup = true){
		return $this->select->withRollup($with_rollup);
	}
	function orderBy($order_by, array $params = null){
		return $this->select->orderBy($order_by, $params);
	}
	function sort($desc=false){
		return $this->select->sort($desc);
	}
	function limit($limit){
		return $this->select->limit($limit);
	}
	function offset($offset){
		return $this->select->offset($offset);
	}
	function having($having, array $params = null){
		return $this->select->having($having, $params);
	}
	function havingIn($having, array $params){
		return $this->select->havingIn($having, $params);
	}
	function havingOp($column, $op, array $params=null){
		return $this->select->havingOp($column, $op, $params);
	}
	function openHavingAnd(){
		return $this->select->openHavingAnd();
	}
	function openHavingOr(){
		return $this->select->openHavingOr();
	}
	function openHavingNotAnd(){
		return $this->select->openHavingNotAnd();
	}
	function openHavingNotOr(){
		return $this->select->openHavingNotOr();
	}
	function closeHaving(){
		return $this->select->closeHaving();
	}
	function unSelect($select=null, array $params = null){
		return $this->select->unSelect($select, $params);
	}
	function unDistinct(){
		return $this->select->unDistinct();
	}
	function unGroupBy($group_by=null, array $params = null){
		return $this->select->unGroupBy($group_by, $params);
	}
	function unWithRollup(){
		return $this->select->unWithRollup();
	}
	function unOrderBy($order_by=null, array $params = null){
		return $this->select->unOrderBy($order_by, $params);
	}
	function unSort(){
		return $this->select->unSort();
	}
	function unLimit(){
		return $this->select->unLimit();
	}
	function unOffset(){
		return $this->select->unOffset();
	}
	function unHaving($having=null, array $params = null){
		return $this->select->unHaving($having,  $params);
	}
	function unHavingIn($having, array $params){
		return $this->select->unHavingIn($having, $params);
	}
	function unHavingOp($column, $op, array $params=null){
		return $this->select->unHavingOp($column, $op,  $params);
	}
	function unOpenHavingAnd(){
		return $this->select->unOpenHavingAnd();
	}
	function unOpenHavingOr(){
		return $this->select->unOpenHavingOr();
	}
	function unOpenHavingNotAnd(){
		return $this->select->unOpenHavingNotAnd();
	}
	function unOpenHavingNotOr(){
		return $this->select->unOpenHavingNotOr();
	}
	function unCloseHaving(){
		return $this->select->unCloseHaving();
	}
	function hasColumn(){
		return $this->select->hasColumn();
	}
	function getColumn(){
		return $this->select->getColumn();
	}
	function hasTable(){
		return $this->select->hasTable();
	}
	function getTable(){
		return $this->select->getTable();
	}
	function hasJoin(){
		return $this->select->hasJoin();
	}
	function getJoin(){
		return $this->select->getJoin();
	}
	function hasFrom(){
		return $this->select->hasFrom();
	}
	function getFrom(){
		return $this->select->getFrom();
	}
	function hasWhere(){
		return $this->select->hasWhere();
	}
	function hasWith(){
		return $this->select->hasWith();
	}
	function getWhere(){
		return $this->select->getWhere();
	}
	function getWith(){
		return $this->select->getWith();
	}
	function hasSelect(){
		return $this->select->hasSelect();
	}
	function getSelect(){
		return $this->select->getSelect();
	}
	function hasDistinct(){
		return $this->select->hasDistinct();
	}
	function hasGroupBy(){
		return $this->select->hasGroupBy();
	}
	function getGroupBy(){
		return $this->select->getGroupBy();
	}
	function hasWithRollup(){
		return $this->select->hasWithRollup();
	}
	function hasHaving(){
		return $this->select->hasHaving();
	}
	function getHaving(){
		return $this->select->getHaving();
	}
	function hasOrderBy(){
		return $this->select->hasOrderBy();
	}
	function getOrderBy(){
		return $this->select->getOrderBy();
	}
	function hasSort(){
		return $this->select->hasSort();
	}
	function getSort(){
		return $this->select->getSort();
	}
	function hasLimit(){
		return $this->select->hasLimit();
	}
	function getLimit(){
		return $this->select->getLimit();
	}
	function hasOffset(){
		return $this->select->hasOffset();
	}
	function getOffset(){
		return $this->select->getOffset();
	}
}