<?php namespace surikat\model;
use surikat\control;
use surikat\model;
use surikat\model\SQLComposer\API as SQLComposer;
class Query {
	const FLAG_ACCENT_INSENSITIVE = 2;
	const FLAG_CASE_INSENSITIVE = 4;
	protected $writer;
	protected $table;
	function __construct($table,$writer=null){
		$this->table = $table;
		if(!$writer)
			$writer = R::getWriter();
		$this->writer = $writer;
	}
	function quote($v){
		if($v=='*')
			return $v;
		$q = $this->writerQuote;
		return $q.trim($v,$q).$q;
	}
	function formatColumnName($v){
		if(strpos($v,'(')===false&&strpos($v,')')===false&&strpos($v,' as ')===false&&strpos($v,'.')===false)
			$v = $this->quote($this->table).'.'.$this->quote($v);
		return $v;
	}
	function buildQuery($compo=array(),$method='select'){
		$select = array();
		$methods = array();
		if(is_string($compo))
			$compo = explode(',',$compo);
		foreach((array)$compo as $k=>$v){
			if(empty($v))
				continue;
			if(is_integer($k)||$k=='select'){
				if(is_array($v))
					foreach($v as $sel)
						$select[] = $this->formatColumnName($sel);
				else
					$select[] = $this->formatColumnName($v);
			}
			else
				$methods[$k] = $v;
		}
		if(isset($methods['joinWhere'])){
			if(!empty($methods['joinWhere'])){
				$hc = $this->writerSumCaster;
				$hs = implode(' AND ',(array)$methods['joinWhere']);
				if($hc)
					$hs = '('.$hs.')'.$hc;
					$methods['having'][] = 'SUM('.$hs.')>0';
				unset($methods['joinWhere']);
			}
		}
		if(isset($methods['joinOn'])){
			$methods['join'] = (isset($methods['join'])?implode((array)$methods['join']):'').implode((array)$this->joinOn($methods['joinOn']));
			unset($methods['joinOn']);
		}
		$composer = stripos($method,'get')==0?'select':(($pos=strcspn($string,'ABCDEFGHJIJKLMNOPQRSTUVWXYZ'))!==false?substr($method,$pos):$method);
		if($composer=='select'){
			foreach(array_keys($select) as $i)
				$select[$i] = $this->formatColumnName($select[$i]);
		}
		if(isset($methods['from'])){
			$from = $methods['from'];
			unset($methods['from']);
		}
		else
			$from = $this->table;
		if(strpos($from,'(')===false&&strpos($from,')')===false)
			$from = $this->quote($from);
		if(control::devHas(control::dev_model_compo))
			print('<pre>'.htmlentities(print_r($compo,true)).'</pre>');
		$sqlc = SQLComposer::$composer($select)->from($from);
		foreach($methods as $k=>$v){
			switch($k){
				case 'where':
					$v = implode(' AND ',(array)$v);
				break;
			}
			$sqlc->$k($v);
		}
		return $sqlc->getQuery();
	}
	function query($method,$compo=array(),$params=array()){
		$query = $this->buildQuery($compo,$method);
		if(control::devHas(control::dev_model_sql))
			print('<pre>'.str_replace(',',",\r\n\t",htmlentities($query))."\r\n\t".print_r($params,true).'</pre>');
		if(R::getWriter()->tableExists($this->table))
			return R::$method($query,(array)$params);
	}

	function cellId($label,$addCompo=null,$addParams=null){
		$i = is_integer($label);
		$compo = array('select'=>$i?'label':'id','where'=>($i?'id':'label').'=?');
		if(is_array($addCompo))
			$compo = array_merge($compo,$addCompo);
		$params = array($label);
		if(is_array($addParams))
			$params = array_merge($params,$addParams);
		return $this->cell($compo,$params);
	}
	function cell($compo=array(),$params=array()){
		return $this->query('getCell',$compo,$params);
	}
	function column($compo=array(),$params=array()){
		return $this->query('getCol',$compo,$params);
	}
	function col($compo=array(),$params=array()){
		$col = array();
		foreach($this->query('getAll',$compo,$params) as $row){
			$id = $row['id'];
			unset($row['id']);
			$col[$id] = array_shift($row);
		}
		return $col;
	}
	function row($compo=array(),$params=array()){
		return $this->query('getRow',$compo,$params);
	}
	function table($compo=array(),$params=array()){
		return $this->query('getAll',$compo,$params);
	}
	
	protected $listOfColumns = array();
	function listOfColumns($t,$details=null,$reload=null){
		if(!isset($this->listOfColumns[$t])||$reload)
			$this->listOfColumns[$t] = R::inspect($t);
		return $details?$this->listOfColumns[$t]:array_keys($this->listOfColumns[$t]);
	}
	
	function joinOn($share){
		if(is_array($share)){
			$r = array();
			foreach($share as $k=>$v)
				$r[$k] = $this->joinOn($this->table,$v);
			return $r;
		}
		$rel = array($this->table,$share);
		sort($rel);
		$rel = implode('_',$rel);
		$q = $this->writerQuote;
		return "LEFT OUTER JOIN {$q}{$rel}{$q} ON {$q}{$rel}{$q}.{$q}{$this->table}_id{$q}={$q}{$this->table}{$q}.{$q}id{$q}
				LEFT OUTER JOIN {$q}{$share}{$q} ON {$q}{$rel}{$q}.{$q}{$share}_id{$q}={$q}{$share}{$q}.{$q}id{$q}";
	}
	function count($compo=array(),$params=null){
		return (int)$this->query('getCell',array('select'=>'COUNT(*)','from'=>'('.$this->buildQuery($compo).') as TMP_count'),(array)$params);
	}


	//magic decouplers accessors and setters
	function __get($k){
		if(strpos($k,'writer')===0&&ctype_upper(substr($k,6,1))){
			$m =  'get'.substr($k,6);
			return $this->writer->$m();
		}
	}
	function __set($k,$v){
		
	}
	
	//public helpers api
	static function multiSlots(){
		$args = func_get_args();
		$query = array_shift($args);
		$x = explode('?',$query);
		$q = array_shift($x);
		for($i=0;$i<count($x);$i++){
			foreach($args[$i] as $k=>$v)
				$q .= (is_integer($k)?'?':':'.ltrim($k,':')).',';
			$q = rtrim($q,',').$x[$i];
		}
		return $q;
	}
	static function explodeGroupConcat($data){
		$_gs = chr(0x1D);
		$row = array();
		foreach(array_keys($data) as $col){
			$multi = stripos($col,'>');
			$sep = stripos($col,'<>')?'<>':(stripos($col,'<')?'<':($multi?'>':false));
			if($sep){
				$x = explode($sep,$col);
				$tb = &$x[0];
				$_col = &$x[1];
				if(!isset($row[$tb]))
					$row[$tb] = array();
				if(empty($data[$col])){
					if(!isset($row[$tb]))
						$row[$tb] = array();
				}
				elseif($multi){
					$_idx = explode($_gs,$data[$tb.$sep.'id']);
					$_x = explode($_gs,$data[$col]);
					foreach($_idx as $_i=>$_id)
						if(!empty($_id))
							$row[$tb][$_id][$_col] = isset($_x[$_i])?$_x[$_i]:null;
				}
				else
					$row[$tb][$_col] = $data[$col];
			}
			else
				$row[$col] = $data[$col];
		}
		return $row;
	}
	static function explodeGroupConcatMulti($data){
		$table = array();
		if(is_array($data)||$data instanceof \ArrayAccess)
			foreach($data as $i=>$d){
				$id = isset($d['id'])?$d['id']:$i;
				$table[$id] = self::explodeGroupConcat($d);
			}
		return $table;
	}	
	static function inSelectTable($in,$select,$table=null){
		foreach(array_keys($select) as $k)
			$select[$k] = trim($select[$k],'"');
		if(in_array($in,$select))
			return true;
		if(in_array($table.'.'.$in,$select))
			return true;
		return false;
	}
}