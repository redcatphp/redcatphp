<?php namespace Surikat\Route;
class Prefix{
	private $match;
	function __construct($match){
		$this->match = ltrim($match,'/');
	}
	function __invoke($uri){
		if(strpos($uri,$this->match)===0){
			return (string)substr($uri,strlen($this->match));
		}
	}
}