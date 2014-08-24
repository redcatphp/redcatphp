<?php namespace surikat\control; 
class chrono {
	static $sizeFactors = 'BKMGTP';
	static $time;
	static function sizeFromBytes($bytes,$dec=2){
		return rtrim(sprintf("%.{$dec}f",(float)($bytes)/(float)pow(1024,$factor=floor((strlen($bytes)-1)/3))),'.0').' '.@self::$sizeFactors[$factor].($factor?'B':'ytes');
	}
	static function get($dec=2){
		if(isset($_SERVER["REQUEST_TIME_FLOAT"]))
			return self::format(microtime(true)-$_SERVER["REQUEST_TIME_FLOAT"],$dec);
	}
	static function format($v,$dec=2){
		return self::formatTime($v,$dec)." | ".self::sizeFromBytes(memory_get_peak_usage(),$dec);
	}
	static function formatTime($v,$dec=2){
		return sprintf("%.{$dec}f", $v*(float)1000)." ms";
	}

	private static $__times = [];
	static function start($k){
		return self::$__times[$k] = microtime(true);
	}
	static function end($k){
		return self::$__times[$k] = microtime(true)-self::$__times[$k];
	}
	static function display($k,$dec=2){
		return self::formatTime(self::end($k),$dec);
	}
	static function show($k,$dec=2){
		echo '<pre>'.self::display($k,$dec)."\r\n".'</pre>';
	}
	static function showAll($k,$dec=2){
		echo '<pre>'.$k.':'.self::display($k)." | ".self::sizeFromBytes(memory_get_peak_usage(),$dec)."\r\n".'</pre>';
	}
	
	
}