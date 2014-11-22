<?php
namespace surikat\view\CssSelector\Combinator;
use surikat\view\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorDescendant extends CssParserCombinator{
	function filter($node, $tagname){
		return $node->getElementsByTagName($tagname);
	}
}