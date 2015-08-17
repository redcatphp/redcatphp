<?php
namespace Wild\DataMap\Helper;
abstract class CaseConvert{
	static function snake($str){
        return str_replace(' ', '_', strtolower(preg_replace('/([a-z])([A-Z])/', '$1 $2', $str)));
	}
	static function camel($str){
		return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $str))));
	}
	static function pascal($str){
		return ucfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $str))));
	}
	static function ucw($str){
		return ucfirst(str_replace(' ', '_', ucwords(str_replace('_', ' ', $str))));
	}
	static function lcw($str){
		return lcfirst(str_replace(' ', '_', preg_replace_callback('~\b\w~', ['self', '_lcwordsCallback'],str_replace('_', ' ', $str))));
	}
	private static function _lcwordsCallback($matches){
		return strtolower($matches[0]);
	}
}