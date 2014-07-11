<?php namespace surikat\model;
use surikat\control;
use surikat\model;
use surikat\model\SQLComposer\SQLComposer;
class Query4D extends Query {
	protected $heuristic;
	function heuristic($reload=null){ //todo mode frozen
		if(!isset($this->heuristic)||$reload){
			$this->heuristic = array();
			$listOfTables = R::inspect();
			$tableL = strlen($this->table);
			$h['fields'] = in_array($this->table,$listOfTables)?$this->listOfColumns($this->table,null,$reload):array();
			$h['shareds'] = array();
			$h['parents'] = array();
			$h['fieldsOwn'] = array();
			$h['owns'] = array();
			foreach($listOfTables as $table) //shared
				if(strpos($table,'_')!==false&&((strpos($table,$this->table)===0&&$table=substr($table,$tableL+1))
					||((strrpos($table,$this->table)===strlen($table)-$tableL)&&($table=substr($table,0,($tableL+1)*-1))))){
						$h['shareds'][] = $table;
						$h['fieldsShareds'][$table] = $this->listOfColumns($table,null,$reload);
				}
			foreach($h['fields'] as $field) //parent
				if(strrpos($field,'_id')===strlen($field)-3){
					$table = substr($field,0,-3);
					$h['parents'][] = $table;
				}
			foreach($listOfTables as $table){ //own
				if(strpos($table,'_')===false&&$table!=$this->table){
					$h['fieldsOwn'][$table] = $this->listOfColumns($table,null,$reload);
					if(in_array($this->table.'_id',$h['fieldsOwn'][$table]))
						$h['owns'][] = $table;
				}
			}
			$this->heuristic = $h;
		}
		return $this->heuristic;
	}
	function autoSelect($reload=null){
		$q = $this->writerQuote;
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
		$queryCount->autoSelect();
		$queryCount->select($this->table.'.id');
		$this->select('COUNT(*)')->from('('.$queryCount->getQuery().') as TMP_count');
		return (int)$this->getCell();
	}
	function table($compo=array(),$params=array()){
		if(empty($compo['select']))
			$compo['select'] = '*';
		$compo['select'] = (array)$compo['select'];
		if(!self::inSelectTable('id',$compo['select'],$this->table)&&!self::inSelectTable('*',$compo['select'],$this->table))
			$compo['select'][] = 'id';
		$this->autoSelect($compo);
		$data = $this->query('getAll',$compo,$params);
		$data = self::explodeAggTable($data);
		if(control::devHas(control::dev_model_compo))
			print('<pre>'.htmlentities(print_r($data,true)).'</pre>');
		return $data;
	}
	function row($compo=array(),$params=array()){
		if(empty($compo['select']))
			$compo['select'] = '*';
		$compo['select'] = (array)$compo['select'];
		if(!in_array('id',$compo['select'])&&!in_array($this->table.'.id',$compo['select']))
			$compo['select'][] = 'id';
		$this->autoSelect($compo);
		$compo['limit'] = 1;
		$row = $this->query('getRow',$compo,$params);
		$row = self::explodeAgg($row);
		if(control::devHas(control::dev_model_compo))
			print('<pre>'.htmlentities(print_r($row,true)).'</pre>');
		return $row;
	}
}