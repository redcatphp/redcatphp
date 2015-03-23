<?php
namespace Surikat\Component\Templator\CssSelector\Combinator;
use Closure;
use Surikat\Component\Templator\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorFactory{
	static function getInstance($classname, $userDefFunction = null){
		$class = "Surikat\\Component\\Templator\\CssSelector\\Combinator\\".$classname;
		return new $class($userDefFunction);
	}
}