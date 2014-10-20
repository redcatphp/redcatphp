<?php namespace surikat\model;
use ArrayAccess;
use BadMethodCallException;
use surikat\control;
use surikat\control\str;
use surikat\model;
use surikat\model\RedBeanPHP\QueryWriter;
use surikat\model\RedBeanPHP\QueryWriter\AQueryWriter;
class Query /* implements ArrayAccess */{
	protected $table;
	protected $writer;
	protected $composer;
	protected $_ignore = [];
	static function getNew($table=null,$composer='select',$writer=null){
		$c = get_called_class();
		return new $c($table,$composer,$writer);
	}
	static function tableExists($table){
		return R::getWriter()->tableExists($table);
	}
	static function columnExists($table,$column){
		return R::getWriter()->columnExists($table,$column);
	}
	function __construct($table=null,$composer='select',$writer=null){
		$this->setTable($table);
		if(!$writer)
			$writer = R::getWriter();
		$this->writer = $writer;
		if(is_string($composer))
			$composer = SQLComposer::$composer();
		$this->composer = $composer;
		if(isset($table))
			$this->from($table);
	}
	function setTable($table=null){
		$this->table = $table;
	}
	function getTable(){
		return $this->table;
	}
	function __destruct(){
		unset($this->composer);
	}
	function __toString(){
		return (string)$this->composer->getQuery();
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
		if(strpos($f,'get')===0&&ctype_upper(substr($f,3,1))){
			if(!$this->table||self::tableExists($this->table)){
				$params = $this->composer->getParams();
				if(is_array($paramsX=array_shift($args)))
					$params = array_merge($params,$args);
				if(control::devHas(control::dev_model_sql)){
					print(str_replace(',',",\n",$this->composer->getQuery()).'<br>');
					print_r($params);
				}
				return R::$f($this->composer->getQuery(),$params);
			}
			return;
		}
		else{
			switch($f){
				case 'orderByFullTextRank':
				case 'selectFullTextRank':
				case 'selectFullTextHighlite':
				case 'selectFullTextHighlight':
				case 'whereFullText':
					array_unshift($args,$this);
					call_user_func_array([$this->writer,$f],$args);
					return $this;
				break;
				default:					
					if(method_exists($this->composer,$f)){
						$un = strpos($f,'un')===0&&ctype_upper(substr($f,2,1));
						if(method_exists($this,$m='composer'.ucfirst($un?substr($f,2):$f)))
							$args = call_user_func_array([$this,$m],$args);
						$sql = array_shift($args);
						$binds = array_shift($args);
						if($sql instanceof SQLComposerBase){
							if(is_array($binds))
								$binds = array_merge($sql->getParams(),$binds);
							else
								$binds = $sql->getParams();
							$sql = $sql->getQuery();
						}
						if(is_array($binds))
							$args = self::nestBinding($sql,$binds);
						else
							$args = [$sql,$binds];
						if($un){
							if(is_array($args[1])&&empty($args[1]))
								$args[1] = null;
						}
						call_user_func_array([$this->composer,$f],$args);
						return $this;
					}
				break;
			}
		}
		throw new BadMethodCallException('Class "'.get_class($this).'": call to undefined method '.$f);
	}
	function getQuery(){
		return $this->composer->getQuery();
	}
	function getParams(){
		return $this->composer->getParams();
	}
	function joinOn($on){
		$this->join(implode((array)$this->joinOnSQL($on)));
		return $this;
	}
	function unJoinOn($on){
		$this->unJoin(implode((array)$this->joinOnSQL($on)));
		return $this;
	}
	function joinWhere($w,$params=null){
		if(empty($w))
			return;
		$this->having($this->joinWhereSQL($w),$params);
		return $this;
	}
	function unJoinWhere($w){
		if(empty($w))
			return;
		$this->unHaving($this->joinWhereSQL($w));
		return $this;
	}
	protected function joinWhereSQL($w){
		if(empty($w))
			return;
		$hc = $this->writerSumCaster;
		$hs = implode(' AND ',(array)$w);
		if($hc)
			$hs = '('.$hs.')'.$hc;
		return 'SUM('.$hs.')>0';
	}
	function selectTruncation($col,$truncation=369,$getl=true){
		$c = $this->formatColumnName($col);
		$this->composer->select("SUBSTRING($c,1,$truncation) as $col");
		if($getl)
			$this->composer->select("LENGTH($c) as {$col}_length");
		return $this;
	}
	protected function composerSelect(){
		$args = func_get_args();
		if(isset($args[0])){
			if(is_array($args[0]))
				foreach($args[0] as &$s)
					$s = $this->formatColumnName($s);
			else
				$args[0] = $this->formatColumnName($args[0]);
		}
		return $args;
	}
	protected function composerFrom(){
		$args = func_get_args();
		if(isset($args[0])&&strpos($args[0],'(')===false&&strpos($args[0],')')===false)
			$args[0] = $this->quote($args[0]);
		return $args;
	}
	protected function composerWhere(){
		$args = func_get_args();
		if(isset($args[0])&&is_array($args[0]))
			$args[0] = implode(' AND ',$args[0]);
		return $args;
	}
	function unQuote($v){
		return trim($v,$this->writerQuoteCharacter);
	}
	function quote($v){
		if($v=='*')
			return $v;
		return $this->writerQuoteCharacter.$this->unQuote($v).$this->writerQuoteCharacter;
	}
	function formatColumnName($v){
		if($this->table&&strpos($v,'(')===false&&strpos($v,')')===false&&strpos($v,' as ')===false&&strpos($v,'.')===false)
			$v = $this->quote($this->table).'.'.$this->quote($v);
		return $v;
	}	
	protected $listOfColumns = [];
	function listOfColumns($t,$details=null,$reload=null){
		if(!isset($this->listOfColumns[$t])||$reload)
			$this->listOfColumns[$t] = R::inspect($t);
		return $details?$this->listOfColumns[$t]:array_keys($this->listOfColumns[$t]);
	}
	function joinOnSQL($share){
		if(is_array($share)){
			$r = [];
			foreach($share as $k=>$v)
				$r[$k] = $this->joinOnSQL($this->table,$v);
			return $r;
		}
		$rel = [$this->table,$share];
		sort($rel);
		$rel = implode('_',$rel);
		$q = $this->writerQuoteCharacter;
		$sql = "LEFT OUTER JOIN {$q}{$rel}{$q} ON {$q}{$rel}{$q}.{$q}{$this->table}_id{$q}={$q}{$this->table}{$q}.{$q}id{$q}";
		if($this->table!=$share)
			$sql .= "LEFT OUTER JOIN {$q}{$share}{$q} ON {$q}{$rel}{$q}.{$q}{$share}_id{$q}={$q}{$share}{$q}.{$q}id{$q}";
		return $sql;
	}
	static function typeAliasExtract($type,&$superalias=null){
		$type = R::toSnake(trim($type));
		$alias = null;
		if(($p=strpos($type,':'))!==false){
			if(isset($type[$p+1])&&$type[$p+1]==':'){
				$superalias = trim(substr($type,$p+2));
				$type = trim(substr($type,0,$p));
			}
			else{
				$alias = trim(substr($type,$p+1));
				$type = trim(substr($type,0,$p));
			}
		}
		return [$type,$alias?$alias:$type];
	}
	function selectRelationnal($select,$colAlias=null){
		$this->getRelationnal($select,$colAlias,true);
		return $this;
	}
	function getRelationnal($select,$colAlias=null,$autoSelectId=false){
		if(is_array($select)){
			foreach($select as $k=>$s)
				if(is_integer($k))
					$this->selectRelationnal($s);
				else
					$this->selectRelationnal($k,$s);
			return;
		}
		$l = strlen($select);
		$type = '';
		$typeParent = $this->table;
		$aliasParent = $this->table;
		$q = $this->writerQuoteCharacter;
		$shareds = [];
		for($i=0;$i<$l;$i++){
			switch($select[$i]){
				case '>': //own
					list($type,$alias) = self::typeAliasExtract($type,$superalias);
					if($superalias)
						$alias = $superalias.'__'.$alias;
					$joint = $type!=$alias?"{$q}$type{$q} as {$q}$alias{$q}":$q.$alias.$q;
					if($exist=(self::tableExists($type)&&self::columnExists($type,$typeParent.'_id')))
						$this->join("LEFT OUTER JOIN $joint ON {$q}$aliasParent{$q}.{$q}id{$q}={$q}$alias{$q}.{$q}{$typeParent}_id{$q}");
					$typeParent = $type;
					$aliasParent = $alias;
					$type = '';
					$relation = '>';
				break;
				case '<':
					list($type,$alias) = self::typeAliasExtract($type,$superalias);
					if(isset($select[$i+1])&&$select[$i+1]=='>'){ //shared
						$i++;
						if($superalias)
							$alias = $superalias.'__'.($alias?$alias:$type);
						$rels = [$typeParent,$type];
						sort($rels);
						$imp = implode('_',$rels);
						$impt = $q.$imp.$q.($superalias?' as '.$q.$superalias.'__'.$imp.$q:'');
						if($exist=(self::tableExists($type)&&self::tableExists($imp))){
							if($superalias)
								$imp = $superalias.'__'.$imp;
							$this->join("LEFT OUTER JOIN $impt ON {$q}$typeParent{$q}.{$q}id{$q}={$q}$imp{$q}.{$q}{$typeParent}_id{$q}");
							$joint = $type!=$alias?"{$q}$type{$q} as {$q}$alias{$q}":$q.$alias.$q;
							$this->join("LEFT OUTER JOIN $joint ON {$q}$alias{$q}.{$q}id{$q}={$q}$imp{$q}.{$q}{$type}".(in_array($type,$shareds)?2:'')."_id{$q}");
							$shareds[] = $type;
						}
						$typeParent = $type;
						$relation = '<>';
					}
					else{ //parent
						if($superalias)
							$alias = $superalias.'__'.$alias;
						$joint = $type!=$alias?"{$q}$type{$q} as {$q}$alias{$q}":$q.$alias.$q;
						if($exist=(self::tableExists($typeParent)&&self::columnExists($typeParent,$type.'_id')))
							$this->join("LEFT OUTER JOIN $joint ON {$q}$alias{$q}.{$q}id{$q}={$q}$typeParent{$q}.{$q}{$type}_id{$q}");
						$typeParent = $type;
						$relation = '<';
					}
					$type = '';
				break;
				default:
					$type .= $select[$i];
				break;
			}
		}
		$table = $typeParent;
		$col = trim($type);
		
		$agg = $this->writerAgg;
		$aggc = $this->writerAggCaster;
		$sep = $this->writerSeparator;
		$cc = $this->writerConcatenator;
		if(!$colAlias)
			$colAlias = ($superalias?$superalias:$table).$relation.$col;
		if($colAlias)
			$colAlias = ' as '.$q.$colAlias.$q;
		if($autoSelectId)
			$idAlias = ' as '.$q.$table.$relation.'id'.$q;
		if($exist){
			switch($relation){
				case '<':
					$this->select(self::autoWrapCol($q.$table.$q.'.'.$q.$col.$q,$table,$col).$colAlias);
					if($autoSelectId)
						$this->select($q.$table.$q.'.'.$q.'id'.$q.$idAlias);
					$this->groupBy($q.$table.$q.'.'.$q.'id'.$q);
				break;
				case '>':
					$this->select("{$agg}(COALESCE(".self::autoWrapCol("{$q}{$table}{$q}.{$q}{$col}{$q}",$table,$col)."{$aggc},''{$aggc}) {$sep} {$cc})".$colAlias);
					if($autoSelectId)
						$this->select("{$agg}(COALESCE({$q}{$table}{$q}.{$q}id{$q}{$aggc},''{$aggc}) {$sep} {$cc})".$idAlias);
				break;
				case '<>':
					$this->select("{$agg}(".self::autoWrapCol("{$q}{$table}{$q}.{$q}{$col}{$q}",$table,$col)."{$aggc} {$sep} {$cc})".$colAlias);
					if($autoSelectId)
						$this->select("{$agg}({$q}{$table}{$q}.{$q}id{$q}{$aggc} {$sep} {$cc})".$idAlias);
				break;
			}
			$this->groupBy($q.$this->table.$q.'.'.$q.'id'.$q);
		}
	}
	function selectNeed($n='id'){
		if(!count($this->composer->select))
			$this->select('*');
		if(!$this->inSelect($n))
			$this->select($n);
		return $this;
	}
	function rowMD(){
		return Query::explodeAgg($this->table());
	}
	function tableMD(){
		return Query::explodeAggTable($this->table());
	}
	function table(){
		$data = $this->getAll();
		if(control::devHas(control::dev_model_data))
			print('<pre>'.htmlentities(print_r($data,true)).'</pre>');
		return $data;
	}
	function getClone(){
		return clone $this;
	}
	function countAll(){
		return $this
			->getClone()
			->unLimit()
			->unOffset()
			->count()
		;
	}
	function count(){
		$queryCount = $this
			->getClone()
			->unOrderBy()
			->unSelect()
			->select('id')
		;
		return (int)static::getNew()
			->select('COUNT(*)')
			->from('('.$queryCount->getQuery().') as TMP_count',$queryCount->getParams())
			->getCell()
		;
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
		$row = [];
		foreach(array_keys($data) as $col){
			$multi = stripos($col,'>');
			$sep = stripos($col,'<>')?'<>':(stripos($col,'<')?'<':($multi?'>':false));
			if($sep){
				$x = explode($sep,$col);
				$tb = &$x[0];
				$_col = &$x[1];
				if(!isset($row[$tb]))
					$row[$tb] = [];
				if(empty($data[$col])){
					if(!isset($row[$tb]))
						$row[$tb] = [];
				}
				elseif($multi){
					$_x = explode($_gs,$data[$col]);
					if(isset($data[$tb.$sep.'id'])){
						$_idx = explode($_gs,$data[$tb.$sep.'id']);
						foreach($_idx as $_i=>$_id)
							$row[$tb][$_id][$_col] = $_x[$_i];
					}
					else{
						foreach($_x as $_i=>$v)
							$row[$tb][$_i][$_col] = $v;
					}
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
		$table = [];
		if(is_array($data)||$data instanceof \ArrayAccess)
			foreach($data as $i=>$d){
				$id = isset($d['id'])?$d['id']:$i;
				$table[$id] = self::explodeAgg($d);
			}
		return $table;
	}	
	function inSelect($in,$select=null,$table=null){
		if(!isset($table))
			$table = $this->table;
		if(!isset($select))
			$select = $this->composer->select;
		foreach(array_keys($select) as $k)
			$select[$k] = $this->unQuote($select[$k]);
		if(in_array($in,$select))
			return true;
		if(in_array($this->formatColumnName($in),$select))
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
		return [$sql,$binds];
	}
	private static function nestBindingLoop($sql,$binds){
		$nBinds = [];
		$ln = 0;
		foreach($binds as $k=>$v){
			if(is_array($v)){
				if(is_integer($k))
					$find = '?';
				else
					$find = ':'.ltrim($k,':');
				$binder = [];
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
		return [$sql,$nBinds];
	}
	static function autoWrapCol($s,$table,$col){
		if($func=R::getTableColumnDef($table,$col,'readCol'))
			$s = $func.'('.$s.')';
		if(isset(AQueryWriter::$sqlFilters[QueryWriter::C_SQLFILTER_READ][$table])&&isset(AQueryWriter::$sqlFilters[QueryWriter::C_SQLFILTER_READ][$table][$col]))
			$s = AQueryWriter::$sqlFilters[QueryWriter::C_SQLFILTER_READ][$table][$col];
		return $s;
	}

	
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
		return call_user_func_array([$this,'ignoreFrom'],func_get_args());
	}
	function select(){
		if(!$this->ignoring('select',func_get_arg(0)))
			return $this->__call(__FUNCTION__,func_get_args());
	}
	function join(){
		if(!$this->ignoring('join',func_get_arg(0)))
			return $this->__call(__FUNCTION__,func_get_args());
	}
	protected static $listOfTables;
	protected static $heuristic;
	function heuristic($reload=null){ //todo mode frozen
		if(!$this->table)
			return;
		if(!isset(self::$heuristic[$this->table])||$reload){
			if(!isset(self::$listOfTables))
				self::$listOfTables = R::inspect();
			$tableL = strlen($this->table);
			$h = [];
			$h['fields'] = in_array($this->table,self::$listOfTables)?$this->listOfColumns($this->table,null,$reload):[];
			$h['shareds'] = [];
			$h['parents'] = [];
			$h['fieldsOwn'] = [];
			$h['owns'] = [];
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
			foreach($this->listOfColumns($parent,null,$reload) as $col){
				$this->select(self::autoWrapCol($q.$parent.$q.'.'.$q.$col.$q,$parent,$col).' as '.$q.$parent.'<'.$col.$q);
				$this->groupBy($q.$parent.$q.'.'.$q.$col.$q);
			}
			$this->join(" LEFT OUTER JOIN {$q}{$parent}{$q} ON {$q}{$parent}{$q}.{$q}id{$q}={$q}{$this->table}{$q}.{$q}{$parent}_id{$q}");
			$this->groupBy($q.$parent.$q.'.'.$q.'id'.$q);
		}
		foreach($shareds as $share){
			foreach($fieldsShareds[$share] as $col)
				$this->select("{$agg}(".self::autoWrapCol("{$q}{$share}{$q}.{$q}{$col}{$q}",$share,$col)."{$aggc} {$sep} {$cc}) as {$q}{$share}<>{$col}{$q}");
			$rel = [$this->table,$share];
			sort($rel);
			$rel = implode('_',$rel);
			$this->join(" LEFT OUTER JOIN {$q}{$rel}{$q} ON {$q}{$rel}{$q}.{$q}{$this->table}_id{$q}={$q}{$this->table}{$q}.{$q}id{$q}");
			$this->join(" LEFT OUTER JOIN {$q}{$share}{$q} ON {$q}{$rel}{$q}.{$q}{$share}_id{$q}={$q}{$share}{$q}.{$q}id{$q}");
		}
		foreach($owns as $own){
			foreach($fieldsOwn[$own] as $col){
				if(strrpos($col,'_id')!==strlen($col)-3)
					$this->select("{$agg}(COALESCE(".self::autoWrapCol("{$q}{$own}{$q}.{$q}{$col}{$q}",$own,$col)."{$aggc},''{$aggc}) {$sep} {$cc}) as {$q}{$own}>{$col}{$q}");
			}
			$this->join(" LEFT OUTER JOIN {$q}{$own}{$q} ON {$q}{$own}{$q}.{$q}{$this->table}_id{$q}={$q}{$this->table}{$q}.{$q}id{$q}");
		}
		if(!(empty($parents)&&empty($shareds)&&empty($owns))){
			$this->groupBy($q.$this->table.$q.'.'.$q.'id'.$q);
			foreach($fields as $field)
				$this->groupBy($q.$this->table.$q.'.'.$q.$field.$q);
		}
	}
	function count4D(){
		$queryCount = clone $this;
		$queryCount->autoSelectJoin();
		$queryCount->unSelect();
		$queryCount->select('id');
		return (int)model::newSelect('COUNT(*)')->from('('.$queryCount->getQuery().') as TMP_count',$queryCount->getParams())->getCell();
	}
	function table4D(){
		$this->selectNeed();
		$this->autoSelectJoin();
		$data = $this->getAll4D();
		if(control::devHas(control::dev_model_data))
			print('<pre>'.htmlentities(print_r($data,true)).'</pre>');
		return $data;
	}
	function row4D($compo=[],$params=[]){
		$this->selectNeed();
		$this->autoSelectJoin();
		$this->limit(1);
		$row = $this->getRow4D();
		if(control::devHas(control::dev_model_data))
			print('<pre>'.htmlentities(print_r($row,true)).'</pre>');
		return $row;
	}
}