<?php
namespace Surikat\Component\Templator\CssSelector\Combinator;
use Surikat\Component\Templator\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorDescendant extends CssParserCombinator{
	function filter($node, $tagname){
		return $node->getElementsByTagName($tagname);
	}
}