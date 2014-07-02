<?php namespace surikat\control;
use control;
use model\R;
//use control\CsvIterator;
class CsvImporter{
	private $debug;
	private $utf8_encode;
	private $separator = ';';
	private $csvDir;
	private $callback;
	function __construct($options=array()){
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
		$continue = false;
		foreach($csvIterator as $i=>$data){
			if($this->callback)
				call_user_func_array($this->callback,array(&$data,&$continue));
			if($continue){
				$continue = false;
				continue;
			}
			if($this->debug&&$this->debug!=3)
				print_r($data);
			$b = R::dispense($table);
			foreach($allCols as $k)
				$b->$k = $data[$k];

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