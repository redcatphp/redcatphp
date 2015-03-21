<?php
namespace Surikat\Templator\CssSelector\Combinator;
use Surikat\Templator\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorDescendant extends CssParserCombinator{
	function filter($node, $tagname){
		return $node->getElementsByTagName($tagname);
	}
}