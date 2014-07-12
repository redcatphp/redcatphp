<?php namespace surikat;
use surikat\model\Query;
use surikat\model\Query4D;
class model {
	static function __callStatic($f,$args){
		$cl = 'surikat\model\Query';
		if(substr($f,-2)=='4D'){
			$cl .= '4D';
			$f = substr($f,0-2);
		}
		$q = new $cl(array_shift($args));
		foreach((array)array_shift($args) as $method=>$params)
			call_user_func_array(array($q,$method),(array)$params);
		return $q->$f();
	}
}