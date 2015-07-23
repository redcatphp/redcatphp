<?php
namespace RedBase\DataTable;
use RedBase\Exception;
class Sqlite extends SQL{
	private $fullTextSearchLocale;
	function setFullTextSearchLocale($locale){
		if(!preg_match('/[a-z]{2,3}\_[A-Z]{2,3}$/',$locale))
			throw new Exception('Locale "'.$locale.'" is not a valid locale name');
		$this->fullTextSearchLocale = $locale;
	}
	function fullTextSearch($text,$tokensNumber=30,$targetColumnIndex=-1,
		$start='<b>',$end='</b>',$sep='<b>...</b>',$columns=[]
	){
		if($tokensNumber>64)
			$tokensNumber = 64;
		$sufx = '_fulltext_';
		$ftsTable = $this->dataSource->escTable($this->name.$sufx);
		$table = $this->dataSource->escTable($this->name);
		$pk = $this->dataSource->esc($this->primaryKey);
		if(!$this->dataSource->tableExists($this->name.$sufx)){
			if($this->fullTextSearchLocale)
				$tokenize = 'icu '.$this->fullTextSearchLocale;
			else
				$tokenize = 'porter';
			if(empty($columns)){
				$sufxL = -1*strlen($sufx);
				foreach($this->dataSource->getColumns($this->name) as $col=>$type){
					if(($col==$this->uniqTextKey||substr($col,$sufxL)==$sufx)&&$type=='TEXT')
						$columns[] = $col;
				}
			}
			else{
				foreach($columns as &$col){
					$col = $this->dataSource->esc($col);
				}
			}
			$cols = '`'.implode('`,`',$columns).'`';
			$this->dataSource->execute('CREATE VIRTUAL TABLE '.$ftsTable.' USING fts4('.$cols.', tokenize='.$tokenize.')');
			$this->dataSource->execute('INSERT INTO '.$ftsTable.'(docid,'.$cols.') SELECT '.$this->dataSource->esc($this->primaryKey).','.$cols.' FROM '.$table);
		}		
		$this->select("snippet($ftsTable,?,?,?,?,?) as _snippet",
			[$start,$end,$sep,(int)$targetColumnIndex,(int)$tokensNumber]);
		$this->select("docid as $pk");
		$this->select("$table.*");
		$this->from($ftsTable);
		$this->unFrom($table);
		$limit = $this->getLimit();
		$offset = $this->getOffset();
		if($limit)
			$limit = 'LIMIT '.$limit;
		if($offset)
			$offset = 'OFFSET '.$offset;
		$this->join("(
			SELECT docid as $pk, matchinfo($ftsTable) AS rank
				FROM $ftsTable 
				WHERE $ftsTable MATCH ?
				ORDER BY rank DESC
				$limit $offset
		) AS _ranktable USING($pk)",[$text]);
		$this->where($ftsTable.' MATCH ?',[$text]);
		$this->orderBy('_ranktable.rank DESC');
		$this->setCounter(function()use($ftsTable,$text){
			if(!$this->exists())
				return;
			return (int)$this->dataSource->getCell("SELECT COUNT(*) FROM $ftsTable WHERE $ftsTable MATCH ?",[$text]);
		});
	}
}