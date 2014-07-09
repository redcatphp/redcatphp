<?php namespace surikat\model;
use surikat\control;
use surikat\model;
use surikat\model\SQLComposer\API as SQLComposer;
class Query4D extends Query {
	protected $heuristic4D;
	function heuristic4D($reload=null){
		if(!isset($this->heuristic4D)||$reload){
			$this->heuristic4D = array();
			$listOfTables = R::inspect();
			$tableL = strlen($this->table);
			$h4D['fields'] = in_array($this->table,$listOfTables)?$this->listOfColumns($this->table,null,$reload):array();
			$h4D['shareds'] = array();
			$h4D['parents'] = array();
			$h4D['fieldsOwn'] = array();
			$h4D['owns'] = array();
			foreach($listOfTables as $table) //shared
				if(strpos($table,'_')!==false&&((strpos($table,$this->table)===0&&$table=substr($table,$tableL+1))
					||((strrpos($table,$this->table)===strlen($table)-$tableL)&&($table=substr($table,0,($tableL+1)*-1))))){
						$h4D['shareds'][] = $table;
						$h4D['fieldsShareds'][$table] = $this->listOfColumns($table,null,$reload);
				}
			foreach($h4D['fields'] as $field) //parent
				if(strrpos($field,'_id')===strlen($field)-3){
					$table = substr($field,0,-3);
					$h4D['parents'][] = $table;
				}
			foreach($listOfTables as $table){ //own
				if(strpos($table,'_')===false&&$table!=$this->table){
					$h4D['fieldsOwn'][$table] = $this->listOfColumns($table,null,$reload);
					if(in_array($this->table.'_id',$h4D['fieldsOwn'][$table]))
						$h4D['owns'][] = $table;
				}
			}
			$this->heuristic4D = $h4D;
		}
		return $this->heuristic4D;
	}
	function compoSelectIn4D(&$compo,$reload=null){
		$q = $this->writerQuote;
		$agg = $this->writerAgg;
		$aggc = $this->writerAggCaster;
		$sep = $this->writerSeparator;
		$cc = $this->writerConcatenator;
		extract($this->heuristic4D($reload));
		
		$comp = array();
		if(isset($compo['join'])){
			$comp['join'] = (array)@$compo['join'];
			unset($compo['join']);
		}
		
		$compo['select'] = (array)@$compo['select'];
		foreach($parents as $parent){
			foreach($this->listOfColumns($parent,null,$reload) as $col)
				$compo['select'][] = "{$q}{$parent}{$q}.{$q}{$col}{$q} as {$q}{$parent}<{$col}{$q}";
			$compo['join'][] = " LEFT OUTER JOIN {$q}{$parent}{$q} ON {$q}{$parent}{$q}.{$q}id{$q}={$q}{$this->table}{$q}.{$q}{$parent}_id{$q}";
			$compo['group_by'][] = $q.$parent.$q.'.'.$q.'id'.$q;
		}
		foreach($shareds as $share){
			foreach($fieldsShareds[$share] as $col)
				$compo['select'][] =  "{$agg}({$q}{$share}{$q}.{$q}{$col}{$q}{$aggc} {$sep} {$cc}) as {$q}{$share}<>{$col}{$q}";
			$rel = array($this->table,$share);
			sort($rel);
			$rel = implode('_',$rel);
			$compo['join'][] = " LEFT OUTER JOIN {$q}{$rel}{$q} ON {$q}{$rel}{$q}.{$q}{$this->table}_id{$q}={$q}{$this->table}{$q}.{$q}id{$q}";
			$compo['join'][] = " LEFT OUTER JOIN {$q}{$share}{$q} ON {$q}{$rel}{$q}.{$q}{$share}_id{$q}={$q}{$share}{$q}.{$q}id{$q}";
		}
		foreach($owns as $own){
			foreach($fieldsOwn[$own] as $col)
				if(strrpos($col,'_id')!==strlen($col)-3)
					$compo['select'][] = "{$agg}(COALESCE({$q}{$own}{$q}.{$q}{$col}{$q}{$aggc},''{$aggc}) {$sep} {$cc}) as {$q}{$own}>{$col}{$q}";
			$compo['join'][] = " LEFT OUTER JOIN {$q}{$own}{$q} ON {$q}{$own}{$q}.{$q}{$this->table}_id{$q}={$q}{$this->table}{$q}.{$q}id{$q}";
		}
		if(!(empty($parents)&&empty($shareds)&&empty($owns)))
			$compo['group_by'][] = $q.$this->table.$q.'.'.$q.'id'.$q;

		if(isset($comp['join']))
			foreach($comp['join'] as $c)
				$compo['join'][] = $c;
		
	}
	function count4D($compo=array(),$params=array()){
		$this->compoSelectIn4D($compo);
		$compo['select'] = array($this->table.'.id');
		$q = $this->buildQuery($compo);
		//$i = R::getCell('SELECT COUNT(*) FROM ('.$this->buildQuery($compo).') as TMP_count',(array)$params);
		$i = $this->query('getCell',array('select'=>'COUNT(*)','from'=>'('.$q.') as TMP_count'),(array)$params);
		return (int)$i;
	}
	function table4D($compo=array(),$params=array()){
		if(empty($compo['select']))
			$compo['select'] = '*';
		$compo['select'] = (array)$compo['select'];
		if(!self::inSelectTable('id',$compo['select'],$this->table)&&!self::inSelectTable('*',$compo['select'],$this->table))
			$compo['select'][] = 'id';
		$this->compoSelectIn4D($compo);
		$data = $this->query('getAll',$compo,$params);
		$data = self::explodeGroupConcatMulti($data);
		if(control::devHas(control::dev_model_compo))
			print('<pre>'.htmlentities(print_r($data,true)).'</pre>');
		return $data;
	}
	function row4D($compo=array(),$params=array()){
		if(empty($compo['select']))
			$compo['select'] = '*';
		$compo['select'] = (array)$compo['select'];
		if(!in_array('id',$compo['select'])&&!in_array($this->table.'.id',$compo['select']))
			$compo['select'][] = 'id';
		$this->compoSelectIn4D($compo);
		$compo['limit'] = 1;
		$row = $this->query('getRow',$compo,$params);
		$row = self::explodeGroupConcat($row);
		if(control::devHas(control::dev_model_compo))
			print('<pre>'.htmlentities(print_r($row,true)).'</pre>');
		return $row;
	}
}