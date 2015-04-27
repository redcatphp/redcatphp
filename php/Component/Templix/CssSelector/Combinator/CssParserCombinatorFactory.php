<?php
namespace Surikat\Component\Templix\CssSelector\Combinator;
use Closure;
use Surikat\Component\Templix\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorFactory{
	static function getInstance($classname, $userDefFunction = null){
		$class = "Surikat\\Component\\Templix\\CssSelector\\Combinator\\".$classname;
		return new $class($userDefFunction);
	}
}