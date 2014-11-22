<?php
namespace surikat\view\CssSelector\Combinator;
use surikat\view\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorDescendant extends CssParserCombinator{
	public function filter($node, $tagname){
		return $node->getElementsByTagName($tagname);
	}
}