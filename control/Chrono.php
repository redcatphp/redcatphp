<?php namespace surikat\control; 
class Chrono {
	static $sizeFactors = 'BKMGTP';
	static $time;
	static function sizeFromBytes($bytes,$dec=2){
		return rtrim(sprintf("%.{$dec}f",(float)($bytes)/(float)pow(1024,$factor=floor((strlen($bytes)-1)/3))),'.0').' '.@self::$sizeFactors[$factor].($factor?'B':'ytes');
	}
	static function get($dec=2){
		if(isset($_SERVER["REQUEST_TIME_FLOAT"]))
			return sprintf("%.{$dec}f", (microtime(true)-$_SERVER["REQUEST_TIME_FLOAT"])*(float)1000)." ms | ".self::sizeFromBytes(memory_get_peak_usage(),$dec);
	}
}
