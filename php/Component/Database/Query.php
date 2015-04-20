<?php namespace Surikat\Component\Database;
use ArrayAccess;
use BadMethodCallException;
use Surikat\Component\Vars\ArrayObject;
use Surikat\Component\Vars\Arrays;
use Surikat\Component\Database\SqlFormatter;
use Surikat\Component\Database\R;
use Surikat\Component\Database\RedBeanPHP\Facade;
use Surikat\Component\Database\RedBeanPHP\QueryWriter;
use Surikat\Component\Database\RedBeanPHP\QueryWriter\AQueryWriter;
use Surikat\Component\DependencyInjection\MutatorMagicTrait;
class Query {
	use MutatorMagicTrait;
	protected $table;
	protected $pxTable;
	protected $prefix;
	protected $writer;
	protected $composer;
	protected $DataBase;
	protected static $listOfTables;
	function getDB(){
		return $this->DataBase;
	}
	function tableExists($table=null){
		if(!isset($table))
			$table = $this->table;
		return $this->writer->tableExists($table);
	}
	function columnExists($table,$column){
		return $this->writer->columnExists($table,$column);
	}
	function __construct($table=null,$composer='select',$db=null,$writer=null){
		if($table instanceof Facade){
			$db = $table;
			$table = null;
		}
		if($composer instanceof Facade){
			$db = $composer;
			$composer = 'select';
		}
		if(!$db)
			$db = $this->Database_R();
		$this->DataBase = $db;
		if(!$writer)
			$writer = $this->DataBase->getWriter();
		$this->writer = $writer;
		if(is_string($composer))
			$composer = SQLComposer::$composer();
		$this->composer = $composer;
		$this->composer->setWriter($writer);
		$this->composer->setQuery($this);
		$this->prefix = $this->writer->prefix;
		if(isset($table)){
			$this->setTable($table);
			$this->from($table);
		}
	}
	function setTable($table=null){
		$this->table = $table;
		$this->pxTable = $this->writer->prefix.$table;
	}
	function getTable(){
		return $this->table;
	}
	function getPrefix(){
		return $this->prefix;
	}
	function __destruct(){
		unset($this->composer);
	}
	function __toString(){
		return (string)$this->composer->getQuery();
	}
	function __clone(){
        $this->composer = clone $this->composer;
    }
	function ___call($f,$args){
		if(strpos($f,'get')===0&&ctype_upper(substr($f,3,1))){
			if(!$this->table||$this->tableExists($this->table)){
				$params = $this->composer->getParams();
				if(!empty($args))
					$params = array_merge($params,$args);
				$sql = $this->composer->getQuery();
				list($sql,$params) = R::nestBinding($sql,$params);
				return $this->DataBase->$f($sql,$params);
			}
			return;
		}
		else{
			switch($f){
				case 'orderByFullTextRank':
				case 'selectFullTextRank':
				case 'selectFullTextHighlight':
				case 'selectFullTextHighlightTruncated':
				case 'whereFullText':
					array_unshift($args,$this);
					call_user_func_array([$this->writer,$f],$args);
					return $this;
				break;
				case 'limit':
					call_user_func_array([$this->composer,'limit'],$args);
					return $this;
				break;
				default:
					if(substr($f,-5)=='Array'){
						$array = true;
						$f = substr($f,0,-5);
					}
					else{
						$array = false;
					}
					if(method_exists($this->composer,$f)){
						$un = strpos($f,'un')===0&&ctype_upper(substr($f,2,1));
						if(method_exists($this,$m='composer'.ucfirst($f)))
							$args = call_user_func_array([$this,$m],$args);
						$sql = array_shift($args);
						if($array)
							$binds = array_shift($args);
						else
							$binds = $args;
						if($sql instanceof SQLComposerBase){
							if(is_array($binds))
								$binds = array_merge($sql->getParams(),$binds);
							else
								$binds = $sql->getParams();
							$sql = '('.$sql->getQuery().')';
						}
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
		throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()',get_class($this),$f));
	}
	function getQuery($removeUnbinded=true){
		return $this->composer->getQuery($removeUnbinded);
	}
	function getParams(){
		return $this->composer->getParams();
	}
	function joinOwn($on){
		foreach((array)$this->joinOwnSQL($on) as $join){
			if(is_array($join)){
				foreach($join as $j){
					$this->join($j);
				}
			}
			else{
				$this->join($join);
			}
		}
		return $this;
	}
	function unJoinOwn($on){
		foreach((array)$this->joinOwnSQL($on) as $join){
			if(is_array($join)){
				foreach($join as $j){
					$this->unJoin($j);
				}
			}
			else{
				$this->unJoin($join);
			}
		}
		return $this;
	}
	function joinShared($on){
		foreach((array)$this->joinSharedSQL($on) as $join){
			if(is_array($join)){
				foreach($join as $j){
					$this->join($j);
				}
			}
			else{
				$this->join($join);
			}
		}
		return $this;
	}
	function unJoinShared($on){
		foreach((array)$this->joinSharedSQL($on) as $join){
			if(is_array($join)){
				foreach($join as $j){
					$this->unJoin($j);
				}
			}
			else{
				$this->unJoin($join);
			}
		}
		return $this;
	}
	function selectTruncation($col,$truncation=369,$getl=true){
		$c = $this->formatColumnName($col);
		$this->composer->select("SUBSTRING($c,1,$truncation) as $col");
		if($getl)
			$this->composer->select("LENGTH($c) as {$col}_length");
		return $this;
	}
	function relationShared($rel){
		$a = [$this->table,$rel];
		sort($a);
		return implode('_',$a);
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
	protected function composerGroupBy(){
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
	protected function composerWhere(){
		$args = func_get_args();
		if(isset($args[0])&&is_array($args[0]))
			$args[0] = implode(' AND ',$args[0]);
		return $args;
	}
	function unQuote($v){
		return trim($v,$this->writer->quoteCharacter);
	}
	function quote($v){
		if($v=='*')
			return $v;
		return $this->writer->quoteCharacter.$this->unQuote($v).$this->writer->quoteCharacter;
	}
	function formatColumnName($v){
		if($this->table&&strpos($v,'(')===false&&strpos($v,')')===false&&strpos($v,' as ')===false&&strpos($v,'.')===false)
			$v = $this->quote($this->pxTable).'.'.$this->quote($v);
		return $v;
	}	
	protected $listOfColumns = [];
	function listOfColumns($t,$details=null,$reload=null){
		if(!isset($this->listOfColumns[$t])||$reload)
			$this->listOfColumns[$t] = $this->DataBase->inspect($t);
		return $details?$this->listOfColumns[$t]:array_keys($this->listOfColumns[$t]);
	}
	function joinSharedSQL($share){
		if(is_array($share)){
			$r = [];
			foreach($share as $k=>$v)
				$r[$k] = $this->joinSharedSQL($this->table,$v);
			return $r;
		}
		$rel = [$this->table,$share];
		sort($rel);
		$rel = $this->writer->prefix.implode('_',$rel);
		$q = $this->writer->quoteCharacter;
		$sql = [];
		$sql[] = "{$q}{$rel}{$q} ON {$q}{$this->pxTable}{$q}.{$q}id{$q}={$q}{$rel}{$q}.{$q}{$this->table}_id{$q}";
		if($this->table!=$share){
			$shareTable = $this->writer->prefix.$share;
			$sql[] = "{$q}{$shareTable}{$q} ON {$q}{$shareTable}{$q}.{$q}id{$q}={$q}{$rel}{$q}.{$q}{$share}_id{$q}";
		}
		return $sql;
	}
	function joinOwnSQL($own){
		if(is_array($own)){
			$r = [];
			foreach($own as $k=>$v)
				$r[$k] = $this->joinOwnSQL($this->table,$v);
			return $r;
		}
		$q = $this->writer->quoteCharacter;
		return "{$q}{$this->writer->prefix}{$own}{$q} ON {$q}{$this->writer->prefix}{$own}{$q}.{$q}{$this->table}_id{$q}={$q}{$this->pxTable}{$q}.{$q}id{$q}";
	}
	function selectRelationnal($select,$colAlias=null){
		if(is_array($select)){
			foreach($select as $k=>$s)
				if(is_integer($k))
					$this->selectRelationnal($s,null);
				else
					$this->selectRelationnal($k,$s);
			return $this;
		}
		$this->processRelationnal($select,$colAlias,true);
		return $this;
	}
	function processRelationnal($select,$colAlias=null,$autoSelectId=false){
		$sql = [];
		$l = strlen($select);
		$type = '';
		$typeParent = $this->table;
		$aliasParent = $this->prefix.$this->table;
		$q = $this->writer->quoteCharacter;
		$shareds = [];
		for($i=0;$i<$l;$i++){
			switch($select[$i]){
				case '>': //own
					list($type,$alias) = $this->writer->specialTypeAliasExtract($type,$superalias);
					if($superalias)
						$alias = $superalias.'__'.$alias;
					$joint = $type!=$alias?"{$q}{$this->prefix}$type{$q} as {$q}{$this->prefix}$alias{$q}":$q.$this->prefix.$alias.$q;
					if($exist=($this->tableExists($type)&&$this->columnExists($type,$typeParent.'_id'))){
						$sql[] = [$joint,"{$q}$aliasParent{$q}.{$q}id{$q}={$q}{$this->prefix}$alias{$q}.{$q}{$typeParent}_id{$q}"];
					}
					$typeParent = $type;
					$aliasParent = $this->prefix.$alias;
					$type = '';
					$relation = '>';
				break;
				case '<':
					list($type,$alias) = $this->writer->specialTypeAliasExtract($type,$superalias);
					if(substr($type,-1)=='2'){
						if($type==$alias)
							$alias = substr($alias,0,-1);
						$type = substr($type,0,-1);
						$two = true;
					}
					else
						$two = false;
						
					if(isset($select[$i+1])&&$select[$i+1]=='>'){ //shared
						$i++;
						if($superalias)
							$alias = $superalias.'__'.($alias?$alias:$type);
						$rels = [$typeParent,$type];
						sort($rels);
						$imp = implode('_',$rels);
						$impt = $q.$this->prefix.$imp.$q.($superalias?' as '.$q.$this->prefix.$superalias.'__'.$imp.$q:'');
						if($exist=($this->tableExists($type)&&$this->tableExists($imp))){
							if($superalias)
								$imp = $superalias.'__'.$imp;
							$joint = $type!=$alias?"{$q}{$this->prefix}$type{$q} as {$q}{$this->prefix}$alias{$q}":$q.$this->prefix.$alias.$q;
							$sql[] = [$impt];
							$sql[] = [
								$joint,
								"{$q}{$this->prefix}$alias{$q}.{$q}id{$q}={$q}{$this->prefix}$imp{$q}.{$q}{$type}".(!$two&&in_array($type,$shareds)?'2':'')."_id{$q}",
								"{$q}$aliasParent{$q}.{$q}id{$q}={$q}{$this->prefix}$imp{$q}.{$q}{$typeParent}".($two?'2':'')."_id{$q}"
							];
							if(!$two)
								$shareds[] = $type;
						}
						$typeParent = $type;
						$aliasParent = $this->prefix.$alias;
						$relation = '<>';
					}
					else{ //parent
						if($superalias)
							$alias = $superalias.'__'.$alias;
						$joint = $type!=$alias?"{$q}{$this->prefix}$type{$q} as {$q}{$this->prefix}$alias{$q}":$q.$this->prefix.$alias.$q;
						if($exist=($this->tableExists($typeParent)&&$this->columnExists($typeParent,$type.'_id'))){
							$sql[] = [$joint,"{$q}{$this->prefix}$alias{$q}.{$q}id{$q}={$q}{$this->prefix}$typeParent{$q}.{$q}{$type}_id{$q}"];
						}
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
		$Qt = new static();
		$i = 0;
		foreach($sql as $_sql){
			if($i){
				$Qt->join(array_shift($_sql));
				if(!empty($_sql)){
					$Qt->joinOn(implode(' AND ',$_sql));
				}
			}
			else{
				$Qt->from(array_shift($_sql));
				if(!empty($_sql)){
					$Qt->where(implode(' AND ',$_sql));
				}
			}
			$i++;
		}		
		$table = $typeParent;
		$col = trim($type);
		$agg = $this->writer->agg;
		$aggc = $this->writer->aggCaster;
		$sep = $this->writer->separator;
		$cc = $this->writer->concatenator;
		$q = $this->writer->quoteCharacter;
		if(!$colAlias)
			$colAlias = ($superalias?$superalias:$alias).$relation.$col;
		if($colAlias)
			$colAlias = ' as '.$q.$colAlias.$q;
		if($autoSelectId)
			$idAlias = ' as '.$q.($superalias?$superalias:$alias).$relation.'id'.$q;
		$Qt2 = $Qt->getClone();
		if($exist){
			switch($relation){
				case '<':
					$Qt->select($this->writer->autoWrapCol($q.$this->prefix.$alias.$q.'.'.$q.$col.$q,$table,$col));
					$this->select('('.$Qt.') '.$colAlias);
					if($autoSelectId){
						$Qt2->select($q.$this->prefix.$alias.$q.'.'.$q.'id'.$q);
						$this->select('('.$Qt2.') '.$idAlias);
					}
				break;
				case '>':
					$Qt->select("{$agg}(COALESCE(".$this->writer->autoWrapCol("{$q}{$this->prefix}{$alias}{$q}.{$q}{$col}{$q}",$table,$col)."{$aggc},''{$aggc}) {$sep} {$cc})");
					$this->select('('.$Qt.') '.$colAlias);
					if($autoSelectId){
						$Qt2->select("{$agg}(COALESCE({$q}{$this->prefix}{$alias}{$q}.{$q}id{$q}{$aggc},''{$aggc}) {$sep} {$cc})");
						$this->select('('.$Qt2.') '.$idAlias);
					}
				break;
				case '<>':
					$Qt->select("{$agg}(".$this->writer->autoWrapCol("{$q}{$this->prefix}{$alias}{$q}.{$q}{$col}{$q}",$table,$col)."{$aggc} {$sep} {$cc})");
					$this->select('('.$Qt.') '.$colAlias);
					if($autoSelectId){
						$Qt2->select("{$agg}({$q}{$this->prefix}{$alias}{$q}.{$q}id{$q}{$aggc} {$sep} {$cc})");
						$this->select('('.$Qt2.') '.$idAlias);
					}
				break;
			}
		}
	}	
	function selectNeed($n='id'){
		if(!count($this->composer->select))
			$this->select('*');
		if(!$this->inSelect($n))
			$this->select($n);
		return $this;
	}
	function row(){
		return self::explodeAgg((array)$this->getRow());
	}
	function table(){
		return self::explodeAggTable((array)$this->getAll());
	}
	function tableRw(){
		return R::convertToBeans($this->table,$this->table());
	}
	function rowRw(){
		return R::convertToBeans($this->table,$this->row());
	}
	function tableObject(){
		return new ArrayObject($this->table());
	}
	function rowObject(){
		return new ArrayObject($this->row());
	}
	function tableRwObject(){
		return new ArrayObject($this->tableRw());
	}
	function rowRwObject(){
		return new ArrayObject($this->rowRw());
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
		if(!$this->table||$this->tableExists())
			return (int)(new static())
				->select('COUNT(*)')
				->fromArray('('.$queryCount->getQuery().') as TMP_count',$queryCount->getParams())
				->getCell()
			;
	}
	
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
	function debug(){
		return SqlFormatter::format($this->__toString());
	}
}