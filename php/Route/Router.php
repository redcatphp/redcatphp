<?php namespace Surikat\Route;
class Router {
	protected $match;
	function __construct($match=null){
		$this->match = $match;
	}
	function match($url){
		$match = $this->match;
		if(is_string($match)){
			if(strpos($match,'/^')===0&&strrpos($match,'$/')-strlen($match)===-2){
				$match = function($url)use($match){
					if(preg_match($pattern, $url, $params)){
						array_shift($params);
						return array_values($params);
					}
				};
			}
			else{
				$match = function($url)use($match){
					if(strpos($url,$match)===0){
						return substr($url,strlen($match));
					}
				};
			}
		}
		return call_user_func_array($match,func_get_args());
	}
}