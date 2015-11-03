<?php
namespace RedCat\Route\Match;
class Extension{
	private $extensions;
	function __construct($extensions){
		$this->extensions = is_string($extensions)?explode('|',$extensions):$extensions;
	}
	function __invoke($uri){
		$e = strtolower(pathinfo($uri,PATHINFO_EXTENSION));
		if($e&&in_array($e,$this->extensions)){
			return [(string)substr($uri,0,-1*(strlen($e)+1)),$e];
		}
	}
}