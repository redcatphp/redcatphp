<?php
namespace RedBase\DataTable;
class Mysql extends SQL{
	function fullTextSearch($text){
		if($this->dataSource->fulltextAvailableOnInnoDB())
			return call_user_func_array([$this,'fullTextSearchInnoDB'],func_get_args());
		else
			return call_user_func_array([$this,'fullTextSearchMyISAM'],func_get_args());
	}
	function fullTextSearchInnoDB($text,$columns=[]){
		$this->dataSource->addFtsIndex($this->name,$columns,$this->primaryKey,$this->uniqTextKey,$this->fullTextSearchLocale);
		$this->where('MATCH(`'.implode('`,`',$columns).'`) AGAINST (?)',[$text]);
	}
	function fullTextSearchMyISAM($text){
		
	}
}