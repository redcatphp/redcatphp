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
		$this->where('MATCH(`'.implode('`,`',$columns).'`) AGAINST (? '.$mode.')',[$text]);
		$this->select('MATCH(`'.implode('`,`',$columns).'`) AGAINST (? '.$mode.') AS _rank',[$text]);
		$this->select($table.'.*');
		$this->orderBy('_rank DESC');
	}
	function fullTextSearchMyISAM($text){
		
	}
}