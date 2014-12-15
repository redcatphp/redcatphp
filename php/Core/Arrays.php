<?php namespace Surikat\Core;
class Arrays{
	static function merge_recursive(){
		$args = func_get_args();
		$merged = array_shift($args);
		foreach($args as $array2){
			if(!is_array($array2)){
				continue;
			}
			foreach($array2 as $key => &$value){
				if(is_array($value)&&isset($merged [$key])&&is_array($merged[$key])){
					$merged[$key] = self::merge_recursive($merged[$key],$value);
				}
				else{
					$merged[$key] = $value;
				}
			}
		}
		return $merged;
	}
	static function values_recursive($key,$arr){
		$val = [];
		array_walk_recursive($arr, function($v, $k) use($key, &$val){
			if($k == $key) array_push($val, $v);
		});
		return count($val) > 1 ? $val : array_pop($val);
	}
	static function unique($arr){
		return array_filter($arr, function($obj){
			static $idList = array();
			if(in_array($obj,$idList,true)){
				return false;
			}
			$idList[] = $obj;
			return true;
		});
	}
}
