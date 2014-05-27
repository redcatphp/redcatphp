<?php namespace surikat\model;
use surikat\model;
use surikat\model\SQLComposer\API as SQLComposer;
class Compo {
	static function explodeGroupConcat($data){
		$_gs = chr(0x1D);
		$table = array();
		if(is_array($data)||$data instanceof \ArrayAccess)
			foreach(array_keys($data) as $i){
				$id = $data[$i]['id'];
				$table[$id] = array();
				foreach(array_keys($data[$i]) as $col){
					$multi = stripos($col,'>');
					$sep = stripos($col,'<>')?'<>':(stripos($col,'<')?'<':($multi?'>':false));
					if($sep){
						$x = explode($sep,$col);
						$tb = &$x[0];
						$_col = &$x[1];
						if(!isset($table[$id][$tb]))
							$table[$id][$tb] = array();
						if(empty($data[$i][$col])){
							if(!isset($table[$id][$tb]))
								$table[$id][$tb] = array();
						}
						elseif($multi){
							$_idx = explode($_gs,$data[$i][$tb.$sep.'id']);
							$_x = explode($_gs,$data[$i][$col]);
							foreach($_idx as $_i=>$_id)
								if(!empty($_id))
									$table[$id][$tb][$_id][$_col] = $_x[$_i];
						}
						else
							$table[$id][$tb][$_col] = $data[$i][$col];
					}
					else
						$table[$id][$col] = $data[$i][$col];
				}
			}
		return $table;
	}
	static function getSeparator(){
		return (strpos(get_class(R::$writer),'SQLiteT')!==false)?',':'SEPARATOR';
	}
	static function getConcatenator(){
		return (strpos(get_class(R::$writer),'SQLiteT')!==false)?"cast(X'1D' as text)":'0x1D';
	}
	static function wrapperEnable(){
		stream_register_wrapper('db', 'Stream_Db');
	}

	static function prefixSelectColWithTable($table,$v){
		return strpos($v,'.')===false&&strpos($v,'(')===false&&strpos($v,')')===false?$table.'.'.$v:$v;
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
						$select[] = self::prefixSelectColWithTable($table,$sel);
				else
					$select[] = self::prefixSelectColWithTable($table,$v);
			}
			else
				$methods[$k] = $v;
		}
		
		//helpers methods
		if(isset($methods['sum'])){
			if(!empty($methods['sum']))
				$methods['having'][] = 'SUM('.implode(' && ',(array)$methods['sum']).')';
			unset($methods['sum']);
		}
		if(isset($methods['joinOn'])){
			$methods['join'] = (isset($methods['join'])?implode((array)$methods['join']):'').self::joinOn($table,implode((array)$methods['joinOn']));
			unset($methods['joinOn']);
		}
			
		//aliasing methods
		if(isset($methods['having-sum'])){
			if(!empty($methods['having-sum']))
				$methods['having'][] = 'SUM('.$methods['having-sum'].')';
			unset($methods['having-sum']);
		}
		$composer = stripos($method,'get')==0?'select':(($pos=strcspn($string,'ABCDEFGHJIJKLMNOPQRSTUVWXYZ'))!==false?substr($method,$pos):$method);
		if($composer=='select'){
			if(empty($select)){
				$select[] = 'label';
				$select[] = 'id';
			}
			foreach(array_keys($select) as $i)
					$select[$i] = self::prefixSelectColWithTable($table,$select[$i]);
		}
		if(isset($methods['from'])){
			$from = $methods['from'];
			unset($methods['from']);
		}
		else
			$from = $table;
		if(model::$DEBUG)
			print('<pre>'.htmlentities(print_r($compo,true)).'</pre>');
		$sqlc = SQLComposer::$composer($select)->from($from);
		foreach($methods as $k=>$v)
			$sqlc->$k($v);
		return $sqlc->getQuery();
	}
	static function query($table,$method,$compo=array(),$params=array()){
		$query = self::buildQuery($table,$compo,$method);
		if(model::$DEBUG)
			print('<pre>'.htmlentities($query).'</pre>');
		if(in_array($table,self::listOfTables()))
			return R::$method($query,(array)$params);
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
	
	static function table4D($table,$compo=array(),$params=array()){
		// self::debug();
		if(empty($compo['select']))
			$compo['select'] ='*';
		self::compoSelectIn4D($table,$compo,$params);
		$table = self::query($table,'getAll',$compo,$params);
		$table = self::explodeGroupConcat($table);
		if(model::$DEBUG)
			print('<pre>'.htmlentities(print_r($table,true)).'</pre>');
		return $table;
	}
	protected static $listOfTables;
	protected static $heuristic4D;
	static function listOfTables(){
		if(!self::$listOfTables)
			self::$listOfTables = R::inspect();
		return self::$listOfTables;
	}
	static function heuristic4D($table){
		if(!isset(self::$heuristic4D[$table])){
			$listOfTables = self::listOfTables();
			$tableL = strlen($table);
			$h4D['fields'] = in_array($table,$listOfTables)?array_keys(R::inspect($table)):array();
			$h4D['shareds'] = array();
			$h4D['parents'] = array();
			$h4D['fieldsOwn'] = array();
			$h4D['owns'] = array();
			foreach($listOfTables as $table) //shared
				if(strpos($table,'_')!==false&&((strpos($table,$table)===0&&$table=substr($table,$tableL+1))
					||((strrpos($table,$table)===strlen($table)-$tableL)&&($table=substr($table,0,($tableL+1)*-1))))){
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
			self::$heuristic4D[$table] = $h4d;
		}
		return self::$heuristic4D[$table];
	}
	static function compoSelectIn4D($table,&$compo,&$params){
		extract(self::heuristic4D($table));
		$compo['select'] = (array)@$compo['select'];
		foreach($parents as $parent){
			foreach(array_keys(R::inspect($parent)) as $col)
				$compo['select'][] = "{$parent}.{$col} as '{$parent}<{$col}'";
			$compo['join'][] = " LEFT OUTER JOIN {$parent} ON {$parent}.id={$table}.{$parent}_id";
		}
		foreach($shareds as $share){
			foreach($fieldsShareds as $col)
				$compo['select'][] =  "GROUP_CONCAT(COALESCE({$share}.{$col},'') ".self::getSeparator().' '.self::getConcatenator().") as '{$share}<>{$col}'";
			$rel = array($table,$share);
			sort($rel);
			$rel = implode('_',$rel);
			$compo['join'][] = " LEFT OUTER JOIN {$rel} ON {$rel}.{$table}_id={$table}.id";
			$compo['join'][] = " LEFT OUTER JOIN {$share} ON {$rel}.{$share}_id={$share}.id";
		}
		foreach($owns as $own){
			foreach($fieldsOwn[$table] as $col)
				if(strrpos($col,'_id')!==strlen($col)-3)
					$compo['select'][] = "GROUP_CONCAT(COALESCE({$own}.{$col},'') ".self::getSeparator().' '.self::getConcatenator().") as '{$own}>{$col}'";
			$compo['join'][] = " LEFT OUTER JOIN {$own} ON {$own}.{$table}_id={$table}.id";
		}
		if(strpos(implode('',$compo['select']),'GROUP_CONCAT')!==false)
			$compo['group_by'][] = 'id';
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
		return "LEFT OUTER JOIN {$rel} ON {$rel}.{$table}_id={$table}.id
				LEFT OUTER JOIN {$share} ON {$rel}.{$share}_id={$share}.id";
	}
	static function count($table,$compo=array(),$params=null){
		//return (int)R::getCell('SELECT COUNT(*) FROM ('.self::buildQuery($table,$compo).') as TMP_count',(array)$params);
		return (int)self::query($table,'getCell',array('select'=>'COUNT(*)','from'=>'('.self::buildQuery($table,$compo).') as TMP_count'),(array)$params);
	}
	
}
?>
