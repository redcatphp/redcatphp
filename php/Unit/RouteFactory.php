<?php namespace Unit;
use Unit\Route\Regex;
use Unit\Route\Prefix;
class RouteFactory {
	static function getInstance($uri){
		if(is_string($uri)){
			if(strpos($uri,'/^')===0&&strrpos($uri,'$/')-strlen($uri)===-2){
				$uri = new Regex($uri);
			}
			else{
				$uri = new Prefix($uri);
			}
		}
		return $uri;
	}
}