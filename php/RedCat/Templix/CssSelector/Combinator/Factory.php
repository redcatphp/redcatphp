<?php
namespace RedCat\Templix\CssSelector\Combinator;
use Closure;
class Factory{
	static function getInstance($classname, $userDefFunction = null){
		$class = "RedCat\\Templix\\CssSelector\\Combinator\\".$classname;
		return new $class($userDefFunction);
	}
}