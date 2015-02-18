<?php
namespace Surikat\Templator\CssSelector\Combinator;
use Surikat\Templator\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorAdjacent extends CssParserCombinator{
	function filter($node, $tagname){
		$ret = [];
		if ($element = $node->nextSibling)
			array_push($ret, $element);
		return $ret;
	}
}