<?php
namespace Surikat\Component\Templix\CssSelector\Combinator;
use Surikat\Component\Templix\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorDescendant extends CssParserCombinator{
	function filter($node, $tagname){
		return $node->getElementsByTagName($tagname);
	}
}