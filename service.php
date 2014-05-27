<?php namespace surikat; 
abstract class service {
	static function __callStatic($func,Array $args=array()){
		list($c,$m) = self::__funcToCm($func);
		if($m&&class_exists($c))
			return call_user_func_array(array($c,$m),$args);
	}
	static function method($func){
		list($c,$m) = self::__funcToCm($func);
		if($m&&class_exists($c)){
			$c::$m();
			return true;
		}
	}
	static function __funcToCm($func){
		$pos = strpos($func,'_');
		$c = 'service\\Service';
		if($pos){
			$c = $c.'_'.ucfirst(substr($func,0,$pos));
			$m = lcfirst(substr($func,$pos+1));
		}
		else{
			$c = $c.'_'.ucfirst($func);
			$m = 'method';
		}
		return array($c,$m);
	}
}
