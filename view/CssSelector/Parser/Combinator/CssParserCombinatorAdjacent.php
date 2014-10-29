<?php
namespace surikat\view\CssSelector\Parser\Combinator;
use surikat\view\CssSelector\Parser\Combinator\CssParserCombinator;
class CssParserCombinatorAdjacent extends CssParserCombinator{
	public function filter($node, $tagname){
		$ret = [];
		if ($element = $node->nextSibling)
			array_push($ret, $element);
		return $ret;
	}
}