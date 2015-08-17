<?php
namespace Wild\DataMap\DataTable;
class Mysql extends SQL{
	function fullTextSearch($text,$mode='',$columns=[]){
		if($mode){
			switch(strtoupper($mode)){
				case 'EXP':
				case 'EXPANSION':
				case 'QUERY EXPANSION':
				case 'WITH QUERY EXPANSION':
					$mode = 'WITH QUERY EXPANSION';
				break;
				case 'BOOL':
				case 'BOOLEAN':
				case 'IN BOOLEAN MODE':
					$mode = 'IN BOOLEAN MODE';
				break;
				default:
					$mode = '';
				break;
			}
		}
		if($this->dataSource->fulltextAvailableOnInnoDB())
			$this->fullTextSearchInnoDB($text,$mode,$columns);
		else
			$this->fullTextSearchMyISAM($text,$mode,$columns);
	}
	function fullTextSearchInnoDB($text,$mode='',&$columns=[]){
		$table = $this->dataSource->escTable($this->name);
		$this->dataSource->addFtsIndex($this->name,$columns,$this->primaryKey,$this->uniqTextKey);
		$cols = '`'.implode('`,`',$columns).'`';
		$this->where('MATCH('.$cols.') AGAINST (? '.$mode.')',[$text]);
		$this->select('MATCH('.$cols.') AGAINST (? '.$mode.') AS _rank',[$text]);
		$this->select($table.'.*');
		$this->orderBy('_rank DESC');
		$this->setCounter(function()use($cols,$table,$text){
			return $this->dataSource->getCell('SELECT COUNT(IF(MATCH ('.$cols.') AGAINST (?), 1, NULL)) FROM '.$table,[$text]);
		});
	}
	function fullTextSearchMyISAM($text,$mode='',&$columns=[]){
		$table = $this->dataSource->escTable($this->name);
		$ftsTable = $this->dataSource->escTable($this->name.$this->dataSource->getFtsTableSuffix());
		$this->dataSource->makeFtsTableAndIndex($this->name,$columns,$this->primaryKey,$this->uniqTextKey);
		$cols = '`'.implode('`,`',$columns).'`';
		$pk = $this->dataSource->esc($this->primaryKey);
		$this->select($table.'.*');
		$this->unFrom($table);
		$limit = $this->getLimit();
		$offset = $this->getOffset();
		if($limit)
			$limit = 'LIMIT '.$limit;
		if($offset)
			$offset = 'OFFSET '.$offset;
		$this->join("(
			SELECT $ftsTable.$pk, MATCH($cols) AGAINST(? $mode) AS rank
				FROM $ftsTable
				WHERE MATCH($cols) AGAINST(? $mode)
				ORDER BY rank DESC
				$limit $offset
		) AS _ranktable ON _ranktable.$pk = $table.$pk",[$text,$text]);
		$this->orderBy('_ranktable.rank DESC');
		$this->setCounter(function()use($cols,$ftsTable,$text){
			return $this->dataSource->getCell('SELECT COUNT(IF(MATCH ('.$cols.') AGAINST (?), 1, NULL)) FROM '.$ftsTable,[$text]);
		});
	}
}