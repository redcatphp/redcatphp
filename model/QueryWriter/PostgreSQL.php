<?php namespace surikat\model\QueryWriter;
use surikat\model\RedBeanPHP\Adapter;
use surikat\model\R;
use surikat\model\Table;
use surikat\model\Query;
class PostgreSQL extends \surikat\model\RedBeanPHP\QueryWriter\PostgreSQL {
	use AQueryWriter;
	const C_DATATYPE_FULLTEXT             = 20;
	protected $separator = ',';
	protected $agg = 'string_agg';
	protected $aggCaster = '::text';
	protected $sumCaster = '::int';
	protected $concatenator = 'chr(29)';
	function __construct(Adapter $adapter){
		parent::__construct($adapter);
		$this->addSqlColumnType(self::C_DATATYPE_FULLTEXT,' tsvector ');
	}
	function addColumnFulltext($table, $col){
		$this->addColumn($table,$col,$this->code('tsvector'));
	}
	function buildColumnFulltext($table, $col, $cols ,$lang=''){
		$sqlUpdate = $this->buildColumnFulltextSQL($table, $col, $cols ,$lang);
		$this->adapter->exec($sqlUpdate);
	}
	function buildColumnFulltextSQL($table, $col, $cols ,$lang=''){
		$agg = $this->agg;
		$aggc = $this->aggCaster;
		$sep = $this->separator;
		$cc = "' '";
		$q = $this->quoteCharacter;
		$id = $this->esc('id');
		$tb = $this->esc($table);
		$_tb = $this->esc('_'.$table);
		$groupBy = array();
		$columns = array();
		$tablesJoin = array();
		if($lang)
			$lang = "'$lang',";
		foreach($cols as $select){
			$shareds = array();
			$typeParent = $table;
			$aliasParent = $table;
			$type = '';
			$l = strlen($select);
			$weight = '';
			$relation = null;
			for($i=0;$i<$l;$i++){
				switch($select[$i]){
					case '/':
						$i++;
						while(isset($select[$i])){
							$weight .= $select[$i];
							$i++;
						}
						$weight = trim($weight);
					break;
					case '.':
					case '>': //own
						list($type,$alias) = Query::typeAliasExtract($type,$superalias);
						if($superalias)
							$alias = $superalias.'__'.$alias;
						$joint = $type!=$alias?"{$q}$type{$q} as {$q}$alias{$q}":$q.$alias.$q;
						$tablesJoin[] = "LEFT OUTER JOIN $joint ON {$q}$aliasParent{$q}.{$q}id{$q}={$q}$alias{$q}.{$q}{$typeParent}_id{$q}";
						$typeParent = $type;
						$aliasParent = $alias;
						$type = '';
						$relation = '>';
					break;
					case '<':
						list($type,$alias) = Query::typeAliasExtract($type,$superalias);
						if(isset($select[$i+1])&&$select[$i+1]=='>'){ //shared
							$i++;
							if($superalias)
								$alias = $superalias.'__'.($alias?$alias:$type);
							$rels = array($typeParent,$type);
							sort($rels);
							$imp = implode('_',$rels);
							$join[$imp][] = $alias;
							$tablesJoin[] = "LEFT OUTER JOIN $q$imp$q ON {$q}$typeParent{$q}.{$q}id{$q}={$q}$imp{$q}.{$q}{$typeParent}_id{$q}";
							$joint = $type!=$alias?"{$q}$type{$q} as {$q}$alias{$q}":$q.$alias.$q;
							$tablesJoin[] = "LEFT OUTER JOIN $joint ON {$q}$alias{$q}.{$q}id{$q}={$q}$imp{$q}.{$q}{$type}".(in_array($type,$shareds)?2:'')."_id{$q}";
							$shareds[] = $type;
							$typeParent = $type;
							$relation = '<>';
						}
						else{ //parent
							if($superalias)
								$alias = $superalias.'__'.$alias;
							$join[$type][] = ($alias?array($typeParent,$alias):$typeParent);
							$joint = $type!=$alias?"{$q}$type{$q} as {$q}$alias{$q}":$q.$alias.$q;
							$tablesJoin[] = "LEFT OUTER JOIN $joint ON {$q}$alias{$q}.{$q}id{$q}={$q}$typeParent{$q}.{$q}{$type}_id{$q}";
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
			$localTable = $typeParent;
			$localCol = trim($type);
			switch($relation){
				default:
				case '<':
					$c = Query::autoWrapCol($q.$localTable.$q.'.'.$q.$localCol.$q,$localTable,$localCol);
					$gb = $q.$localTable.$q.'.'.$q.'id'.$q;
					if(!in_array($gb,$groupBy))
						$groupBy[] = $gb;
				break;
				case '>':
					$c = "{$agg}(COALESCE(".Query::autoWrapCol("{$q}{$localTable}{$q}.{$q}{$localCol}{$q}",$localTable,$localCol)."{$aggc},''{$aggc}) {$sep} {$cc})";
				break;
				case '<>':
					$c = "{$agg}(".Query::autoWrapCol("{$q}{$localTable}{$q}.{$q}{$localCol}{$q}",$localTable,$localCol)."{$aggc} {$sep} {$cc})";
				break;
			}
			$c = "to_tsvector($lang$c)";
			if($weight)
				$c = "setweight($c,'$weight')";
			$columns[] = $c;
		}
		$sqlUpdate = 'UPDATE '.$tb.' as '.$_tb;
		$sqlUpdate .= ' SET '.$col.'=(SELECT '.implode("||",$columns);
		$sqlUpdate .= ' FROM '.$tb;
		$sqlUpdate .= implode(" \n",$tablesJoin);
		$sqlUpdate .= ' WHERE '.$tb.'.'.$id.'='.$_tb.'.'.$id;
		if(!empty($groupBy))
			$sqlUpdate .= ' GROUP BY '.implode(',',$groupBy);
		$sqlUpdate .= ')';
		return $sqlUpdate;
	}
	function addIndexFullText($table, $col, $name = null ){
		if(!isset($name))
			$name = $table.'_'.$col.'_fulltext';
		$col  = $this->esc($col);
		$table  = $this->esc( $table );
		$name   = preg_replace( '/\W/', '', $name );
		if($this->adapter->getCell( "SELECT COUNT(*) FROM pg_class WHERE relname = '$name'" ))
			return;
		try{
			$this->adapter->exec("CREATE INDEX $name ON $table USING gin($col) ");
		}
		catch (\Exception $e ) {
		}
	}
	function handleFullText($table, $col, Array $cols, Table &$model, $lang=''){
		//$col = $this->esc(R::toSnake($col));
		if($lang)
			$lang .= ',';
		$w =& $this;
		$model->on('changed',function($bean)use(&$w,$table,$col,$cols,$lang){
			$that->adapter->exec($w->buildColumnFulltextSQL($table,$col,$cols,$lang).' WHERE id=?',array($bean->id));
		});
		
	}
		
}