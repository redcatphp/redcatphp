<?php
namespace surikat\view\CssSelector\Combinator;
use surikat\view\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorChild extends CssParserCombinator{
	public function filter($node, $tagname){
		return $node->childNodes;
	}
}