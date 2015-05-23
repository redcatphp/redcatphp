<?php namespace Unit\RouteMatch;
use Unit\RouteMatch;
class Prefix extends RouteMatch{
	function __invoke($uri){
		$match = ltrim($this->match,'/');
		if(strpos($uri,$match)===0){
			return (string)substr($uri,strlen($match));
		}
	}
}