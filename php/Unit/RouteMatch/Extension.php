<?php namespace Unit\RouteMatch;
class Extension{
	private $match;
	function __construct($match){
		$this->match = $match;
	}
	function __invoke($uri){
		if(is_string($this->match))
			$this->match = explode('|',$this->match);
		$e = strtolower(pathinfo($uri,PATHINFO_EXTENSION));
		if($e&&in_array($this->extension,$this->match)){
			return [(string)substr($uri,0,-1*(strlen($e)+1)),$e];
		}
	}
}