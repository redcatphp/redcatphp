<?php namespace surikat;
use surikat\model\Query;
use surikat\model\Query4D;
class model {
	static function __callStatic($f,$args){
		$cl = 'surikat\model\Query';
		if(strpos($f,'new')===0&&ctype_upper(substr($f,3,1))){
			$n = new $cl();
			$m = substr($f,3);
			return call_user_func_array([$n,$m],$args);
		}
		$q = new $cl(array_shift($args));
		foreach((array)array_shift($args) as $method=>$params)
			call_user_func_array([$q,$method],(array)$params);
		return $q->$f();
	}
}