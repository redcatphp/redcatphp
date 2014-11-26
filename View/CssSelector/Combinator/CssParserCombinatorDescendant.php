<?php
namespace Surikat\View\CssSelector\Combinator;
use Surikat\View\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorDescendant extends CssParserCombinator{
	function filter($node, $tagname){
		return $node->getElementsByTagName($tagname);
	}
}