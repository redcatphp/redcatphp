<?php
namespace Wild\Route\Match;
class Suffix{
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
		elseif(strrpos($uri,$match)===strlen($uri)-strlen($match)){
			return (string)substr($uri,strlen($match));
		}
	}
}