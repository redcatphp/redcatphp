<?php
namespace Surikat\Component\Templix\CssSelector\Combinator;
use Surikat\Component\Templix\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorAdjacent extends CssParserCombinator{
	function filter($node, $tagname){
		$ret = [];
		if ($element = $node->nextSibling)
			array_push($ret, $element);
		return $ret;
	}
}