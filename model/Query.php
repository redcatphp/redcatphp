<?php namespace surikat\model;
use surikat\control;
use surikat\model;
use surikat\model\SQLComposer\API as SQLComposer;
class Query {
	const FLAG_ACCENT_INSENSITIVE = 2;
	const FLAG_CASE_INSENSITIVE = 4;
	protected $writer;
	function __construct($writer=null){
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
	function prefixColWithTable($v,$table){
		if(strpos($v,'(')===false&&strpos($v,')')===false&&strpos($v,' as ')===false&&strpos($v,'.')===false)
			$v = $this->quote($table).'.'.$this->quote($v);
		return $v;
	}
	function buildQuery($table,$compo=array(),$method='select'){
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
						$select[] = $this->prefixColWithTable($sel,$table);
				else
					$select[] = $this->prefixColWithTable($v,$table);
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
			$methods['join'] = (isset($methods['join'])?implode((array)$methods['join']):'').implode((array)$this->joinOn($table,$methods['joinOn']));
			unset($methods['joinOn']);
		}
		$composer = stripos($method,'get')==0?'select':(($pos=strcspn($string,'ABCDEFGHJIJKLMNOPQRSTUVWXYZ'))!==false?substr($method,$pos):$method);
		if($composer=='select'){
			foreach(array_keys($select) as $i)
					$select[$i] = $this->prefixColWithTable($select[$i],$table);
		}
		if(isset($methods['from'])){
			$from = $methods['from'];
			unset($methods['from']);
		}
		else
			$from = $table;
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
	function query($table,$method,$compo=array(),$params=array()){
		$query = $this->buildQuery($table,$compo,$method);
		if(control::devHas(control::dev_model_sql))
			print('<pre>'.str_replace(',',",\r\n\t",htmlentities($query))."\r\n\t".print_r($params,true).'</pre>');
		if(R::getWriter()->tableExists($table))
			return R::$method($query,(array)$params);
	}

	function cellId($table,$label,$addCompo=null,$addParams=null){
		$i = is_integer($label);
		$compo = array('select'=>$i?'label':'id','where'=>($i?'id':'label').'=?');
		if(is_array($addCompo))
			$compo = array_merge($compo,$addCompo);
		$params = array($label);
		if(is_array($addParams))
			$params = array_merge($params,$addParams);
		return $this->cell($table,$compo,$params);
	}
	function cell($table,$compo=array(),$params=array()){
		return $this->query($table,'getCell',$compo,$params);
	}
	function column($table,$compo=array(),$params=array()){
		return $this->query($table,'getCol',$compo,$params);
	}
	function col($table,$compo=array(),$params=array()){
		$col = array();
		foreach($this->query($table,'getAll',$compo,$params) as $row){
			$id = $row['id'];
			unset($row['id']);
			$col[$id] = array_shift($row);
		}
		return $col;
	}
	function row($table,$compo=array(),$params=array()){
		return $this->query($table,'getRow',$compo,$params);
	}
	function table($table,$compo=array(),$params=array()){
		return $this->query($table,'getAll',$compo,$params);
	}
	function inSelectTable($in,$select,$table=null){
		foreach(array_keys($select) as $k)
			$select[$k] = trim($select[$k],'"');
		if(in_array($in,$select))
			return true;
		if(in_array($table.'.'.$in,$select))
			return true;
		return false;
	}
	function count4D($table,$compo=array(),$params=array()){
		$this->compoSelectIn4D($table,$compo);
		$compo['select'] = array($table.'.id');
		$q = $this->buildQuery($table,$compo);
		//$i = R::getCell('SELECT COUNT(*) FROM ('.$this->buildQuery($table,$compo).') as TMP_count',(array)$params);
		$i = $this->query($table,'getCell',array('select'=>'COUNT(*)','from'=>'('.$q.') as TMP_count'),(array)$params);
		return (int)$i;
	}
	function table4D($table,$compo=array(),$params=array()){
		if(empty($compo['select']))
			$compo['select'] = '*';
		$compo['select'] = (array)$compo['select'];
		if(!$this->inSelectTable('id',$compo['select'],$table)&&!$this->inSelectTable('*',$compo['select'],$table))
			$compo['select'][] = 'id';
		$this->compoSelectIn4D($table,$compo);
		$data = $this->query($table,'getAll',$compo,$params);
		$data = self::explodeGroupConcatMulti($data);
		if(control::devHas(control::dev_model_compo))
			print('<pre>'.htmlentities(print_r($data,true)).'</pre>');
		return $data;
	}
	function row4D($table,$compo=array(),$params=array()){
		if(empty($compo['select']))
			$compo['select'] = '*';
		$compo['select'] = (array)$compo['select'];
		if(!in_array('id',$compo['select'])&&!in_array($table.'.id',$compo['select']))
			$compo['select'][] = 'id';
		$this->compoSelectIn4D($table,$compo);
		$compo['limit'] = 1;
		$row = $this->query($table,'getRow',$compo,$params);
		$row = self::explodeGroupConcat($row);
		if(control::devHas(control::dev_model_compo))
			print('<pre>'.htmlentities(print_r($row,true)).'</pre>');
		return $row;
	}
	protected $listOfColumns = array();
	protected $heuristic4D;
	function listOfColumns($t,$details=null){
		if(!isset($this->listOfColumns[$t]))
			$this->listOfColumns[$t] = R::inspect($t);
		return $details?$this->listOfColumns[$t]:array_keys($this->listOfColumns[$t]);
	}
	function heuristic4D($t){
		if(!isset($this->heuristic4D[$t])){
			$listOfTables = R::inspect();
			$tableL = strlen($t);
			$h4D['fields'] = in_array($t,$listOfTables)?$this->listOfColumns($t):array();
			$h4D['shareds'] = array();
			$h4D['parents'] = array();
			$h4D['fieldsOwn'] = array();
			$h4D['owns'] = array();
			foreach($listOfTables as $table) //shared
				if(strpos($table,'_')!==false&&((strpos($table,$t)===0&&$table=substr($table,$tableL+1))
					||((strrpos($table,$t)===strlen($table)-$tableL)&&($table=substr($table,0,($tableL+1)*-1))))){
						$h4D['shareds'][] = $table;
						$h4D['fieldsShareds'][$table] = $this->listOfColumns($table);
				}
			foreach($h4D['fields'] as $field) //parent
				if(strrpos($field,'_id')===strlen($field)-3){
					$table = substr($field,0,-3);
					$h4D['parents'][] = $table;
				}
			foreach($listOfTables as $table){ //own
				if(strpos($table,'_')===false&&$table!=$t){
					$h4D['fieldsOwn'][$table] = $this->listOfColumns($table);
					if(in_array($t.'_id',$h4D['fieldsOwn'][$table]))
						$h4D['owns'][] = $table;
				}
			}
			$this->heuristic4D[$t] = $h4D;
		}
		return $this->heuristic4D[$t];
	}
	function compoSelectIn4D($table,&$compo){
		$q = $this->writerQuote;
		$agg = $this->writerAgg;
		$aggc = $this->writerAggCaster;
		$sep = $this->writerSeparator;
		$cc = $this->writerConcatenator;
		extract($this->heuristic4D($table));
		
		$comp = array();
		if(isset($compo['join'])){
			$comp['join'] = (array)@$compo['join'];
			unset($compo['join']);
		}
		
		$compo['select'] = (array)@$compo['select'];
		foreach($parents as $parent){
			foreach($this->listOfColumns($parent) as $col)
				$compo['select'][] = "{$q}{$parent}{$q}.{$q}{$col}{$q} as {$q}{$parent}<{$col}{$q}";
			$compo['join'][] = " LEFT OUTER JOIN {$q}{$parent}{$q} ON {$q}{$parent}{$q}.{$q}id{$q}={$q}{$table}{$q}.{$q}{$parent}_id{$q}";
			$compo['group_by'][] = $q.$parent.$q.'.'.$q.'id'.$q;
		}
		foreach($shareds as $share){
			foreach($fieldsShareds[$share] as $col)
				$compo['select'][] =  "{$agg}({$q}{$share}{$q}.{$q}{$col}{$q}{$aggc} {$sep} {$cc}) as {$q}{$share}<>{$col}{$q}";
			$rel = array($table,$share);
			sort($rel);
			$rel = implode('_',$rel);
			$compo['join'][] = " LEFT OUTER JOIN {$q}{$rel}{$q} ON {$q}{$rel}{$q}.{$q}{$table}_id{$q}={$q}{$table}{$q}.{$q}id{$q}";
			$compo['join'][] = " LEFT OUTER JOIN {$q}{$share}{$q} ON {$q}{$rel}{$q}.{$q}{$share}_id{$q}={$q}{$share}{$q}.{$q}id{$q}";
		}
		foreach($owns as $own){
			foreach($fieldsOwn[$own] as $col)
				if(strrpos($col,'_id')!==strlen($col)-3)
					$compo['select'][] = "{$agg}(COALESCE({$q}{$own}{$q}.{$q}{$col}{$q}{$aggc},''{$aggc}) {$sep} {$cc}) as {$q}{$own}>{$col}{$q}";
			$compo['join'][] = " LEFT OUTER JOIN {$q}{$own}{$q} ON {$q}{$own}{$q}.{$q}{$table}_id{$q}={$q}{$table}{$q}.{$q}id{$q}";
		}
		if(!(empty($parents)&&empty($shareds)&&empty($owns)))
			$compo['group_by'][] = $q.$table.$q.'.'.$q.'id'.$q;

		if(isset($comp['join']))
			foreach($comp['join'] as $c)
				$compo['join'][] = $c;
		
	}
	function joinOn($table,$share){
		if(is_array($share)){
			$r = array();
			foreach($share as $k=>$v)
				$r[$k] = $this->joinOn($table,$v);
			return $r;
		}
		$rel = array($table,$share);
		sort($rel);
		$rel = implode('_',$rel);
		$q = $this->writerQuote;
		return "LEFT OUTER JOIN {$q}{$rel}{$q} ON {$q}{$rel}{$q}.{$q}{$table}_id{$q}={$q}{$table}{$q}.{$q}id{$q}
				LEFT OUTER JOIN {$q}{$share}{$q} ON {$q}{$rel}{$q}.{$q}{$share}_id{$q}={$q}{$share}{$q}.{$q}id{$q}";
	}
	function count($table,$compo=array(),$params=null){
		//return (int)R::getCell('SELECT COUNT(*) FROM ('.$this->buildQuery($table,$compo).') as TMP_count',(array)$params);
		return (int)$this->query($table,'getCell',array('select'=>'COUNT(*)','from'=>'('.$this->buildQuery($table,$compo).') as TMP_count'),(array)$params);
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
	
}