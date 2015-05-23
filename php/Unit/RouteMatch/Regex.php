<?php namespace Unit\RouteMatch;
use Unit\RouteMatch;
class Regex extends RouteMatch{
	function __invoke($uri){
		if(preg_match($this->match, $uri, $params)){
			array_shift($params);
			return array_values($params);
		}
	}
}