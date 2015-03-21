<?php namespace Surikat\Dev;
class Chrono {
	private $sizeFactors = 'BKMGTP';
	private $start;
	private $end;
	function sizeFromBytes($bytes,$dec=2){
		return rtrim(sprintf("%.{$dec}f",(float)($bytes)/(float)pow(1024,$factor=floor((strlen($bytes)-1)/3))),'.0').' '.@$this->sizeFactors[$factor].($factor?'B':'ytes');
	}
	function requestTime($dec=2){
		if(isset($_SERVER["REQUEST_TIME_FLOAT"]))
			return $this->format(microtime(true)-$_SERVER["REQUEST_TIME_FLOAT"],$dec);
	}
	function format($v,$dec=2){
		return $this->formatTime($v,$dec)." | ".$this->sizeFromBytes(memory_get_peak_usage(),$dec);
	}
	function formatTime($v,$dec=2){
		if($v>=1){
			$u = 's';
		}
		else{
			$v = $v*(float)1000;
			$u = 'ms';
		}
		return sprintf("%.{$dec}f", $v).' '.$u;
	}
	function __construct($name=null){
		$this->name = isset($name)?$name:uniqid('chrono');
		$this->start();
	}
	function start(){
		return $this->start = microtime(true);
	}
	function end(){
		return $this->end = microtime(true);
	}
	function getLength(){
		$end = $this->end?$this->end:microtime(true);
		return $end-$this->start;
	}
	function display($dec=2){
		return $this->formatTime($this->getLength(),$dec);
	}
	function show($dec=2){
		echo '<pre>'.$this->display($dec)."\r\n".'</pre>';
	}
	function showAll($dec=2){
		echo '<pre>'.$this->name.':'.$this->display()." | ".$this->sizeFromBytes(memory_get_peak_usage(),$dec)."\r\n".'</pre>';
	}
	
	
}