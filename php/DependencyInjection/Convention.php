<?php namespace Surikat\DependencyInjection;
class Convention{
	static function toClass($value){
		return str_replace('_','\\',$value);
	}
	static function toMethod($value){
		return str_replace('\\','_',$value);
	}
	static function interfaceSubstitutionDefaultClass(&$value){
		$value = self::toClass($value);
		if(interface_exists($value)){
			$pos = strrpos($value,'\\');
			if($pos===false)
				$value .= '\\'.$value;
			else
				$value .= substr($value,strrpos($value,'\\'));
		}
		if(strpos($value,'\\')===false)
			$value = $value.'\\'.$value;
		return $value;
	}
	static function toClassMixed($value){
		if(is_array($value)){
			if(isset($value[0])){
				self::interfaceSubstitutionDefaultClass($value[0]);
			}
		}
		elseif(is_string($value)){
			self::interfaceSubstitutionDefaultClass($value);
		}
		return $value;
	}
}