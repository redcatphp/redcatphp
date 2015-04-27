<?php
namespace Surikat\Component\Templix\CssSelector\Combinator;
use Surikat\Component\Templix\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorChild extends CssParserCombinator{
	function filter($node, $tagname){
		return $node->childNodes;
	}
}