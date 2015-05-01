<?php
namespace Templix\CssSelector\Combinator;
use Templix\CssSelector\Combinator\CssParserCombinator;
class CssParserCombinatorChild extends CssParserCombinator{
	function filter($node, $tagname){
		return $node->childNodes;
	}
}