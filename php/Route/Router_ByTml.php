<?php namespace Surikat\Route;
use ArrayAccess;
class Router_ByTml extends Router_SuperURI{
	protected $match;
	function match($url){
		$params = parent::match($url);
		if(is_file(SURIKAT_PATH.'tml/'.$params[0].'.tml'))
			return $params;
	}
}