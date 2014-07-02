<?php namespace surikat\control;
use control;
use model\R;
//use control\CsvIterator;
class CsvImporter{
	private $debug;
	private $utf8_encode;
	private $separator = ';';
	private $csvDir;
	function __construct($options){
		$this->csvDir = control::$CWD.'/.data/';
		foreach($options as $k=>$v)
			$this->$k = $v;
	}
	static function importation($table,$allCols,$orderCols=null,$options=array()){
		$o = new CsvImporter($options);
		$r = $o->import($table,$allCols,$orderCols);
		unset($o);
		return $r;
	}
	function import($table,$allCols,$orderCols=null){
		$csvFile = $this->csvDir.$table.'.csv';
		$csvIterator = new CsvIterator($csvFile,$this->separator);
		if($orderCols)
			$csvIterator->setKeys(array_combine((array)$orderCols,(array)$allCols));
		else
			$csvIterator->setKeys((array)$allCols);
		$o = &$this;
		$csvIterator->setCallback(function(&$line)use(&$o){
			foreach($line as $k=>&$v){
				if(is_string($v)){
					if($o->utf8_encode)
						$v = utf8_encode($v);
				}
			}
		});
		$missingCols = array();
		$completesCols = array();
		foreach($csvIterator as $i=>$data){
			if($this->debug)
				print_r($data);
			$b = R::dispense($table);
			foreach($data as $k=>$v)
				$b->$k = $v;
			foreach($allCols as $k)
				if(!in_array($k,$missingCols)&&(!isset($data[$k])||!$data[$k]))
					$missingCols[] = $k;
			R::store($b);
			unset($b);
		}
		$completesCols = array_diff($allCols,$missingCols);
		if($this->debug>1){
			print('$allCols');
			print_r($allCols);
			print('$completesCols');
			print_r($completesCols);
			print('$missingCols');
			print_r($missingCols);
		}
	}
}