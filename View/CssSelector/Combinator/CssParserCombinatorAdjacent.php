<?php
namespace Surikat\View\CssSelector\Combinator;
use Surikat\View\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorAdjacent extends CssParserCombinator{
	function filter($node, $tagname){
		$ret = [];
		if ($element = $node->nextSibling)
			array_push($ret, $element);
		return $ret;
	}
}