<?php
namespace surikat\view\CssSelector\Parser\Combinator;
use Closure;
use surikat\view\CssSelector\Parser\Combinator\CssParserCombinator;
class CssParserCombinatorFactory{
	public static function getInstance($classname, $userDefFunction = null){
		$class = "surikat\\view\\CssSelector\\Parser\\Combinator\\".$classname;
		return new $class($userDefFunction);
	}
}