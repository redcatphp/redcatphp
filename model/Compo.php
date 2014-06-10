<?php namespace surikat\model;
use surikat\control;
use surikat\model;
use surikat\model\SQLComposer\API as SQLComposer;
class Compo {
	static $SqlWriterSeparators = array(
		'MySQL'			=>'SEPARATOR',
		'SQLiteT'		=>',',
		'PostgreSQL'	=>',',
		//'CUBRID'		=>,
	);
	static $SqlWriterAgg = array(
		'MySQL'			=>'GROUP_CONCAT',
		'SQLiteT'		=>'GROUP_CONCAT',
		'PostgreSQL'	=>'string_agg',
		//'CUBRID'=>,
	);
	static $SqlWriterAggCaster = array(
		'PostgreSQL'	=>'::text',
		'MySQL'			=>'',
		'SQLiteT'		=>'',
		//'CUBRID'=>,
	);
	static $SqlWriterConcatenators = array(
		'PostgreSQL'	=>"x'1D'::text",
		'SQLiteT'		=>"cast(X'1D' as text)",
		'MySQL'			=>'0x1D',
		//'CUBRID'=>,
	);
	static function getQuote(){
		$q = R::getWriter()->esc('test'); //work around protected property of RedBean QueryWriter for not fork
		$q = substr($q,0,strpos($q,'test'));
		return $q;
	}
	static function quote($v){
		if($v=='*')
			return $v;
		$q = self::getQuote();
		return $q.trim($v,$q).$q;
	}
	static function getWriterType(){
		return substr(get_class(R::getWriter()),37); //strlen('surikat\\model\\RedBeanPHP\\QueryWriter\\') = 37
	}
	static function getSeparator(){
		$c = self::getWriterType();
		if(!isset(self::$SqlWriterSeparators[$c]))
			trigger_error('separator for '.$c.' not implemented',256);
		return self::$SqlWriterSeparators[$c];
	}
	static function getConcatenator(){
		$c = self::getWriterType();
		if(!isset(self::$SqlWriterConcatenators[$c]))
			trigger_error('concatenator for '.$c.' not implemented',256);
		return self::$SqlWriterConcatenators[$c];
	}
	static function getAgg(){
		$c = self::getWriterType();
		if(!isset(self::$SqlWriterAgg[$c]))
			trigger_error('GROUP_CONCAT for '.$c.' not implemented',256);
		return self::$SqlWriterAgg[$c];
	}
	static function getAggCaster(){
		$c = self::getWriterType();
		return isset(self::$SqlWriterAggCaster[$c])?self::$SqlWriterAggCaster[$c]:'';
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
							$row[$tb][$_id][$_col] = $_x[$_i];
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
			foreach(array_keys($data) as $i){
				$id = isset($data[$i]['id'])?$data[$i]['id']:$i;
				$table[$id] = self::explodeGroupConcat($data[$i]);
			}
		return $table;
	}
	static function wrapperEnable(){
		stream_register_wrapper('db', 'Stream_Db');
	}
	static function prefixColWithTable($v,$table){
		if(strpos($v,'(')===false&&strpos($v,')')===false&&strpos($v,' as ')===false&&strpos($v,'.')===false)
			$v = self::quote($table).'.'.self::quote($v);
		return $v;
	}
	static function buildQuery($table,$compo=array(),$method='select'){
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
						$select[] = self::prefixColWithTable($sel,$table);
				else
					$select[] = self::prefixColWithTable($v,$table);
			}
			else
				$methods[$k] = $v;
		}
		if(isset($methods['sum'])){ //helpers methods
			if(!empty($methods['sum']))
				$methods['having'][] = 'SUM('.implode(' && ',(array)$methods['sum']).')';
			unset($methods['sum']);
		}
		if(isset($methods['joinOn'])){
			$methods['join'] = (isset($methods['join'])?implode((array)$methods['join']):'').implode((array)self::joinOn($table,$methods['joinOn']));
			unset($methods['joinOn']);
		}
		if(isset($methods['having-sum'])){ //aliasing methods
			if(!empty($methods['having-sum']))
				$methods['having'][] = 'SUM('.$methods['having-sum'].')';
			unset($methods['having-sum']);
		}
		$composer = stripos($method,'get')==0?'select':(($pos=strcspn($string,'ABCDEFGHJIJKLMNOPQRSTUVWXYZ'))!==false?substr($method,$pos):$method);
		if($composer=='select'){
			foreach(array_keys($select) as $i)
					$select[$i] = self::prefixColWithTable($select[$i],$table);
		}
		if(isset($methods['from'])){
			$from = $methods['from'];
			unset($methods['from']);
		}
		else
			$from = $table;
		if(strpos($from,'(')===false&&strpos($from,')')===false)
			$from = self::quote($from);
		if(control::devHas(control::dev_model_compo))
			print('<pre>'.htmlentities(print_r($compo,true)).'</pre>');
		$sqlc = SQLComposer::$composer($select)->from($from);
		foreach($methods as $k=>$v)
			$sqlc->$k($v);
		return $sqlc->getQuery();
	}
	static function query($table,$method,$compo=array(),$params=array()){
		$query = self::buildQuery($table,$compo,$method);
		if(control::devHas(control::dev_model_sql))
			print('<pre>'.htmlentities($query).'</pre>');
		if(in_array($table,self::listOfTables()))
			return R::$method($query,(array)$params);
	}

	static function cellId($table,$label,$addCompo=null,$addParams=null){
		$i = is_integer($label);
		$compo = array('select'=>$i?'label':'id','where'=>($i?'id':'label').'=?');
		if(is_array($addCompo))
			$compo = array_merge($compo,$addCompo);
		$params = array($label);
		if(is_array($addParams))
			$params = array_merge($params,$addParams);
		return self::cell($table,$compo,$params);
	}
	static function cell($table,$compo=array(),$params=array()){
		return self::query($table,'getCell',$compo,$params);
	}
	static function column($table,$compo=array(),$params=array()){
		return self::query($table,'getCol',$compo,$params);
	}
	static function col($table,$compo=array(),$params=array()){
		$col = array();
		foreach(self::query($table,'getAll',$compo,$params) as $row){
			$id = $row['id'];
			unset($row['id']);
			$col[$id] = array_shift($row);
		}
		return $col;
	}
	static function row($table,$compo=array(),$params=array()){
		return self::query($table,'getRow',$compo,$params);
	}
	static function table($table,$compo=array(),$params=array()){
		return self::query($table,'getAll',$compo,$params);
	}
	
	static function count4D($table,$compo=array(),$params=array()){
		self::compoSelectIn4D($table,$compo);
		$compo['select'] = array($table.'.id');
		$q = self::buildQuery($table,$compo);
		//$i = R::getCell('SELECT COUNT(*) FROM ('.self::buildQuery($table,$compo).') as TMP_count',(array)$params);
		$i = self::query($table,'getCell',array('select'=>'COUNT(*)','from'=>'('.$q.') as TMP_count'),(array)$params);
		return (int)$i;
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
	static function table4D($table,$compo=array(),$params=array()){
		if(empty($compo['select']))
			$compo['select'] = '*';
		$compo['select'] = (array)$compo['select'];
		if(!self::inSelectTable('id',$compo['select'],$table)&&!self::inSelectTable('*',$compo['select'],$table))
			$compo['select'][] = 'id';
		self::compoSelectIn4D($table,$compo);
		$data = self::query($table,'getAll',$compo,$params);
		$data = self::explodeGroupConcatMulti($data);
		if(control::devHas(control::dev_model_compo))
			print('<pre>'.htmlentities(print_r($data,true)).'</pre>');
		return $data;
	}
	static function row4D($table,$compo=array(),$params=array()){
		if(empty($compo['select']))
			$compo['select'] = '*';
		$compo['select'] = (array)$compo['select'];
		if(!in_array('id',$compo['select'])&&!in_array($table.'.id',$compo['select']))
			$compo['select'][] = 'id';
		self::compoSelectIn4D($table,$compo);
		$compo['limit'] = 1;
		$row = self::query($table,'getRow',$compo,$params);
		$row = self::explodeGroupConcat($row);
		if(control::devHas(control::dev_model_compo))
			print('<pre>'.htmlentities(print_r($row,true)).'</pre>');
		return $row;
	}
	protected static $listOfTables;
	protected static $heuristic4D;
	static function listOfTables(){
		if(!self::$listOfTables)
			self::$listOfTables = R::inspect();
		return self::$listOfTables;
	}
	static function heuristic4D($t){
		if(!isset(self::$heuristic4D[$t])){
			$listOfTables = self::listOfTables();
			$tableL = strlen($t);
			$h4D['fields'] = in_array($t,$listOfTables)?array_keys(R::inspect($t)):array();
			$h4D['shareds'] = array();
			$h4D['parents'] = array();
			$h4D['fieldsOwn'] = array();
			$h4D['owns'] = array();
			foreach($listOfTables as $table) //shared
				if(strpos($table,'_')!==false&&((strpos($table,$t)===0&&$table=substr($table,$tableL+1))
					||((strrpos($table,$t)===strlen($table)-$tableL)&&($table=substr($table,0,($tableL+1)*-1))))){
						$h4D['shareds'][] = $table;
						$h4D['fieldsShareds'] = array_keys(R::inspect($table));
				}
			foreach($h4D['fields'] as $field) //parent
				if(strrpos($field,'_id')===strlen($field)-3){
					$table = substr($field,0,-3);
					$h4D['parents'][] = $table;
					$h4D['fieldsParents'] = array_keys(R::inspect($table));
				}
			foreach($listOfTables as $table) //own
				if(strpos($table,'_')===false&&$table!=$table){
					$h4D['fieldsOwn'][$table] = array_keys(R::inspect($table));
					if(in_array($table.'_id',$h4D['fieldsOwn']))
						$h4D['owns'][] = $table;
				}
			self::$heuristic4D[$t] = $h4D;
		}
		return self::$heuristic4D[$t];
	}
	static function compoSelectIn4D($table,&$compo){
		$q = self::getQuote();
		$agg = self::getAgg();
		$aggc = self::getAggCaster();
		extract(self::heuristic4D($table));
		$compo['select'] = (array)@$compo['select'];
		foreach($parents as $parent){
			foreach(array_keys(R::inspect($parent)) as $col)
				$compo['select'][] = "{$q}{$parent}{$q}.{$q}{$col}{$q} as {$q}{$parent}<{$col}{$q}";
			$compo['join'][] = " LEFT OUTER JOIN {$q}{$parent}{$q} ON {$q}{$parent}{$q}.{$q}id{$q}={$q}{$table}{$q}.{$q}{$parent}_id{$q}";
			$compo['group_by'][] = $q.$parent.$q.'.'.$q.'id'.$q;
		}
		foreach($shareds as $share){
			foreach($fieldsShareds as $col)
				$compo['select'][] =  $agg."(COALESCE({$q}{$share}{$q}.{$q}{$col}{$q}{$aggc},''{$aggc}) ".self::getSeparator().' '.self::getConcatenator().") as {$q}{$share}<>{$col}{$q}";
			$rel = array($table,$share);
			sort($rel);
			$rel = implode('_',$rel);
			$compo['join'][] = " LEFT OUTER JOIN {$q}{$rel}{$q} ON {$q}{$rel}{$q}.{$q}{$table}_id{$q}={$q}{$table}{$q}.{$q}id{$q}";
			$compo['join'][] = " LEFT OUTER JOIN {$q}{$share}{$q} ON {$q}{$rel}{$q}.{$q}{$share}_id{$q}={$q}{$share}{$q}.{$q}id{$q}";
		}
		foreach($owns as $own){
			foreach($fieldsOwn[$table] as $col)
				if(strrpos($col,'_id')!==strlen($col)-3)
					$compo['select'][] = $agg."(COALESCE({$q}{$own}{$q}.{$q}{$col}{$q}{$aggc},''{$aggc}) ".self::getSeparator().' '.self::getConcatenator().") as {$q}{$own}>{$col}{$q}";
			$compo['join'][] = " LEFT OUTER JOIN {$q}{$own}{$q} ON {$q}{$own}{$q}.{$q}{$table}_id{$q}={$q}{$table}{$q}.{$q}id{$q}";
		}
		if(!(empty($parents)&&empty($shareds)&&empty($owns)))
			$compo['group_by'][] = $q.$table.$q.'.'.$q.'id'.$q;
	}
	static function joinOn($table,$share){
		if(is_array($share)){
			$r = array();
			foreach($share as $k=>$v)
				$r[$k] = self::joinOn($table,$v);
			return $r;
		}
		$rel = array($table,$share);
		sort($rel);
		$rel = implode('_',$rel);
		$q = self::getQuote();
		return "LEFT OUTER JOIN {$q}{$rel}{$q} ON {$q}{$rel}{$q}.{$q}{$table}_id{$q}={$q}{$table}{$q}.{$q}id{$q}
				LEFT OUTER JOIN {$q}{$share}{$q} ON {$q}{$rel}{$q}.{$q}{$share}_id{$q}={$q}{$share}{$q}.{$q}id{$q}";
	}
	static function count($table,$compo=array(),$params=null){
		//return (int)R::getCell('SELECT COUNT(*) FROM ('.self::buildQuery($table,$compo).') as TMP_count',(array)$params);
		return (int)self::query($table,'getCell',array('select'=>'COUNT(*)','from'=>'('.self::buildQuery($table,$compo).') as TMP_count'),(array)$params);
	}
	
}
