<?php namespace Surikat\Route;
class Regex extends Route{
	private $match;
	function __construct($match){
		$this->match = $match;
	}
	function __invoke($uri){
		if(preg_match($match, $uri, $params)){
			array_shift($params);
			return array_values($params);
		}
	}
}