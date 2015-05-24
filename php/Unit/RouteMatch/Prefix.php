<?php namespace Unit\RouteMatch;
class Prefix{
	private $match;
	function __construct($match){
		$this->match = $match;
	}
	function __invoke($uri){
		$match = ltrim($this->match,'/');
		if(strpos($uri,$match)===0){
			return (string)substr($uri,strlen($match));
		}
	}
}