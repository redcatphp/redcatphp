<?php
namespace Templix\CssSelector\Combinator;
use Closure;
use Templix\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorFactory{
	static function getInstance($classname, $userDefFunction = null){
		$class = "Templix\\CssSelector\\Combinator\\".$classname;
		return new $class($userDefFunction);
	}
}