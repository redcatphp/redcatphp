<?php
namespace Wild\DataMap\DataTable;
use Wild\DataMap\Exception;
use Wild\DataMap\DataTable;
use Wild\DataMap\SqlComposer\Select;
class SQL extends DataTable{
	private $stmt;
	private $row;
	protected $select;
	protected $hasSelectRelational;
	function __construct($name,$primaryKey='id',$uniqTextKey='uniq',$dataSource){
		parent::__construct($name,$primaryKey,$uniqTextKey,$dataSource);		
		$this->select = $this->createSelect();
	}
	function exists(){
		return $this->dataSource->tableExists($this->name);
	}
	function fetch(){
		return $this->dataSource->fetch($this->select->getQuery(),$this->select->getParams());
	}
	function getRow(){
		$row = $this->dataSource->getRow($this->select->getQuery(),$this->select->getParams());
		if($this->hasSelectRelational)
			$row = $this->dataSource->explodeAgg($row);
		$row = $this->dataSource->arrayToEntity($row,$this->name);
		return $row;
	}
	function getAll(){
		$table = [];
		$all = $this->dataSource->getAll($this->select->getQuery(),$this->select->getParams());
		if($this->hasSelectRelational)
			$all = $this->dataSource->explodeAggTable($all);
		foreach($all as $row){
			$row = $this->dataSource->arrayToEntity($row,$this->name);
			if(isset($row->{$this->primaryKey}))
				$table[$row->{$this->primaryKey}] = $row;
			else
				$table[] = $row;
		}
		return $table;
	}
	function rewind(){
		if(!$this->exists())
			return;
		$this->stmt = $this->fetch();
		$this->next();
	}
	function current(){
		return $this->row;
	}
	function key(){
		if($this->row)
			return $this->row->{$this->primaryKey};
	}
	function valid(){
		return (bool)$this->row;
	}
	function next(){
		$this->row = $this->dataSource->entityFactory($this->name);
		$this->trigger('beforeRead',$this->row);
		$row = $this->stmt->fetch();
		if($row){
			if($this->hasSelectRelational){
				$row = $this->dataSource->explodeAgg($row);
			}
			foreach($row as $k=>$v){
				$this->row->$k = $v;
			}
			if($this->useCache)
				$this->data[$this->row->{$this->primaryKey}] = $this->row;
		}
		$this->trigger('afterRead',$this->row);
		if(!$row){
			$this->row = null;
		}
	}
	function count(){
		if($this->counterCall)
			return call_user_func($this->counterCall,$this);
		else
			return $this->countSimple();
	}
	function countSimple(){
		if(!$this->exists())
			return;
		$select = $this->select
			->getClone()
			->unOrderBy()
			->unSelect()
			->select('COUNT(*)')
		;
		return (int)$this->dataSource->getCell($select->getQuery(),$select->getParams());
	}
	function countNested(){
		if(!$this->exists())
			return;
		$select = $this->createSelect();
		$queryCount = $this->select
			->getClone()
			->unOrderBy()
			->unSelect()
			->select($this->primaryKey)
		;
		$select
			->select('COUNT(*)')
			->from('('.$queryCount->getQuery().') as TMP_count',$queryCount->getParams())
		;
		return (int)$this->dataSource->getCell($select->getQuery(),$select->getParams());
	}
	function countAll(){
		if(!$this->exists())
			return;
		$select = $this->createSelect();
		$select
			->select('COUNT(*)')
			->from($this->name)
		;
		return (int)$this->dataSource->getCell($select->getQuery(),$select->getParams());
	}
	function createSelect(){
		return new Select(
			$this->name,
			$this->dataSource->getQuoteCharacter(),
			$this->dataSource->getTablePrefix()
		);
	}
	function getClone(){
		return clone $this;
	}
	function __clone(){
		if(isset($this->select))
			$this->select = clone $this->select;
	}
	
	function selectMany2many($select,$colAlias=null){
		return $this->selectRelational('<>'.$select,$colAlias);
	}
	function selectMany($select,$colAlias=null){
		return $this->selectRelational('>'.$select,$colAlias);
	}
	function selectOne($select,$colAlias=null){
		return $this->selectRelational('<'.$select,$colAlias);
	}
	function selectRelational($select,$colAlias=null){
		$this->hasSelectRelational = true;
		$table = $this->dataSource->escTable($this->name);
		$this->select($table.'.*');
		if(is_array($select)){
			foreach($select as $k=>$s)
				if(is_integer($k))
					$this->selectRelationnal($s,null);
				else
					$this->selectRelationnal($k,$s);
			return $this;
		}
		$this->processRelational($select,$colAlias,true);
		return $this;
	}
	function processRelational($select,$colAlias=null,$autoSelectId=false){
		$sql = [];
		$type = '';
		$typeParent = $this->name;
		$prefix = $this->dataSource->getTablePrefix();
		$q = $this->dataSource->getQuoteCharacter();
		$aliasParent = $prefix.$this->name;
		$shareds = [];
		$selection = explode('~',ltrim(str_replace(['<','>','<>','<~~>','.'],['~<~','~>~','~<>~','<>','~.~'],$select),'~'));
		$relation = null;
		foreach($selection as $i=>$token){
			if(in_array($token,['<>','<','>','.'])){
				$pkP = $this->dataSource[$typeParent]->primaryKey;
				if(!isset($selection[$i+1]))
					throw new Exception('Unexpected end of relational declaration expecting table or column name after "'.$token.'" in '.$select);				
				switch($token){
					case '<>':
						$type = $selection[$i+1];
						list($type,$alias) = self::specialTypeAliasExtract($type,$superalias);
						$pkT = $this->dataSource[$type]->primaryKey;
						if(substr($type,-1)=='2'){
							if($type==$alias)
								$alias = substr($alias,0,-1);
							$type = substr($type,0,-1);
							$two = true;
						}
						else
							$two = false;
						if($superalias)
							$alias = $superalias.'__'.($alias?$alias:$type);
						$rels = [$typeParent,$type];
						sort($rels);
						$imp = implode('_',$rels);
						$impt = $q.$prefix.$imp.$q.($superalias?' AS '.$q.$prefix.$superalias.'__'.$imp.$q:'');
						if($exist=($this->dataSource->tableExists($type)&&$this->dataSource->tableExists($imp))){
							if($superalias)
								$imp = $superalias.'__'.$imp;
							$joint = $type!=$alias?"{$q}{$prefix}$type{$q} AS {$q}{$prefix}$alias{$q}":$q.$prefix.$alias.$q;
							$sql[] = [$impt];
							$sql[] = [
								$joint,
								"{$q}{$prefix}$alias{$q}.{$q}{$pkT}{$q}={$q}{$prefix}$imp{$q}.{$q}{$type}".(!$two&&in_array($type,$shareds)?'2':'')."_{$pkT}{$q}",
								"{$q}$aliasParent{$q}.{$q}{$pkP}{$q}={$q}{$prefix}$imp{$q}.{$q}{$typeParent}".($two?'2':'')."_{$pkP}{$q}"
							];
							if(!$two)
								$shareds[] = $type;
						}
						$typeParent = $type;
						$aliasParent = $prefix.$alias;
						$type = '';
						$relation = '<>';
					break;
					case '>':
						list($type,$alias) = self::specialTypeAliasExtract($type,$superalias);
						$pkT = $this->dataSource[$type]->primaryKey;
						if($superalias)
							$alias = $superalias.'__'.$alias;
						$joint = $type!=$alias?"{$q}{$prefix}$type{$q} as {$q}{$prefix}$alias{$q}":$q.$prefix.$alias.$q;
						if($exist=($this->dataSource->tableExists($type)&&$this->dataSource->columnExists($type,$typeParent.'_'.$pkP))){
							$sql[] = [$joint,"{$q}$aliasParent{$q}.{$q}{$pkP}{$q}={$q}{$prefix}$alias{$q}.{$q}{$typeParent}_{$pkP}{$q}"];
						}
						$typeParent = $type;
						$aliasParent = $prefix.$alias;
						$type = '';
						$relation = '>';
					break;
					case '<':
						list($type,$alias) = self::specialTypeAliasExtract($type,$superalias);
						if(substr($type,-1)=='2'){
							if($type==$alias)
								$alias = substr($alias,0,-1);
							$type = substr($type,0,-1);
							$two = true;
						}
						else
							$two = false;
						$pkT = $this->dataSource[$type]->primaryKey;
						if($superalias)
							$alias = $superalias.'__'.$alias;
						$joint = $type!=$alias?"{$q}{$prefix}$type{$q} as {$q}{$prefix}$alias{$q}":$q.$prefix.$alias.$q;
						if($exist=($this->dataSource->tableExists($typeParent)&&$this->dataSource->columnExists($typeParent,$type.'_'.$pkT))){
							$sql[] = [$joint,"{$q}{$prefix}$alias{$q}.{$q}{$pkT}{$q}={$q}{$prefix}$typeParent{$q}.{$q}{$type}_{$pkT}{$q}"];
						}
						$typeParent = $type;
						$type = '';
						$relation = '<';
					break;
					case '.':
						$type = $selection[$i+1];
					break;
				}
			}
		}
		$Qt = new Select(
			null,
			$this->dataSource->getQuoteCharacter(),
			$this->dataSource->getTablePrefix()
		);
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
		$agg = $this->dataSource->getAgg();
		$aggc = $this->dataSource->getAggCaster();
		$sep = $this->dataSource->getSeparator();
		$cc = $this->dataSource->getConcatenator();
		if(!$colAlias)
			$colAlias = ($superalias?$superalias:$alias).$relation.$col;
		if($colAlias)
			$colAlias = ' AS '.$q.$colAlias.$q;
		if($autoSelectId)
			$idAlias = ' AS '.$q.($superalias?$superalias:$alias).$relation.$pkT.$q;
		$Qt2 = $Qt->getClone();
		if($exist){
			switch($relation){
				case '<':
					$Qt->select($this->dataSource->getReadSnippetCol($table,$col,$q.$prefix.$alias.$q.'.'.$q.$col.$q));
					$this->select('('.$Qt.') '.$colAlias);
					if($autoSelectId){
						$Qt2->select($q.$prefix.$alias.$q.'.'.$q.$pkT.$q);
						$this->select('('.$Qt2.') '.$idAlias);
					}
				break;
				case '>':
					$Qt->select("{$agg}(COALESCE(".$this->dataSource->getReadSnippetCol($table,$col,"{$q}{$prefix}{$alias}{$q}.{$q}{$col}{$q}")."{$aggc},''{$aggc}) {$sep} {$cc})");
					$this->select('('.$Qt.') '.$colAlias);
					if($autoSelectId){
						$Qt2->select("{$agg}(COALESCE({$q}{$prefix}{$alias}{$q}.{$q}{$pkT}{$q}{$aggc},''{$aggc}) {$sep} {$cc})");
						$this->select('('.$Qt2.') '.$idAlias);
					}
				break;
				case '<>':
					$Qt->select("{$agg}(".$this->dataSource->getReadSnippetCol($table,$col,"{$q}{$prefix}{$alias}{$q}.{$q}{$col}{$q}")."{$aggc} {$sep} {$cc})");
					$this->select('('.$Qt.') '.$colAlias);
					if($autoSelectId){
						$Qt2->select("{$agg}({$q}{$prefix}{$alias}{$q}.{$q}{$pkT}{$q}{$aggc} {$sep} {$cc})");
						$this->select('('.$Qt2.') '.$idAlias);
					}
				break;
			}
		}
	}
	static function specialTypeAliasExtract($type,&$superalias=null){
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
	function hasSelectRelational(){
		return $this->hasSelectRelational;
	}
	
	function getCol(){
		return $this->dataSource->getCol($this->getQuery(),$this->getParams());
	}
	function getCell(){
		return $this->dataSource->getCell($this->getQuery(),$this->getParams());
	}
	
	function tableJoin($table, $join, array $params = null){
		$this->select->tableJoin($table, $join, $params);
		return $this;
	}
	function joinAdd($join,array $params = null){
		$this->select->joinAdd($join, $params);
		return $this;
	}
	function join($join, array $params = null){
		$this->select->join($join, $params);
		return $this;
	}
	function joinLeft($join, array $params = null){
		$this->select->joinLeft($join, $params);
		return $this;
	}
	function joinRight($join, array $params = null){
		$this->select->joinRight($join, $params);
		return $this;
	}
	function joinOn($join, array $params = null){
		$this->select->joinOn($join, $params);
		return $this;
	}
	function from($table, array $params = null){
		$this->select->from($table, $params);
		return $this;
	}
	function unTableJoin($table=null,$join=null,$params=null){
		$this->select->unTableJoin($table,$join,$params);
		return $this;
	}
	function unJoin($join=null,$params=null){
		$this->select->unJoin($join,$params);
		return $this;
	}
	function unFrom($table=null,$params=null){
		$this->select->unFrom($table,$params);
		return $this;
	}
	function setParam($k,$v){
		$this->select->set($k,$v);
		return $this;
	}
	function getParam($k){
		return $this->select->get($k);
	}
	function unWhere($where=null,$params=null){
		$this->select->unWhere($where,$params);
		return $this;
	}
	function unWith($with=null,$params=null){
		$this->select->unWith($with,$params);
		return $this;
	}
	function unWhereIn($where,$params=null){
		$this->select->unWhereIn($where,$params);
		return $this;
	}
	function unWhereOp($column, $op,  array $params=null){
		$this->select->unWhereOp($column, $op, $params);
		return $this;
	}
	function unOpenWhereAnd(){
		$this->select->unOpenWhereAnd();
		return $this;
	}
	function unOpenWhereOr(){
		$this->select->unOpenWhereOr();
		return $this;
	}
	function unOpenWhereNotAnd(){
		$this->select->unOpenWhereNotAnd();
		return $this;
	}
	function unOpenWhereNotOr(){
		$this->select->unOpenWhereNotOr();
		return $this;
	}
	function unCloseWhere(){
		$this->select->unCloseWhere();
		return $this;
	}
	function where($where, array $params = null){
		$this->select->where($where, $params);
		return $this;
	}
	function whereIn($where, array $params){
		$this->select->whereIn($where, $params);
		return $this;
	}
	function whereOp($column, $op, array $params=null){
		$this->select->whereOp($column, $op, $params);
		return $this;
	}
	function openWhereAnd(){
		$this->select->openWhereAnd();
		return $this;
	}
	function openWhereOr(){
		$this->select->openWhereOr();
		return $this;
	}
	function openWhereNotAnd(){
		$this->select->openWhereNotAnd();
		return $this;
	}
	function openWhereNotOr(){
		$this->select->openWhereNotOr();
		return $this;
	}
	function closeWhere(){
		$this->select->closeWhere();
		return $this;
	}
	function with($with, array $params = null){
		$this->select->with($with, $params);
		return $this;
	}
	function select($select, array $params = null){
		$this->select->select($select, $params);
		return $this;
	}
	function distinct($distinct = true){
		$this->select->distinct($distinct);
		return $this;
	}
	function groupBy($group_by, array $params = null){
		$this->select->groupBy($group_by, $params);
		return $this;
	}
	function withRollup($with_rollup = true){
		$this->select->withRollup($with_rollup);
		return $this;
	}
	function orderBy($order_by, array $params = null){
		$this->select->orderBy($order_by, $params);
		return $this;
	}
	function sort($desc=false){
		$this->select->sort($desc);
		return $this;
	}
	function limit($limit){
		$this->select->limit($limit);
		return $this;
	}
	function offset($offset){
		$this->select->offset($offset);
		return $this;
	}
	function having($having, array $params = null){
		$this->select->having($having, $params);
		return $this;
	}
	function havingIn($having, array $params){
		$this->select->havingIn($having, $params);
		return $this;
	}
	function havingOp($column, $op, array $params=null){
		$this->select->havingOp($column, $op, $params);
		return $this;
	}
	function openHavingAnd(){
		$this->select->openHavingAnd();
		return $this;
	}
	function openHavingOr(){
		$this->select->openHavingOr();
		return $this;
	}
	function openHavingNotAnd(){
		$this->select->openHavingNotAnd();
		return $this;
	}
	function openHavingNotOr(){
		$this->select->openHavingNotOr();
		return $this;
	}
	function closeHaving(){
		$this->select->closeHaving();
		return $this;
	}
	function unSelect($select=null, array $params = null){
		$this->select->unSelect($select, $params);
		return $this;
	}
	function unDistinct(){
		$this->select->unDistinct();
		return $this;
	}
	function unGroupBy($group_by=null, array $params = null){
		$this->select->unGroupBy($group_by, $params);
		return $this;
	}
	function unWithRollup(){
		$this->select->unWithRollup();
		return $this;
	}
	function unOrderBy($order_by=null, array $params = null){
		$this->select->unOrderBy($order_by, $params);
		return $this;
	}
	function unSort(){
		$this->select->unSort();
		return $this;
	}
	function unLimit(){
		$this->select->unLimit();
		return $this;
	}
	function unOffset(){
		$this->select->unOffset();
		return $this;
	}
	function unHaving($having=null, array $params = null){
		$this->select->unHaving($having,  $params);
		return $this;
	}
	function unHavingIn($having, array $params){
		$this->select->unHavingIn($having, $params);
		return $this;
	}
	function unHavingOp($column, $op, array $params=null){
		$this->select->unHavingOp($column, $op,  $params);
		return $this;
	}
	function unOpenHavingAnd(){
		$this->select->unOpenHavingAnd();
		return $this;
	}
	function unOpenHavingOr(){
		$this->select->unOpenHavingOr();
		return $this;
	}
	function unOpenHavingNotAnd(){
		$this->select->unOpenHavingNotAnd();
		return $this;
	}
	function unOpenHavingNotOr(){
		$this->select->unOpenHavingNotOr();
		return $this;
	}
	function unCloseHaving(){
		$this->select->unCloseHaving();
		return $this;
	}
	function hasColumn(){
		return $this->select->hasColumn();
	}
	function getColumn(){
		return $this->select->getColumn();
	}
	function hasTable(){
		return $this->select->hasTable();
	}
	function getTable(){
		return $this->select->getTable();
	}
	function hasJoin(){
		return $this->select->hasJoin();
	}
	function getJoin(){
		return $this->select->getJoin();
	}
	function hasFrom(){
		return $this->select->hasFrom();
	}
	function getFrom(){
		return $this->select->getFrom();
	}
	function hasWhere(){
		return $this->select->hasWhere();
	}
	function hasWith(){
		return $this->select->hasWith();
	}
	function getWhere(){
		return $this->select->getWhere();
	}
	function getWith(){
		return $this->select->getWith();
	}
	function hasSelect(){
		return $this->select->hasSelect();
	}
	function getSelect(){
		return $this->select->getSelect();
	}
	function hasDistinct(){
		return $this->select->hasDistinct();
	}
	function hasGroupBy(){
		return $this->select->hasGroupBy();
	}
	function getGroupBy(){
		return $this->select->getGroupBy();
	}
	function hasWithRollup(){
		return $this->select->hasWithRollup();
	}
	function hasHaving(){
		return $this->select->hasHaving();
	}
	function getHaving(){
		return $this->select->getHaving();
	}
	function hasOrderBy(){
		return $this->select->hasOrderBy();
	}
	function getOrderBy(){
		return $this->select->getOrderBy();
	}
	function hasSort(){
		return $this->select->hasSort();
	}
	function getSort(){
		return $this->select->getSort();
	}
	function hasLimit(){
		return $this->select->hasLimit();
	}
	function getLimit(){
		return $this->select->getLimit();
	}
	function hasOffset(){
		return $this->select->hasOffset();
	}
	function getOffset(){
		return $this->select->getOffset();
	}
	
	function getQuery(){
		return $this->select->getQuery();
	}
	function getParams(){
		return $this->select->getParams();
	}
}