<?php namespace Surikat\Route;
class Suffix extends Route{
	private $match;
	function __construct($match){
		$this->match = ltrim($match,'/');
	}
	function __invoke($uri){
		if(strrpos($uri,$this->match)===strlen($uri)-strlen($this->match)){
			return (string)substr($uri,strlen($this->match));
		}
	}
}