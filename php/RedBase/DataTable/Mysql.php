<?php
namespace RedBase\DataTable;
class Mysql extends SQL{
	function fullTextSearch($text){
		if($this->dataSource->fulltextAvailableOnInnoDB())
			return call_user_func_array([$this,'fullTextSearchInnoDB'],func_get_args());
		else
			return call_user_func_array([$this,'fullTextSearchMyISAM'],func_get_args());
	}
	function fullTextSearchInnoDB($text,$mode='',$columns=[]){
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
		$table = $this->dataSource->escTable($this->name);
		$this->dataSource->addFtsIndex($this->name,$columns,$this->primaryKey,$this->uniqTextKey,$this->fullTextSearchLocale);
		$cols = '`'.implode('`,`',$columns).'`';
		$this->where('MATCH('.$cols.') AGAINST (? '.$mode.')',[$text]);
		$this->select('MATCH('.$cols.') AGAINST (? '.$mode.') AS _rank',[$text]);
		$this->select($table.'.*');
		$this->orderBy('_rank DESC');
		$this->setCounter(function()use($cols,$table,$text){
			return $this->dataSource->getCell('SELECT COUNT(IF(MATCH ('.$cols.') AGAINST (?), 1, NULL)) FROM '.$table,[$text]);
		});
	}
	function fullTextSearchMyISAM($text){
		
	}
}