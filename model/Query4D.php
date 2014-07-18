<?php namespace surikat\model;
use surikat\control;
use surikat\model;
class Query4D extends Query {
	protected static $listOfTables;
	protected static $heuristic;
	protected $_ignore = array();
	function ignoring($k,$ignore){
		return isset($this->_ignore[$k])&&in_array($ignore,$this->_ignore[$k]);
	}
	function ignoreTable(){
		foreach(func_get_args() as $ignore)
			$this->_ignore['table'] = $ignore;
	}
	function ignoreColumn(){
		foreach(func_get_args() as $ignore)
			$this->_ignore['column'] = $ignore;
	}
	function ignoreFrom(){
		foreach(func_get_args() as $ignore)
			$this->_ignore['from'] = $ignore;
	}
	function ignoreSelect(){
		foreach(func_get_args() as $ignore)
			$this->_ignore['select'] = $ignore;
	}
	function ignoreJoin(){
		return call_user_func_array(array($this,'ignoreFrom'),func_get_args());
	}
	function select(){
		if(!$this->ignoring('select',func_get_arg(0)))
			return parent::__call(__FUNCTION__,func_get_args());
	}
	function join(){
		if(!$this->ignoring('join',func_get_arg(0)))
			return parent::__call(__FUNCTION__,func_get_args());
	}
	
	function heuristic($reload=null){ //todo mode frozen
		if(!$this->table)
			return;
		if(!isset(self::$heuristic[$this->table])||$reload){
			if(!isset(self::$listOfTables))
				self::$listOfTables = R::inspect();
			$tableL = strlen($this->table);
			$h = array();
			$h['fields'] = in_array($this->table,self::$listOfTables)?$this->listOfColumns($this->table,null,$reload):array();
			$h['shareds'] = array();
			$h['parents'] = array();
			$h['fieldsOwn'] = array();
			$h['owns'] = array();
			foreach(self::$listOfTables as $table) //shared
				if((strpos($table,'_')!==false&&((strpos($table,$this->table)===0&&$table=substr($table,$tableL+1))
					||((strrpos($table,$this->table)===strlen($table)-$tableL)&&($table=substr($table,0,($tableL+1)*-1)))))
				&&!$this->ignoring('table',$table)){
						$h['shareds'][] = $table;
						$h['fieldsShareds'][$table] = $this->listOfColumns($table,null,$reload);
				}
			foreach($h['fields'] as $field) //parent
				if(strrpos($field,'_id')===strlen($field)-3){
					$table = substr($field,0,-3);
					if(!$this->ignoring('table',$table))
						$h['parents'][] = $table;
				}
			foreach(self::$listOfTables as $table){ //own
				if(strpos($table,'_')===false&&$table!=$this->table){
					$h['fieldsOwn'][$table] = $this->listOfColumns($table,null,$reload);
					if(in_array($this->table.'_id',$h['fieldsOwn'][$table])&&!$this->ignoring('table',$table))
						$h['owns'][] = $table;
				}
			}
			
			if(isset($h['fields']))
				foreach(array_keys($h['fields']) as $i)
					if($this->ignoring('column',$h['fields'][$i]))
						unset($h['fields'][$i]);
			if(isset($h['fieldsOwn']))
				foreach(array_keys($h['fieldsOwn']) as $table)
					foreach(array_keys($h['fieldsOwn'][$table]) as $i)
						if($this->ignoring('column',$table.'.'.$h['fieldsOwn'][$table][$i]))
							unset($h['fieldsOwn'][$table][$i]);
			if(isset($h['fieldsShareds']))
				foreach(array_keys($h['fieldsShareds']) as $table)
					foreach(array_keys($h['fieldsShareds'][$table]) as $i)
						if($this->ignoring('column',$table.'.'.$h['fieldsShareds'][$table][$i]))
							unset($h['fieldsShareds'][$table][$i]);
						
			self::$heuristic[$this->table] = $h;
		}
		return self::$heuristic[$this->table];
	}
	function autoSelectJoin($reload=null){
		$q = $this->writerQuoteCharacter;
		$agg = $this->writerAgg;
		$aggc = $this->writerAggCaster;
		$sep = $this->writerSeparator;
		$cc = $this->writerConcatenator;
		extract($this->heuristic($reload));
		foreach($parents as $parent){
			foreach($this->listOfColumns($parent,null,$reload) as $col)
				$this->select("{$q}{$parent}{$q}.{$q}{$col}{$q} as {$q}{$parent}<{$col}{$q}");
			$this->join(" LEFT OUTER JOIN {$q}{$parent}{$q} ON {$q}{$parent}{$q}.{$q}id{$q}={$q}{$this->table}{$q}.{$q}{$parent}_id{$q}");
			$this->group_by($q.$parent.$q.'.'.$q.'id'.$q);
		}
		foreach($shareds as $share){
			foreach($fieldsShareds[$share] as $col)
				$this->select("{$agg}({$q}{$share}{$q}.{$q}{$col}{$q}{$aggc} {$sep} {$cc}) as {$q}{$share}<>{$col}{$q}");
			$rel = array($this->table,$share);
			sort($rel);
			$rel = implode('_',$rel);
			$this->join(" LEFT OUTER JOIN {$q}{$rel}{$q} ON {$q}{$rel}{$q}.{$q}{$this->table}_id{$q}={$q}{$this->table}{$q}.{$q}id{$q}");
			$this->join(" LEFT OUTER JOIN {$q}{$share}{$q} ON {$q}{$rel}{$q}.{$q}{$share}_id{$q}={$q}{$share}{$q}.{$q}id{$q}");
		}
		foreach($owns as $own){
			foreach($fieldsOwn[$own] as $col)
				if(strrpos($col,'_id')!==strlen($col)-3)
					$this->select("{$agg}(COALESCE({$q}{$own}{$q}.{$q}{$col}{$q}{$aggc},''{$aggc}) {$sep} {$cc}) as {$q}{$own}>{$col}{$q}");
			$this->join(" LEFT OUTER JOIN {$q}{$own}{$q} ON {$q}{$own}{$q}.{$q}{$this->table}_id{$q}={$q}{$this->table}{$q}.{$q}id{$q}");
		}
		if(!(empty($parents)&&empty($shareds)&&empty($owns)))
			$this->group_by($q.$this->table.$q.'.'.$q.'id'.$q);
	}
	function count(){
		$queryCount = clone $this;
		$queryCount->autoSelectJoin();
		$queryCount->unSelect();
		$queryCount->select('id');
		return (int)model::newSelect('COUNT(*)')->from('('.$queryCount->getQuery().') as TMP_count')->getCell();
	}
	function selectNeed($n='id'){
		if(!count($this->composer->select))
			$this->select('*');
		if(!$this->inSelect($n)&&!$this->inSelect($n))
			$this->select($n);
	}
	function table(){
		$this->selectNeed();
		$this->autoSelectJoin();
		$data = $this->getAll4D();
		if(control::devHas(control::dev_model_data))
			print('<pre>'.htmlentities(print_r($data,true)).'</pre>');
		return $data;
	}
	function row($compo=array(),$params=array()){
		$this->selectNeed();
		$this->autoSelectJoin();
		$this->limit(1);
		$row = $this->getRow4D();
		if(control::devHas(control::dev_model_data))
			print('<pre>'.htmlentities(print_r($row,true)).'</pre>');
		return $row;
	}
}