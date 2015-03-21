<?php
namespace Surikat\Templator\CssSelector\Combinator;
use Closure;
use Surikat\Templator\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorFactory{
	static function getInstance($classname, $userDefFunction = null){
		$class = "Surikat\\Templator\\CssSelector\\Combinator\\".$classname;
		return new $class($userDefFunction);
	}
}