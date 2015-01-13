<?php namespace Surikat\Route;
class Suffix{
	private $match;
	function __construct($match){
		$this->match = ltrim($match,'/');
	}
	function __invoke($uri){
		if(strrpos($uri,$this->match)===strlen($uri)-strlen($this->match)){
			return substr($uri,strlen($this->match));
		}
	}
}