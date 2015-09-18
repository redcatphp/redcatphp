<?php
namespace Wild\DataMap\SqlComposer;
class Select extends Where {
	protected $distinct = false;
	protected $group_by = [];
	protected $with_rollup = false;
	protected $having = [];
	protected $order_by = [];
	protected $sort = [];
	protected $limit;
	protected $offset;
	function __construct($mainTable = null,$quoteCharacter = '"', $tablePrefix = ''){
		parent::__construct($mainTable,$quoteCharacter, $tablePrefix);
	}
	
	function hasSelect(){
		return $this->hasColumn();
	}
	function getSelect(){
		return $this->getColumn();
	}
	function hasDistinct(){
		return $this->distinct;
	}
	function hasGroupBy(){
		return !empty($this->group_by);
	}
	function getGroupBy(){
		return $this->group_by;
	}
	function hasWithRollup(){
		return $this->with_rollup;
	}
	function hasHaving(){
		return !empty($this->having);
	}
	function getHaving(){
		return $this->having;
	}
	function hasOrderBy(){
		return !empty($this->order_by);
	}
	function getOrderBy(){
		return $this->order_by;
	}
	function hasSort(){
		return !empty($this->sort);
	}
	function getSort(){
		return $this->sort;
	}
	function hasLimit(){
		return !empty($this->limit);
	}
	function getLimit(){
		return $this->limit;
	}
	function hasOffset(){
		return isset($this->offset);
	}
	function getOffset(){
		return $this->offset;
	}
	
	function select($select,  array $params = null){
		foreach((array)$select as $s){
			if(!empty($params)||!in_array($s,$this->columns))
				$this->columns[] = $s;
		}
		$this->_add_params('select', $params);
		return $this;
	}
	function distinct($distinct = true) {
		$this->distinct = (bool)$distinct;
		return $this;
	}
	function groupBy($group_by,  array $params = null) {
		if(!empty($params)||!in_array($group_by,$this->group_by))
			$this->group_by[] = $group_by;
		$this->_add_params('group_by', $params);
		return $this;
	}
	function withRollup($with_rollup = true) {
		$this->with_rollup = $with_rollup;
		return $this;
	}
	function orderBy($order_by,  array $params = null) {
		if(!empty($params)||!in_array($order_by,$this->order_by))
			$this->order_by[] = $order_by;
		$this->_add_params('order_by', $params);
		return $this;
	}
	function sort($desc=false) {
		if(is_string($desc))
			$desc = strtoupper($desc);
		$this->sort[] = ($desc&&$desc!='ASC')||$desc=='DESC'?'DESC':'ASC';
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
	function having($having,  array $params = null) {
		$this->having = array_merge($this->having, (array)$having);
		$this->_add_params('having', $params);
		return $this;
	}
	function havingIn($having,  array $params) {
		if (!is_string($having)) throw new Exception("Method having_in must be called with a string as first argument.");
		list($having, $params) = self::in($having, $params);
		return $this->having($having, $params);
	}
	function havingOp($column, $op,  array $params=null) {
		list($where, $params) = self::applyOperator($column, $op, $params);
		return $this->having($where, $params);
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
	function unSelect($select=null,  array $params = null){
		$this->remove_property('columns',$select,$params);
		return $this;
	}
	function unDistinct(){
		$this->distinct = false;
		return $this;
	}
	function unGroupBy($group_by=null,  array $params = null){
		$this->remove_property('group_by',$group_by,$params);
		return $this;
	}
	function unWithRollup(){
		$this->with_rollup = false;
		return $this;
	}
	function unOrderBy($order_by=null,  array $params = null){
		$i = $this->remove_property('order_by',$order_by,$params);
		if(isset($this->sort[$i]))
			unset($this->sort[$i]);
		return $this;
	}
	function unSort(){
		array_pop($this->sort);
		return $this;
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
		$this->remove_property('having',$having,$params);
		return $this;
	}
	function unHavingIn($having,  array $params){
		if (!is_string($having)) throw new Exception("Method having_in must be called with a string as first argument.");
		list($having, $params) = self::in($having, $params);
		return $this->unHaving($having, $params);
	}
	function unHavingOp($column, $op,  array $params=null){
		list($where, $params) = self::applyOperator($column, $op, $params);
		return $this->unHaving($where, $params);
	}
	function unOpenHavingAnd() {
		$this->remove_property('having',[ '(', 'AND' ]);
		return $this;
	}
	function unOpenHavingOr() {
		$this->remove_property('having',[ '(', 'OR' ]);
		return $this;
	}
	function unOpenHavingNotAnd() {
		$this->remove_property('having',[ '(', 'NOT' ]);
		$this->unOpenHavingAnd();
		return $this;
	}
	function unOpenHavingNotOr() {
		$this->remove_property('having',[ '(', 'NOT' ]);
		$this->unOpenHavingOr();
		return $this;
	}
	function unCloseHaving() {
		$this->remove_property('having',[ ')' ]);
		return $this;
	}
	protected function _render_having($removeUnbinded=true){
		$having = $this->having;
		if($removeUnbinded)
			$having = $this->removeUnbinded($having);
		return self::render_bool_expr($having);
	}
	function render($removeUnbinded=true) {
		$with = empty($this->with) ? '' : 'WITH '.implode(', ', $this->with); //Postgresql specific
		$columns = empty($this->columns) ? '*' : implode(', ', $this->columns);
		$distinct = $this->distinct ? 'DISTINCT' : "";
		$from = '';
		$tables = [];
		$joins = [];
		$mt = $this->getMainTable();
		foreach($this->tables as $t){
			if(!is_array($t))
				$tables[] = $t;
			elseif(isset($t[1]))
				$joins[$t[0]][] = $t[1];
			elseif($mt)
				$joins[$mt][] = $t[0];
			else
				$joins[] = $t[0];
		}
		foreach($tables as $t){
			$from .= '';
			if(strpos($t,'(')===false&&strpos($t,')')===false&&strpos($t,' ')===false&&strpos($t,$this->quoteCharacter)===false)
				$from .= $this->quote($this->tablePrefix.$t);
			else
				$from .= $t;
			if(isset($joins[$t])){
				foreach($joins[$t] as $j){
					$from .= ' '.$j;
				}
				unset($joins[$t]);
			}
			$from .= ',';
		}
		$from = rtrim($from,',');
		foreach($joins as $k=>$j){
			if(is_array($j)){
				foreach($j as $jj){
					$from .= ' '.$jj;
				}
			}
			else{
				$from .= ' '.$j;
			}
		}
		
		$from = "FROM ".$from;
		$where = $this->_render_where($removeUnbinded);
		if(!empty($where))
			$where =  "WHERE $where";
		$group_by = empty($this->group_by) ? "" : "GROUP BY " . implode(", ", $this->group_by);
		$order_by = '';
		if(!empty($this->order_by)){
			$order_by .= "ORDER BY ";
			foreach($this->order_by as $i=>$gb){
				$order_by .= $gb;
				if(isset($this->sort[$i]))
					$order_by .= ' '.$this->sort[$i];
				$order_by .= ',';
			}
			$order_by = rtrim($order_by,',');
		}
		$with_rollup = $this->with_rollup ? "WITH ROLLUP" : "";
		$having = empty($this->having) ? "" : "HAVING " . $this->_render_having($removeUnbinded);
		$limit = "";
		if ($this->limit) {
			$limit = 'LIMIT '.$this->limit;
			if ($this->offset) {
				$limit .= ' OFFSET '.$this->offset;
			}
		}
		return "{$with} SELECT {$distinct} {$columns} {$from} {$where} {$group_by} {$with_rollup} {$having} {$order_by} {$limit}";
	}
	function getParams() {
		return $this->_get_params('with','select', 'tables', 'where', 'group_by', 'having', 'order_by');
	}
}