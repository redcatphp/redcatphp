<?php
namespace Wild\Templix\CssSelector\Combinator;
use Closure;
class Factory{
	static function getInstance($classname, $userDefFunction = null){
		$class = "Wild\\Templix\\CssSelector\\Combinator\\".$classname;
		return new $class($userDefFunction);
	}
}