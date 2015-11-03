<?php
namespace RedCat\Route\Match;
class Regex{
	private $match;
	function __construct($match){
		$this->match = $match;
	}
	function __invoke($uri){
		if(preg_match($this->match, $uri, $params)){
			array_shift($params);
			return array_values($params);
		}
	}
}