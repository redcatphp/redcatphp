<?php namespace surikat\model;
use ArrayAccess;
use surikat\control;
use surikat\control\str;
use surikat\model;
use surikat\model\SQLComposer\SQLComposer;
class Query /* implements ArrayAccess */{
	const FLAG_ACCENT_INSENSITIVE = 2;
	const FLAG_CASE_INSENSITIVE = 4;
	protected $table;
	protected $writer;
	protected $composer;
	function __construct($table,$writer=null,$composer='select'){
		$this->table = $table;
		if(!$writer)
			$writer = R::getWriter();
		$this->writer = $writer;
		if(is_string($composer))
			$composer = SQLComposer::$composer();
		$this->composer = $composer;
		$this->from($table);
	}
	function __get($k){
		if(strpos($k,'writer')===0&&ctype_upper(substr($k,6,1))&&($key=lcfirst(substr($k,6))))
			return $this->writer->$key;
	}
	function __set($k,$v){
		
	}
	function __clone(){
        $this->composer = clone $this->composer;
    }
	function __call($f,$args){
		if(strpos($f,'un')===0&&ctype_upper(substr($f,3,1))){
			$k = substr($f,2);
			unset($this->composer->$k);
		}
		elseif(strpos($f,'get')===0&&ctype_upper(substr($f,4,1))){
			if(R::getWriter()->tableExists($this->table)){
				$params = $this->composer->getParams();
				if(is_array($paramsX=array_shift($args)))
					$params = array_merge($params,$args);
				return R::$f($this->composer->getQuery(),$params);
			}
		}
		else{
			if(method_exists($this->composer,$f)){
				$sql = array_shift($args);
				$binds = array_shift($args);
				if($sql instanceof SQLComposerBase){
					if(is_array($binds))
						$binds = array_merge($sql->getParams(),$binds);
					else
						$binds = $sql->getParams();
					$sql = $sql->getQuery();
				}
				$args = self::nestBinding($sql,$binds);
				call_user_func_array(array($this->composer,$f),$args);
				return $this;
			}
		}
	}
	function joinOn($on){
		$this->join(implode((array)$this->joinOnSQL($on)));
	}
	function joinWhere($w){
		if(empty($w))
			return;
		$hc = $this->writerSumCaster;
		$hs = implode(' AND ',(array)$w);
		if($hc)
			$hs = '('.$hs.')'.$hc;
			$this->having('SUM('.$hs.')>0');
	}
	function select(){
		$args = func_get_args();
		if(is_array($args[0]))
			foreach($args[0] as &$s)
				$s = $this->formatColumnName($s);
		else
			$args[0] = $this->formatColumnName($args[0]);
		$this->__call(__FUNCTION__,$args);
	}
	function from(){
		$args = func_get_args();
		if(strpos($args[0],'(')===false&&strpos($args[0],')')===false)
			$args[0] = $this->quote($args[0]);
		$this->__call(__FUNCTION__,$args);
	}
	function where($w){
		$args = func_get_args();
		if(is_array($args[0]))
			$args[0] = implode(' AND ',$args[0]);
		$this->__call(__FUNCTION__,$args);
	}
	function quote($v){
		if($v=='*')
			return $v;
		return $this->writerQuoteCharacter.trim($v,$this->writerQuoteCharacter).$this->writerQuoteCharacter;
	}
	function formatColumnName($v){
		if(strpos($v,'(')===false&&strpos($v,')')===false&&strpos($v,' as ')===false&&strpos($v,'.')===false)
			$v = $this->quote($this->table).'.'.$this->quote($v);
		return $v;
	}	
	protected $listOfColumns = array();
	function listOfColumns($t,$details=null,$reload=null){
		if(!isset($this->listOfColumns[$t])||$reload)
			$this->listOfColumns[$t] = R::inspect($t);
		return $details?$this->listOfColumns[$t]:array_keys($this->listOfColumns[$t]);
	}
	function joinOnSQL($share){
		if(is_array($share)){
			$r = array();
			foreach($share as $k=>$v)
				$r[$k] = $this->joinOnSQL($this->table,$v);
			return $r;
		}
		$rel = array($this->table,$share);
		sort($rel);
		$rel = implode('_',$rel);
		$q = $this->writerQuoteCharacter;
		return "LEFT OUTER JOIN {$q}{$rel}{$q} ON {$q}{$rel}{$q}.{$q}{$this->table}_id{$q}={$q}{$this->table}{$q}.{$q}id{$q}
				LEFT OUTER JOIN {$q}{$share}{$q} ON {$q}{$rel}{$q}.{$q}{$share}_id{$q}={$q}{$share}{$q}.{$q}id{$q}";
	}
	function count($compo=array(),$params=null){
		return (int)$this->query('getCell',array('select'=>'COUNT(*)','from'=>'('.$this->buildQuery($compo).') as TMP_count'),(array)$params);
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
	static function explodeAgg($data){
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
	static function explodeAggTable($data){
		$table = array();
		if(is_array($data)||$data instanceof \ArrayAccess)
			foreach($data as $i=>$d){
				$id = isset($d['id'])?$d['id']:$i;
				$table[$id] = self::explodeAgg($d);
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
	static function nestBinding($sql,$binds){
		do{
			list($sql,$binds) = self::nestBindingLoop($sql,(array)$binds);
			$containA = false;
			foreach($binds as $v)
				if($containA=is_array($v))
					break;
		}
		while($containA);
		return array($sql,$binds);
	}
	private static function nestBindingLoop($sql,$binds){
		$nBinds = array();
		$ln = 0;
		foreach($binds as $k=>$v){
			if(is_array($v)){
				if(is_integer($k))
					$find = '?';
				else
					$find = ':'.ltrim($k,':');
				$binder = array();
				foreach(array_keys($v) as $_k){
					if(is_integer($_k))
						$binder[] = '?';
					else
						$binder[] = ':slot_'.$k.'_'.ltrim($_k,':');
				}
				$av = array_values($v);
				$i = 0;
				do{
					if($ln)
						$p = strpos($sql,$find,$ln);
					else
						$p = str::posnth($sql,$find,is_integer($k)?$k:0,$ln);
					$nSql = '';
					if($p!==false)
						$nSql .= substr($sql,0,$p);
					$binderL = $binder;
					if($i)
						foreach($binderL as &$v)
							if($v!='?')
								$v .= $i;
					$nSql .= '('.implode(',',$binderL).')';
					$ln = strlen($nSql);
					$nSql .= substr($sql,$p+strlen($find));
					$sql = $nSql;
					foreach($binderL as $y=>$_k){
						if($_k=='?')
							$nBinds[] = $av[$y];
						else
							$nBinds[$_k] = $av[$y];
					}
					$i++;
				}
				while(!is_integer($k)&&strpos($sql,$find)!==false);
			}
			else{
				if(is_integer($k))
					$nBinds[] = $v;
				else{
					$key = ':'.ltrim($k,':');
					$nBinds[$key] = $v;
				}
			}
		}
		return array($sql,$nBinds);
	}
}