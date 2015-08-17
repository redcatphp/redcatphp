<?php
namespace Wild\Templix\CssSelector\Combinator;
use Closure;
use Wild\Templix\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorFactory{
	static function getInstance($classname, $userDefFunction = null){
		$class = "Wild\\Templix\\CssSelector\\Combinator\\".$classname;
		return new $class($userDefFunction);
	}
}