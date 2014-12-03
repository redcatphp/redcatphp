<?php
namespace Surikat\View\CssSelector\Combinator;
use Closure;
use Surikat\View\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorFactory{
	static function getInstance($classname, $userDefFunction = null){
		$class = "Surikat\\View\\CssSelector\\Combinator\\".$classname;
		return new $class($userDefFunction);
	}
}