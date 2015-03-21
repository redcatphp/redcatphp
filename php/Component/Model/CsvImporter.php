<?php namespace Surikat\Model;
use Surikat\Model\R;
use Surikat\Model\CsvIterator;
class CsvImporter{
	private $debug;
	private $utf8_encode;
	private $separator = ';';
	private $csvDir;
	private $callback;
	private $freeze;
	private $breakOnError;
	private $checkUniq;
	private $addCols;
	private $addColsAfter;
	function __construct($options=[]){
		$this->csvDir = SURIKAT_PATH.'.data/';
		foreach($options as $k=>$v)
			$this->$k = $v;
	}
	static function importation($table,$allCols,$orderCols=null,$options=[]){
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
		$missingCols = [];
		$completesCols = [];
		$continue = false;
		foreach($csvIterator as $i=>$data){
			if($this->callback)
				call_user_func_array($this->callback,[&$data,&$continue]);
			if($continue){
				$continue = false;
				continue;
			}
			if($this->debug&&$this->debug!=3)
				print_r($data);
			$b = R::create($table);
			foreach($data as $k=>$v){
				if(strpos($k,'xown')===0&&ctype_upper(substr($k,4,1))){
					foreach($v as $xown)
						$b->noLoad()->{$k}[] = $xown;
				}
				else
					$b->$k = $v;
				
				$allCols[] = $k;
			}
			foreach($allCols as $k)
				if(!in_array($k,$missingCols)&&(!isset($data[$k])||!$data[$k]))
					$missingCols[] = $k;
			if(isset($this->breakOnError))
				$b->breakOnError($this->breakOnError);
			if(isset($this->checkUniq))
				$b->checkUniq($this->checkUniq);
			R::store($b);
			unset($b);
			if($this->freeze&&$i==1)
				R::freeze(true);
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