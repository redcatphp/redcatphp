<?php namespace Unit\RouteMatch;
use Unit\RouteMatch;
class Suffix extends RouteMatch{
	function __invoke($uri){
		$match = ltrim($this->match,'/');
		if(strrpos($uri,$match)===strlen($uri)-strlen($match)){
			return (string)substr($uri,strlen($match));
		}
	}
}