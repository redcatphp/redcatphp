<?php namespace Unit\RouteMatch;
use Unit\RouteMatch;
class Extension extends RouteMatch{
	private $extension;
	function __invoke($uri){
		if(is_string($this->match))
			$this->match = explode('|',$this->match);
		$e = pathinfo($uri,PATHINFO_EXTENSION);
		$this->extension = strtolower($e);
		if($e&&in_array($this->extension,$this->match)){
			return (string)substr($uri,0,-1*(strlen($e)+1));
		}
	}
	function extension(){
		return $this->extension;
	}
}