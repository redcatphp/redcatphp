<?php
namespace Wild\Route\Match;
class Prefix{
	private $match;
	function __construct($match){
		$this->match = $match;
	}
	function __invoke($uri){
		$match = ltrim($this->match,'/');
		if(empty($match)){
			if(empty($uri))
				return '';
		}
		elseif(strpos($uri,$match)===0){
			return (string)substr($uri,strlen($match));
		}
	}
}