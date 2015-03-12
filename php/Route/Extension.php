<?php namespace Surikat\Route;
class Extension extends Route{
	private $match;
	private $extension;
	function __construct($match){
		$this->match = $match;
		if(is_string($this->match))
			$this->match = explode('|',$this->match);
	}
	function __invoke($uri){
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