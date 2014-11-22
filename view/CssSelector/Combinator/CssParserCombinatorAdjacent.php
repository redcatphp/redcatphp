<?php
namespace surikat\view\CssSelector\Combinator;
use surikat\view\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorAdjacent extends CssParserCombinator{
	function filter($node, $tagname){
		$ret = [];
		if ($element = $node->nextSibling)
			array_push($ret, $element);
		return $ret;
	}
}