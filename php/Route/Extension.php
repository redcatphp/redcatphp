<?php namespace Surikat\Route;
class Extension{
	private $match;
	function __construct($match){
		$this->match = $match;
		if(is_string($this->match))
			$this->match = explode('|',$this->match);
	}
	function __invoke($uri){
		$e = pathinfo($uri,PATHINFO_EXTENSION);
		if($e&&in_array(strtolower($e),$this->match)){
			return substr($uri,0,-1*(strlen($e)+1));
		}
	}
}