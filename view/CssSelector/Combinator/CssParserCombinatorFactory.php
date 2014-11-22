<?php
namespace surikat\view\CssSelector\Combinator;
use Closure;
use surikat\view\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorFactory{
	static function getInstance($classname, $userDefFunction = null){
		$class = "surikat\\view\\CssSelector\\Combinator\\".$classname;
		return new $class($userDefFunction);
	}
}