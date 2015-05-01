<?php
namespace Templix\CssSelector\Combinator;
use Templix\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorDescendant extends CssParserCombinator{
	function filter($node, $tagname){
		return $node->getElementsByTagName($tagname);
	}
}