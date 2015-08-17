<?php
namespace Wild\DataMap\DataTable;
class Pgsql extends SQL{
	protected $fulltextHeadline = [
		'MaxFragments'=>2,
		'MaxWords'=>25,
		'MinWords'=>20,
		'ShortWord'=>3,
		'FragmentDelimiter'=>' ... ',
		'StartSel'=>'<b>',
		'StopSel'=>'</b>',
		'HighlightAll'=>false,
	];
	protected $fullTextSearchLang;
	function setFullTextSearchLang($lang){
		if(!preg_match('/[a-z]/i',$lang))
			throw new Exception('Lang "'.$lang.'" is not a valid lang name');
		$this->fullTextSearchLang = $lang;
	}
	function setFulltextHeadline($config){
		$this->fulltextHeadline = $config+$this->fulltextHeadline;
	}
	function getFulltextHeadlineString(){
		$conf = '';
		foreach($this->fulltextHeadline as $k=>$v){
			if(is_bool($v))
				$conf .= $k.'='.($v?'TRUE':'FALSE').',';
			elseif(is_string($v))
				$conf .= $k.'="'.$v.'",';
			else
				$conf .= $k.'='.$v.',';
		}
		$conf = rtrim($conf,',');
		return $conf;
	}

	function fullTextSearch($text,$columns=[],$alias=null,$toVector=null){
		$indexName = $this->dataSource->addFtsColumn($this->name,$columns,$this->primaryKey,$this->uniqTextKey,$this->fullTextSearchLang);
		$lang = $this->fullTextSearchLang?"'".$this->fullTextSearchLang."',":'';
		$c = $this->select->formatColumnName($indexName);
		if(!$alias) $alias = $indexName.'_rank';
		$table = $this->dataSource->escTable($this->name);
		foreach(array_keys($columns) as $k){
			$columns[$k] = $this->select->formatColumnName($columns[$k]);
			if($toVector)
				$columns[$k] = 'to_tsvector('.$columns[$k].')';
		}
		$this->select("ts_rank({$c}, plainto_tsquery({$lang}?)) as $alias",[$text]);
		$sufx = $this->dataSource->getFtsTableSuffix();
		$sufxL = -1*strlen($sufx);
		foreach($this->dataSource->getColumns($this->name) as $col=>$colType){
			if(substr($col,0,6)!='_auto_'&&substr($col,$sufxL)!=$sufx){
				$col = $this->dataSource->esc($col);
				$this->select($table.'.'.$col.' as '.$col);
			}
		}
		$snippet = [];
		$headline = $this->getFulltextHeadlineString();
		$selectParams = [];
		foreach($columns as $v){
			$snippet[] = 'COALESCE(ts_headline('.$v.',plainto_tsquery('.$lang.'?),?),\'\')';
			$selectParams[] = $text;
			$selectParams[] = $headline;
		}
		$this->select(implode('||\''.$this->fulltextHeadline['FragmentDelimiter'].'\'||',$snippet).' as _snippet',$selectParams);
		$this->orderBy("ts_rank({$c}, plainto_tsquery({$lang}?))",[$text]);
		$this->where($table.'."'.$indexName.'" @@ plainto_tsquery('.$lang.'?)',[$text]);
		$this->setCounter(function()use($table,$indexName,$text){
			return $this->dataSource->getCell('SELECT COUNT(*) FROM '.$table.' WHERE '.$table.'."'.$indexName.'"  @@ plainto_tsquery(?)',[$text]);
		});
	}
}