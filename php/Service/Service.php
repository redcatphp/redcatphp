<?php namespace Surikat\Service;
abstract class Service {
	static function __callStatic($func, array $args=[]){
		list($c,$m) = self::__funcToCm($func);
		if($m&&class_exists($c))
			return call_user_func_array([$c,$m],$args);
	}
	static function method($func){
		$func = str_replace('/','_',$func);
		list($c,$m) = self::__funcToCm($func);
		if($m&&class_exists($c)){
			$c::$m();
			return true;
		}
	}
	protected static function StudlyCaps($str){
		$str = ucwords(str_replace('_', ' ', $str));
        $str = str_replace(' ', '', $str);
		return $str;
	}
	protected static function __funcToCm($func){
		$pos = strpos($func,'_');
		$c = 'Service\\Service';
		if($pos){
			$c = $c.'_'.ucfirst(substr($func,0,$pos));
			$m = lcfirst(substr($func,$pos+1));
		}
		else{
			$c = $c.'_'.ucfirst($func);
			$m = 'method';
		}
		$c = self::StudlyCaps($c);
		$m = self::StudlyCaps($m);
		return [$c,$m];
	}
}